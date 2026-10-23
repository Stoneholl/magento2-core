# Stoneholl Core

Shared Admin menu, product registry and marketplace links for Stoneholl Magento modules.
Core works offline and requires no Stoneholl account. It makes no API requests and collects no telemetry.

## Installation

Once the package is available on Packagist, run from your Magento root:

```sh
composer require stoneholl/magento2-core:^1.0
bin/magento module:enable Stoneholl_Core
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

For manual installation, place the module files in `app/code/Stoneholl/Core` and run the Magento commands above.
Use either Composer or a manual copy, never both.

## Compatibility

Tested on Magento Open Source:

| Magento | PHP |
| --- | --- |
| 2.3.7-p4 | 7.4.33 |
| 2.4.9 | 8.5.9 |

Other Magento/PHP combinations and Adobe Commerce have not been runtime tested.

## Configuration

**Stoneholl > Products** lists products registered by installed modules and links to the marketplace and suggestions page.
Grant the **View Stoneholl Products** permission to Admin roles that need this page.

Set **Marketplace URL** under **Stores > Configuration > Stoneholl > Core**. The default is `https://stoneholl.com`.
Use an HTTPS URL without credentials, a query string or a fragment. Changing it updates marketplace and feedback links.
Connector also inherits the account link unless an Account URL Override is set. API and Composer URLs are configured separately.

## For module developers

Require `stoneholl/magento2-core`, declare `Stoneholl_Core` in `etc/module.xml`, and register a
[ProductProviderInterface](Api/ProductProviderInterface.php) implementation in `etc/di.xml` under
[ProductRegistry](Model/ProductRegistry.php)'s `providers` argument. Each product needs a unique code and its installed module/package names.

## Removal

Remove dependent modules before removing Core. Marketplace settings remain in Magento configuration storage.

## License

[MIT](LICENSE).

## Composer package naming

The canonical Composer name is `stoneholl/magento2-core`. The former `stoneholl/module-core` name is a legacy identity. The renamed package replaces that identity at `self.version`, so Composer installs one copy of the Magento module. Existing immutable releases remain unchanged.

When migrating an existing root requirement, back up the project and remove the old requirement without updating, then require the new package with its dependencies:

```bash
composer remove stoneholl/module-core --no-update
composer require stoneholl/magento2-core --with-all-dependencies
```

Run the Magento upgrade, compilation and cache commands documented above according to your deployment mode. Keep native `Stoneholl_*` module names and configuration paths unchanged.
