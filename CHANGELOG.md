# Changelog


## 1.0.1 - 2026-10-23

- Rename the Composer package to `stoneholl/magento2-core` and replace its corresponding legacy `stoneholl/module-core` identity at `self.version`.
- Publish the new version as a runtime-only package; preserve the original immutable 1.0.0 release.
- Keep Magento module identifiers, configuration, storage and licensing unchanged.

## 1.0.0 — 2026-10-18

- Add permission-controlled marketplace navigation configuration and safe marketplace/suggestion links.
- Provide a local marketplace URL service for inherited Connector account navigation.

- Document installation, upgrades, offline product registration, permissions and dependency-safe removal.
- Distinguish exact tested Magento/PHP pairs from Composer dependency constraints.
- Document method contracts and remove the global missing-method-docblock suppression.
- Add shared Stoneholl Admin navigation.
- Add offline product registry contracts and implementation.
