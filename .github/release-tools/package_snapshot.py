"""Allowlisted release snapshots; never copy Git history or private workflows."""
import hashlib
import json
import re
import subprocess
from pathlib import Path, PurePosixPath

PACKAGE = 'stoneholl/module-core'
ROOT_FILES = {'composer.json', 'registration.php', 'LICENSE', 'README.md', 'CHANGELOG.md',
              'phpcs.xml.dist', 'phpunit.xml.dist'}
ROOT_DIRS = {'Api', 'Block', 'Controller', 'Model', 'Test', 'etc', 'view'}
INSTALL_FILES = {'composer.json', 'registration.php', 'LICENSE', 'README.md', 'CHANGELOG.md'}
INSTALL_DIRS = {'Api', 'Block', 'Controller', 'Model', 'etc', 'view'}
FORBIDDEN = re.compile(r'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----|gh[pousr]_[A-Za-z0-9]{30,}|AKIA[0-9A-Z]{16}')
VERSION = re.compile(r'^[0-9]+\.[0-9]+\.[0-9]+$')


def version(value):
    if not VERSION.fullmatch(value):
        raise ValueError('A stable three-component SemVer is required')
    return value


def allowed(name):
    path = PurePosixPath(name)
    if path.is_absolute() or '..' in path.parts or not path.parts or any(p.startswith('.') for p in path.parts):
        return False
    return (len(path.parts) == 1 and name in ROOT_FILES) or path.parts[0] in ROOT_DIRS


def validate_files(files):
    for name, data in files.items():
        if not allowed(name) or len(data) > 5 * 1024 * 1024:
            raise ValueError('Unexpected package path or oversized file: ' + name)
        if FORBIDDEN.search(data.decode('utf-8', errors='replace')):
            raise ValueError('Credential-shaped content rejected in ' + name)
    for name in ['composer.json', 'registration.php', 'LICENSE', 'README.md', 'CHANGELOG.md', 'etc/module.xml']:
        if name not in files:
            raise ValueError('Required package file missing: ' + name)
    if json.loads(files['composer.json'])['name'] != PACKAGE:
        raise ValueError('Unexpected Composer package')


def installable_files(files):
    """Keep repository test tooling out of the customer installation archive."""
    validate_files(files)
    return {name: data for name, data in files.items()
            if name in INSTALL_FILES or PurePosixPath(name).parts[0] in INSTALL_DIRS}


def hashes(files):
    validate_files(files)
    return {name: hashlib.sha256(data).hexdigest() for name, data in sorted(files.items())}


def source_files(root, revision):
    raw = subprocess.check_output(['git', '-C', str(root), 'ls-tree', '-rz', revision])
    files = {}
    for entry in raw.split(b'\0'):
        if not entry:
            continue
        header, rawname = entry.split(b'\t', 1)
        mode, kind, oid = header.decode().split()
        name = rawname.decode('utf-8')
        if name == '.gitignore' or name.startswith('.github/'):
            continue
        if not allowed(name):
            raise ValueError('Unexpected source path needs an allowlist review: ' + name)
        if mode not in ('100644', '100755') or kind != 'blob':
            raise ValueError('Symlink/submodule is not a distributable file: ' + name)
        files[name] = subprocess.check_output(['git', '-C', str(root), 'cat-file', 'blob', oid])
    validate_files(files)
    return files


def read_files(root, manifest):
    result = {}
    for name in manifest['files']:
        if not allowed(name):
            raise ValueError('Invalid manifest path: ' + name)
        path = root / name
        if path.is_symlink() or not path.is_file():
            raise ValueError('Missing file or symlink: ' + name)
        result[name] = path.read_bytes()
    return result


def verify(root):
    manifest = json.loads((root / 'release.json').read_text())
    version(manifest['version'])
    if manifest['package'] != PACKAGE or type(manifest['publish']) is not bool:
        raise ValueError('Invalid release metadata')
    actual = hashes(read_files(root, manifest))
    if actual != manifest['files']:
        raise ValueError('Package fingerprint differs from the approved snapshot')
    expected = set(manifest['files'])
    for path in root.rglob('*'):
        name = path.relative_to(root).as_posix()
        if name.split('/')[0] in ('.git', '.github'):
            continue
        if path.is_symlink():
            raise ValueError('Symlink in public tree: ' + name)
        if path.is_file() and name not in expected and name not in ('release.json', '.gitattributes'):
            raise ValueError('Unexpected public file: ' + name)
    return manifest


def export(root, revision, destination, release_version, approval, publish):
    release_version = version(release_version)
    files = source_files(root, revision)
    expected = hashes(files)
    if approval['version'] != release_version or approval['files'] != expected:
        raise ValueError('Native validation approval does not match this package snapshot')
    if not approval.get('runtime_tested') or approval.get('review_status') != 'passed':
        raise ValueError('Missing successful native release review')
    changelog = files['CHANGELOG.md'].decode()
    if not re.search(r'^## ' + re.escape(release_version) + r'(?:\s|$)', changelog, re.M):
        raise ValueError('Changelog does not identify this release')
    destination.mkdir(parents=True, exist_ok=True)
    if (destination / 'release.json').exists():
        previous = verify(destination)
        old = tuple(int(x) for x in previous['version'].split('.'))
        new = tuple(int(x) for x in release_version.split('.'))
        if new < old or (new == old and previous['files'] != expected):
            raise ValueError('Refusing a downgrade or changed existing version')
        for name in previous['files']:
            (destination / name).unlink()
    for name, data in files.items():
        path = destination / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_bytes(data)
        path.chmod(0o644)
    manifest = {'package': PACKAGE, 'version': release_version, 'publish': bool(publish),
                'files': expected, 'runtime_tested': approval['runtime_tested'],
                'other_composer_constraints': 'declared; not runtime validated'}
    (destination / 'release.json').write_text(json.dumps(manifest, indent=2) + '\n')
    verify(destination)
    return manifest


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument('command', choices=['verify', 'export'])
    parser.add_argument('--root', type=Path, default=Path('.'))
    parser.add_argument('--revision', default='HEAD')
    parser.add_argument('--destination', type=Path)
    parser.add_argument('--version')
    parser.add_argument('--approval', type=Path)
    parser.add_argument('--publish', action='store_true')
    args = parser.parse_args()
    if args.command == 'verify':
        result = verify(args.root)
    else:
        result = export(args.root, args.revision, args.destination, args.version,
                        json.loads(args.approval.read_text()), args.publish)
    print(json.dumps({'package': result['package'], 'version': result['version'],
                      'files': len(result['files']), 'publish': result['publish']}))
