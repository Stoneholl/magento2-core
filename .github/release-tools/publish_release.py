"""Publish a verified public snapshot; retry without replacing existing versions/assets."""
import hashlib
import json
import os
import subprocess
import tempfile
import zipfile
from pathlib import Path
from package_snapshot import verify, read_files, installable_files


def gh(arguments, data=None, optional=False):
    result = subprocess.run(['gh'] + arguments, input=None if data is None else json.dumps(data),
                            text=True, capture_output=True)
    if result.returncode:
        if optional and 'HTTP 404' in result.stderr:
            return None
        raise RuntimeError(result.stderr.strip())
    return json.loads(result.stdout) if result.stdout.strip() else None


def release_for_tag(repository, tag):
    """The tag endpoint omits drafts; inspect the authenticated release list as fallback."""
    published = gh(['api', 'repos/' + repository + '/releases/tags/' + tag], optional=True)
    if published is not None:
        return published
    page, matches = 1, []
    while True:
        releases = gh(['api', 'repos/' + repository + '/releases?per_page=100&page=' + str(page)])
        matches.extend(item for item in releases if item['tag_name'] == tag)
        if len(releases) < 100:
            break
        page += 1
    if len(matches) > 1:
        raise ValueError('Multiple releases use this tag; review drafts before retrying')
    return matches[0] if matches else None


def assets(root, output, commit):
    manifest = verify(root)
    package_files = installable_files(read_files(root, manifest))
    output.mkdir(parents=True, exist_ok=True)
    archive = output / ('Stoneholl_Core-' + manifest['version'] + '.zip')
    with zipfile.ZipFile(archive, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as bundle:
        for name, data in sorted(package_files.items()):
            entry = zipfile.ZipInfo(name, (1980, 1, 1, 0, 0, 0))
            entry.compress_type = zipfile.ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            bundle.writestr(entry, data)
    compatibility = output / 'compatibility.json'
    compatibility.write_text(json.dumps({'package': manifest['package'], 'version': manifest['version'],
                                        'runtime_tested': manifest['runtime_tested'],
                                        'other_composer_constraints': manifest['other_composer_constraints']}, indent=2) + '\n')
    metadata = output / 'release-manifest.json'
    metadata.write_text(json.dumps({'package': manifest['package'], 'version': manifest['version'],
                                   'public_commit': commit,
                                   'files': {name: hashlib.sha256(data).hexdigest()
                                             for name, data in sorted(package_files.items())},
                                   'artifacts': {p.name: {'sha256': hashlib.sha256(p.read_bytes()).hexdigest(),
                                                         'bytes': p.stat().st_size}
                                                 for p in [archive, compatibility]}}, indent=2) + '\n')
    sums = output / 'SHA256SUMS'
    sums.write_text(''.join(hashlib.sha256(p.read_bytes()).hexdigest() + '  ' + p.name + '\n'
                           for p in [archive, compatibility, metadata]))
    return [archive, compatibility, metadata, sums]


def publish(root, output):
    repository = os.environ['GITHUB_REPOSITORY']
    if repository != 'Stoneholl/module-core':
        raise ValueError('Publication is restricted to the canonical public repository')
    commit = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=root, text=True).strip()
    if commit != os.environ['GITHUB_SHA']:
        raise ValueError('Checkout does not match the validated workflow commit')
    manifest = verify(root)
    tag = 'v' + manifest['version']
    files = assets(root, output, commit)
    existing_tag = gh(['api', 'repos/' + repository + '/git/ref/tags/' + tag], optional=True)
    if existing_tag:
        obj = existing_tag['object']
        if obj['type'] == 'tag':
            obj = gh(['api', 'repos/' + repository + '/git/tags/' + obj['sha']])['object']
        if obj['type'] != 'commit' or obj['sha'] != commit:
            raise ValueError('Existing tag identifies another commit; never overwrite it')
    release = release_for_tag(repository, tag)
    if release and not release['draft'] and not existing_tag:
        raise ValueError('Published release lacks its expected commit tag')
    if release and release['draft'] and release['target_commitish'] != commit and not existing_tag:
        raise ValueError('Existing draft targets another commit')
    if release is None:
        notes = root / 'CHANGELOG.md'
        result = subprocess.run(['gh', 'release', 'create', tag, '--repo', repository,
                                 '--target', commit, '--draft', '--title', 'Stoneholl Core ' + manifest['version'],
                                 '--notes-file', str(notes)], text=True, capture_output=True)
        if result.returncode:
            raise RuntimeError(result.stderr)
        release = release_for_tag(repository, tag)
        if release is None:
            raise ValueError('New release draft was not found; inspect releases before retrying')
    current_assets = {item['name']: item for item in release['assets']}
    for path in files:
        if path.name in current_assets:
            with tempfile.TemporaryDirectory() as directory:
                subprocess.run(['gh', 'release', 'download', tag, '--repo', repository,
                                '--pattern', path.name, '--dir', directory], check=True)
                if (Path(directory) / path.name).read_bytes() != path.read_bytes():
                    raise ValueError('Existing release asset differs; never replace it: ' + path.name)
        elif release['draft']:
            subprocess.run(['gh', 'release', 'upload', tag, str(path), '--repo', repository], check=True)
        else:
            raise ValueError('Published release is incomplete; manual review is required')
    if release['draft']:
        subprocess.run(['gh', 'release', 'edit', tag, '--repo', repository, '--draft=false', '--latest'], check=True)
    final = gh(['api', 'repos/' + repository + '/releases/tags/' + tag])
    if final['draft'] or not final.get('immutable'):
        raise ValueError('Expected published immutable release')
    print(json.dumps({'url': final['html_url'], 'version': manifest['version'],
                      'public_commit': commit, 'immutable': final['immutable']}))


if __name__ == '__main__':
    publish(Path('.').resolve(), Path(os.environ.get('RUNNER_TEMP', tempfile.gettempdir())) / 'core-release-assets')
