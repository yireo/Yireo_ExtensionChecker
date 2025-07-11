# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.5.9] - 05 June 2025
### Fixed
- Add option to skip license validation in composer.json #62 @iranimij
- Remove `Composer\Console\Input\InputArgument` #61 @frqnck
- Add $moduleName to output messages where not currently implemented #60 @gwharton

## [2.5.8] - 02 June 2025
### Fixed
- Suggest composer output CLI based on current composer.json

## [2.5.7] - 28 May 2025
### Fixed
- Fix wildcard output for `ext-` in new CLI

## [2.5.6] - 28 May 2025
### Fixed
- New CLI for suggesting composer.json and module.xml

## [2.5.5] - 22 May 2025
### Fixed
- Add `.yireo-extension-checker.json` file

## [2.5.4] - 22 May 2025
### Fixed
- Search PHTML templates for classes

## [2.5.3] - 17 May 2025
### Fixed
- Add composer/composer to deps because of usage Composer API
- Properly find classes behind factory classes
- Update MODULE.json
- Detect PHP classes in etc/ XML files
- Remove default param
- Do not skip abstract classes and interfaces when finding deps
- Do not skip test classes
- Add PHP 8.4
- Detect invalid composer license
- If no composer name, show module name

## [2.5.2] - 27 April 2025
### Fixed
- Prevent error if not semver version
- Fix PHP 8.4 deprecations
- Drop PHP 7.4
- Allow for whitelisting specific modules like `Magento_Store`

