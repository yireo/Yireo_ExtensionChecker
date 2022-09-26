<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Message;

use Magento\Framework\ObjectManagerInterface;

class MessageBucket
{
    const GROUP_EXCEPTION = 'GROUP_EXCEPTION';
    const GROUP_MISSING_COMPOSER_DEP = 'GROUP_MISSING_COMPOSER_DEP';
    const GROUP_UNNECESSARY_COMPOSER_DEP = 'GROUP_UNNECESSARY_COMPOSER_DEP';
    const GROUP_MISSING_MODULEXML_DEP = 'GROUP_MISSING_MODULEXML_DEP';
    const GROUP_UNNECESSARY_MODULEXML_DEP = 'GROUP_UNNECESSARY_MODULEXML_DEP';
    const GROUP_WILDCARD_VERSION = 'GROUP_WILDCARD_VERSION';
    const GROUP_UNMET_REQUIREMENT = 'GROUP_UNMET_REQUIREMENT';
    const GROUP_PHP_DEPRECATED = 'GROUP_PHP_DEPRECATED';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Message[]
     */
    private $messages = [];

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param string $message
     * @param string $group
     * @param string $suggestion
     */
    public function add(string $message, string $group, string $suggestion = '')
    {
        $this->messages[] = $this->objectManager->create(Message::class, [
            'message' => $message,
            'group' => $group,
            'suggestion' => $suggestion
        ]);
    }

    static public function getGroupLabels(): array
    {
        return [
            self::GROUP_EXCEPTION => 'Exception',
            self::GROUP_MISSING_COMPOSER_DEP => 'Missing composer dependency',
            self::GROUP_UNNECESSARY_COMPOSER_DEP => 'Unnecessary composer dependency',
            self::GROUP_MISSING_MODULEXML_DEP => 'Missing module.xml dependency',
            self::GROUP_UNNECESSARY_MODULEXML_DEP => 'Unnecessary module.xml dependency',
            self::GROUP_WILDCARD_VERSION => 'Wild card version',
            self::GROUP_UNMET_REQUIREMENT => 'Unmet requirement',
            self::GROUP_PHP_DEPRECATED => 'Deprecated PHP code',
        ];
    }
}
