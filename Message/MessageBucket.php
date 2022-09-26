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
     * @param string $text
     */
    public function addNotice(string $text)
    {
        $this->messages[] = $this->add($text, Message::TYPE_NOTICE);
    }
    
    /**
     * @param string $text
     */
    public function addWarning(string $text)
    {
        $this->messages[] = $this->add($text, Message::TYPE_WARNING);
    }
    
    /**
     * @param string $text
     */
    public function addDebug(string $text)
    {
        $this->messages[] = $this->add($text, Message::TYPE_DEBUG);
    }
    
    /**
     * @param string $text
     * @param string $type
     */
    private function add(string $text, string $type)
    {
        return $this->objectManager->create(Message::class, ['text' => $text, 'type' => $type]);
    }
}
