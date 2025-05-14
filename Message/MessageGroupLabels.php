<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Message;

class MessageGroupLabels
{
    const GROUP_DEBUG = 'GROUP_DEBUG';
    const GROUP_EXCEPTION = 'GROUP_EXCEPTION';
    const GROUP_MISSING_COMPOSER_DEP = 'GROUP_MISSING_COMPOSER_DEP';
    const GROUP_UNNECESSARY_COMPOSER_DEP = 'GROUP_UNNECESSARY_COMPOSER_DEP';
    const GROUP_MISSING_MODULEXML_DEP = 'GROUP_MISSING_MODULEXML_DEP';
    const GROUP_UNNECESSARY_MODULEXML_DEP = 'GROUP_UNNECESSARY_MODULEXML_DEP';
    const GROUP_WILDCARD_VERSION = 'GROUP_WILDCARD_VERSION';
    const GROUP_UNMET_REQUIREMENT = 'GROUP_UNMET_REQUIREMENT';
    const GROUP_PHP_DEPRECATED = 'GROUP_PHP_DEPRECATED';
    const GROUP_COMPOSER_ISSUES = 'GROUP_COMPOSER_ISSUES';

    public function get(): array
    {
        return [
            self::GROUP_DEBUG => 'Debug',
            self::GROUP_EXCEPTION => 'Exception',
            self::GROUP_MISSING_COMPOSER_DEP => 'Missing composer dependency',
            self::GROUP_UNNECESSARY_COMPOSER_DEP => 'Unnecessary composer dependency',
            self::GROUP_MISSING_MODULEXML_DEP => 'Missing module.xml dependency',
            self::GROUP_UNNECESSARY_MODULEXML_DEP => 'Unnecessary module.xml dependency',
            self::GROUP_WILDCARD_VERSION => 'Wild card version',
            self::GROUP_UNMET_REQUIREMENT => 'Unmet requirement',
            self::GROUP_PHP_DEPRECATED => 'Deprecated PHP code',
            self::GROUP_COMPOSER_ISSUES => 'Composer issues',
        ];
    }
}
