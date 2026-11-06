# MEMORY

- Suggestions (`suggest:*`, `diff:*`, `autofix:*`) always prefer Magento core over Mage-OS: `Composer\MagentoPackageResolver` reads `vendor/mage-os/<pkg>/composer.json` `replace` (e.g. `{"magento/module-csp": "100.4.5"}`) for the Magento name/version. Mage-OS-only packages (no `magento/` in `replace`) keep their Mage-OS name. The scan itself treats `magento/` and `mage-os/` as equal (`ScanComposerRequirements::getAlternativePackageName()`).
- Unit tests: `hersir.py docker-compose exec -T -w /tmp/magento php-fpm vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist vendor/yireo/magento2-extensionchecker/Test/Unit`
- `yireo-extension-builder` lives in `~/bin` and needs `hersir` in `PATH` (symlink to `hersir.py`).
- Integration test install fails with "Unknown module in the requested list" when `app/etc/config.php` of the hersir env contains a module that is no longer installed; remove that stale entry.
