# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
