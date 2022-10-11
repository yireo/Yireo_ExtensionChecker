<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Message;

use Magento\Framework\ObjectManagerInterface;
use Yireo\ExtensionChecker\Config\RuntimeConfig;

class MessageBucket
{
    /**
     * @var Message[]
     */
    private array $messages = [];
    private ObjectManagerInterface $objectManager;
    private RuntimeConfig $runtimeConfig;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param RuntimeConfig $runtimeConfig
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        RuntimeConfig $runtimeConfig
    ) {
        $this->objectManager = $objectManager;
        $this->runtimeConfig = $runtimeConfig;
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

    /**
     * @param string $message
     * @return void
     */
    public function debug(string $message)
    {
        if ($this->runtimeConfig->isVerbose()) {
            $this->add($message, MessageGroupLabels::GROUP_DEBUG);
        }
    }

    /**
     * @return void
     */
    public function clean()
    {
        $this->messages = [];
    }
}
