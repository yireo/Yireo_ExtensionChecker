# Yireo ExtensionChecker
This extension validates the code of other extensions and is complementary to static code analysis tools like PHPCS.

## Example usage

    bin/magento yireo_extensionchecker:scan --module Yireo_ExampleAdminhtml

Running this command might give the following output:

    Dependency "Magento_Backend" not found module.xml
    Dependency "Magento_Ui" not found module.xml
    Dependency "magento/module-backend" not found composer.json. Current version is 101.0.2
    Dependency "magento/module-ui" not found composer.json. Current version is 101.1.2
    Dependency "psr/log" not found composer.json. Current version is 1.1.0

The output gives a hint to what to add to `composer.json`. For instance, a composer requirement `magento/module-ui` should be added and this could have a version constraint `^101.1` to match semantic versioning. Theoretically, this could also be `^101.0` or even `^100.0|^101.0`, but for this, deep-code analysis (by you) would be needed.

Note that you can also pass multiple modules to the `--module` flag by separating them with a comma:
```bash
bin/magento yireo_extensionchecker:scan --module Yireo_Example1,Yireo_Example2,Yireo_Example3
```

Listing dependencies (as in: dependencies detected by this ExtensionChecker) could be done with the following command: 
```bash
bin/magento yireo_extensionchecker:list-dependencies --module Yireo_Example
bin/magento yireo_extensionchecker:list-dependencies --module Yireo_Example --format=json | jq
```


## Installation
Install the module as a composer requirement for developer environments:

    composer require --dev yireo/magento2-extensionchecker
    bin/magento module:enable Yireo_ExtensionChecker
    
Note that if you want to scan a module, this module also needs to be enabled. Personally, we use this extension in our CI/CD chain, to make sure zero issues are reported at all times.

## Deprecated dependencies
Class dependencies (injected via the constructor) are inspected to see if they are deprecated, for the used Magento version. You can skip this behaviour by adding a flag `--hide-deprecated` to the command:

    bin/magento yireo_extensionchecker:scan --module Yireo_Example --hide-deprecated=1

## Undeclared dependencies
Class dependencies (injected via the constructor) are traced back to their corresponding module (or the framework or something else), which should be reflected upon in the `composer.json` file and the `module.xml` file. Of each composer dependencies, the current version is also reported.

Also, by tokenizing the PHP source, it is detected whether the `composer.json` file should reflect a specific PHP extension (for example, `ext-json`) when an extension-specific PHP function is used (for example, `json_encode`).

## @todo: Hard-coded Proxies
A Proxy is a DI trick which should be configured in the `di.xml` file of a module and not be hard-coded in PHP. The extension could report this.

## @todo: Check other methods for signature
If another method than the constructor contains type hints for imported namespaces, those namespaces lead to further dependencies with the module. For example, if a specific method returns an object of type `Magento/ModuleX/SomeInterface` then `Magento_ModuleX` would need to be reported as a dependency.

## @todo: Scan for `@since`
Scan class dependencies for `@since` and double-check if this minimum version matches with the composer requirements.

## File `.yireo-extension-checker.json`
Sometimes a scan shows that dependencies are not needed, even though you disagree. To override this, you can add a file
`.yireo-extension-checker.json` to your module folder with a content like the following:

```json
{
  "ignore": [
    "Yireo_Example",
    "yireo/magento2-example"
  ]
}
```


## Tip: Check multiple modules 
You can quickly check upon multiple modules with a command like this:

```bash
bin/magento yireo_extensionchecker:scan --module $(bin/magento module:status --enabled | grep -e Yireo_ | awk '{printf "%s%s",sep,$0; sep=","} END{print""}') --hide-needless 1 --hide-deprecated 1
```

## Generate module.xml
Based on the found dependencies, a sample `module.xml` output can be generated. Note that you will need to copy and paste the output yourself:

```bash
bin/magento yireo_extensionchecker:suggest:module-xml Yireo_Example
```

Likewise, the `require` section of the `composer.json` file can be generated too:

```bash
bin/magento yireo_extensionchecker:suggest:composer-json Yireo_Example
```
