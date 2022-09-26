<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Message\Message;
use Yireo\ExtensionChecker\Message\MessageFactory;

class MessageTest extends TestCase
{
    public function testCreateNotice()
    {
        $arguments = ['text' => 'Hello World', 'type' => Message::TYPE_NOTICE];
        $message = ObjectManager::getInstance()->create(Message::class, $arguments);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Hello World', $message->getText());
        $this->assertEquals(Message::TYPE_NOTICE, $message->getType());
        $this->assertTrue($message->isNotice());
        $this->assertFalse($message->isWarning());
        $this->assertFalse($message->isDebug());
    }
    
    public function testCreateWarning()
    {
        $arguments = ['text' => 'Hello World', 'type' => Message::TYPE_WARNING];
        $message = ObjectManager::getInstance()->create(Message::class, $arguments);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Hello World', $message->getText());
        $this->assertEquals(Message::TYPE_WARNING, $message->getType());
        $this->assertFalse($message->isNotice());
        $this->assertTrue($message->isWarning());
        $this->assertFalse($message->isDebug());
    }
    
    public function testCreateDebug()
    {
        $arguments = ['text' => 'Hello World', 'type' => Message::TYPE_DEBUG];
        $message = ObjectManager::getInstance()->create(Message::class, $arguments);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Hello World', $message->getText());
        $this->assertEquals(Message::TYPE_DEBUG, $message->getType());
        $this->assertFalse($message->isNotice());
        $this->assertFalse($message->isWarning());
        $this->assertTrue($message->isDebug());
    }
}
