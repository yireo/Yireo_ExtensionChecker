<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Message;

use Magento\Framework\ObjectManagerInterface;

class MessageBucket
{
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

    /**
     * @return void
     */
    public function clean()
    {
        $this->messages = [];
    }
}