# [2.5.1] = 21 January 2025
### Fixed
- Not every occurance of find() is a CLI command (#58 @gwharton)

# [2.5.0] = 6 January 2025
### Fixed
- Prevent deps from deps to be reported #26

### Added
- New command `inspect-class`

# [2.4.4] = 29 August 2024
### Fixed
- Prevent exception when require and/or require-dev is empty

# [2.4.3] = 23 August 2024
### Fixed
- Fixes for dev requirements

# [2.4.2] = 12 August 2024
### Fixed
- Changed call in Nikic parser


# [2.4.1] = 24 June 2024
### Fixed
- Added JSON format to module list output

# [2.4.0] = 21 June 2024
### Added
- New CLI `yireo_extensionchecker:list:modules` (module name, enabled/disabled, composer version)

# [2.3.4] = 8 May 2024
### Fixed
- Do not pick up on nodes that are functions #54 @sprankhub

# [2.3.3] = 10 April 2024
### Fixed
- Remove exception for module with no sequence #52 @iranimij

# [2.3.2] = 26 March 2024
### Fixed
- Fix name issues #50 @iranimij

# [2.3.1] = 3 February 2024
### Fixed
- Additional fixes in performance

# [2.3.0] = 12 December 2023
### Added
- Check multiple modules at once by passing comma-seperated module-names to scan command #46 @iranimij


# [2.2.10] = 3 November 2023
### Fixed
- Detect more dependencies

# [2.2.9] = 15 October 2023
### Fixed
- Cleanup old code with dynamic properties

# [2.2.8] = 28 February 2023
### Fixed
- Add compatibility with older `composer` commands that lack `no-scripts` flag

# [2.2.7] = 21 February 2023
### Fixed
- Argument type compilation error

# [2.2.6] = 27 December 2022
### Added
- Add difference between hard and soft dependencies
- Add XML-file collector for `etc/` folder

### Fixed
- Make sure no table is outputted when messages count 0

# [2.2.5] = 5 December 2022
### Added
- New CLI `yireo_extensionchecker:list-classes`
- New CLI `yireo_extensionchecker:create-plantuml-diagram`

# [2.2.4] = 17 November 2022
### Fixed
- Fix path in DI proxy

# [2.2.3] = 17 November 2022
### Fixed
- Issue with missing DI proxy during M2 installation (also with Integration Tests) #38

# [2.2.2] = 15 November 2022
### Fixed
- Fix PHP 7.4 issues

# [2.2.1] = 15 November 2022
### Added
- Detect classnames referred to via Magento CLI `find('console:command')` 

# [2.2.0] = 11 October 2022
### Added
- New template parsing
- Simple detection of `Hyva_Theme` deps

# [2.1.4] = 11 October 2022
### Fixed 
- Typo prevents missing module.xml deps to be reported
- Implement verbose flag with some debugging
- Set default values for RuntimeConfig

# [2.1.3] = 8 October 2022
### Added
- First draft of CLI to check module compat with certain Magento version

### Fixed 
- Make sure that non-namespaced classes are picked up as well (#30)
- Reimplement flag "hide-needless" again

# [2.1.2] = 7 October 2022
### Fixed
- Remove exception when zero PHP files are found (#28)
- Don't suggest version for PHP dep and ext- deps (#27)

# [2.1.1] = 5 October 2022
### Fixed
- PHP comma not working in PHP 7.4 (#25 @sprankhub)

# [2.1.0] = 4 October 2022
### Added
- Add XML Layout detector to find component dependencies
- Added GitHub Actions for integration tests
- Use `nikic/php-parser` to better detect used FQCN

# [2.0.4] = 29 September 2022
### Fixed
- Only show exception messages in output with verbosity set
- Additional tests and fixes

# [2.0.3] = 29 September 2022
### Fixed
- Skip various needless errors
- Support for imported namespaces separated by comma
- Prevent incorrect imported namespaces from causing issues

# [2.0.2] = 29 September 2022
### Fixed
- Detect imported classes in a filename manually using a simple regex
- Swap return codes in CLI commands because 0 means ok 

# [2.0.1] = 28 September 2022
### Fixed
- Fixed PHP 7.4 compat issue in Message/Message.php

# [2.0.0] = 28 September 2022
### Removed
- Huge rewrite of entire logic

### Added
- JSON format to CLI output
- Suggest version number if set to wildcard

### Fixed
- Make sure invalid FQDN doesn't throw PHP Fatal Error

# [1.2.17] = 21 September 2022
### Fixed
- Find composer packages where registration.php is not in root #22

# [1.2.16] = 19 September 2022
### Fixed
- Fix wrong CLI command name

# [1.2.15] = 20 August 2022
### Fixed
- Correctly report source of deprecated class #21

# [1.2.14] = 8 August 2022
### Fixed
- Properly pick up on injected interfaces too
- Support multiple namespace tokens
- Fix token warning on PHP 7.4

# [1.2.13] = 8 August 2022
### Removed
- Moved CLI to https://github.com/yireo/Yireo_ExtensionCheckerCli

# [1.2.12] = 1 August 2022
### Fixed
- Remove non-JSON lines from composer output

# [1.2.11] = 30 July 2022
### Added
- Allow Command to be run without installing Magento #19 (@lbajsarowicz)

### Fixed
- Typo in class inspector line 122 (issue 20)

# [1.2.10] = 10 July 2022
### Removed
- Moved all non-scan related CLI to Yireo_ExtensionValidationTools
- Removed dep with Magento_Store
- Removed `setup_version`
- Dropped support for PHP version 7.3 or lower

# [1.2.9] = 7 July 2022
### Added
- Integration tests
- Added new messages system for feedback from scan back to console
- Added `Component` class for modules, libraries and other package types

### Fixed
- Non-existing module for known module will now throw an error
- Report composer missing for any component missing #16

### Removed
- Moved `Scan/Module` to `Util/ModuleInfo`
- Removed output and input from main class, replaced with messages

# [1.2.8] = 2 June 2022
### Added
- Additional exceptions and debug statements for analysing errors
- Hide warning about missing constants with different PHP versions

# [1.2.7] = 28 May 2022
### Fixed
- Bump version

# [1.2.6] = 25 April 2022
### Fixed
- Make sure packages are properly detected from class names

# [1.2.5] = 25 April 2022
### Fixed 
- Fix bug with namespace (check T_NAME_QUALIFIED)

# [1.2.4] = 25 April 2022
### Added
- Scan for used interfaces too
- Make sure to import FQDN to avoid bugs
- Add GraphQL detection
- Add simple unit tests to safeguard refactoring
- Verbose flag (`-v`) for better debugging

# [1.2.3] = 16 April 2022
### Added
- New command for generate PHPUnit unit tests
- Upgraded deps to allow for PHP 8 compat

# [1.2.2] = 30 November 2020
### Fixed
- Do not disallow wildcard for PHP extensions

# [1.2.1] - 20 November 2020
### Fixed
- Fix class name detection
- Scan for deps with version set to wildcard


# [1.2.0] - 29 July 2020
### Added
- New CLI to check versioning of composer.json file

## [1.1.3] - 2020-07-29
### Added
- Magento 2.4 compat

## [1.1.2] - 2020-02-29
### Added
- PHPCS compliance

## [1.1.1] - 2019-07-11
### Added
- Add an exit code 1 if warnings are found

## [1.1.0] - 2019-06-28
### Added
- This CHANGELOG
- Better checks to see if class is instantiable
- Use preferences to translate interfaces into classes
