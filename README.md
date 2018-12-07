# Yireo ExtensionChecker
This extension validates the code of other extensions and is complementary to static code analysis tools like PHPCS.

    ./bin/magento yireo:extensionchecker:scan Yireo_Example

## Scanning for deprecated dependencies
Because a Magento installation is required for running the ExtensionChecker, it is able to read the PHP code of a specific Magento module and see if its dependencies are valid. One thing that it does is open those dependencies using the Reflection API to see if the sources are deprecated. This works complete with the use of the ObjectManager.

## Scanning for hard-coded Proxies
A Proxy is a DI trick which should be configured in the `di.xml` file of a module and not be hard-coded in PHP.