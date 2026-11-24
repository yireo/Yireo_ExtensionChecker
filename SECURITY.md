# Security Policy

## Security considerations
- Scanning a module loads and executes its PHP code: classes are autoloaded and inspected with Reflection (`PhpClass/ClassInspector.php`, `PhpClass/ComponentCollector.php`), and `PhpClass/ClassInspector/CommandClassDetector.php` builds the Magento CLI application, which instantiates all console commands. Only scan untrusted code in a disposable environment (like a container or CI job).
- Install this extension as a development dependency only: `composer require --dev yireo/magento2-extensionchecker`.
- The `autofix` commands write `etc/module.xml` and `composer.json` of the given module. Symbolic links and paths outside of the module folder are refused.
- The symbolic link check of the `autofix` commands (and of the caches in `var/`) is done right before writing, but does not protect against concurrent changes. Make sure that the module folder and the Magento `var/` folder are not writable by less-trusted users.
- The `check-magento-version` command runs `composer show magento/product-community-edition <version>` (with `--no-plugins --no-scripts`) and only accepts versions like `2.4.7` or `2.4.7-p3`.
- The license check of the `scan` command fetches a pinned version of the SPDX license list from GitHub over the network and caches it in `var/yireo_extensionchecker/`. Skip it with `--skip-license-check`.

## Reporting a Vulnerability

If you believe you've found a security vulnerability in this project, we encourage you to let us know.

Please report it by emailing us at [security@yireo.com](mailto:security@yireo.com).
We take security seriously and will respond as quickly as possible.

## Bug Bounty Program

Please note that we do not have a paid bug bounty program at this time. However, we appreciate your efforts in helping us keep our project secure.

## Guidelines for Reporting

When reporting a vulnerability, please include the following information:
- A clear description of the vulnerability.
- Steps to reproduce the issue.
- Any relevant screenshots or logs.
- Your contact information (optional) for follow-up questions.

## Response Timeline

We aim to acknowledge your report within 10 business days. Please note that complexity may affect the time it takes to fix the issue, and there is no fixed deadline for resolutions.

## Disclosure Policy

We will determine public disclosure timelines on a case-by-case basis once an issue has been resolved. Our goal is to balance transparency with user safety.

## Our Commitment

We commit to:
- Acknowledging your report promptly.
- Investigating all reported vulnerabilities thoroughly.
- Keeping you updated on our progress towards fixing the issue.

Thank you for helping us maintain the security of our project!
