<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Message\Message;

class MessageTest extends TestCase
{
    public function testCreateMessage()
    {
        $arguments = ['message' => 'Hello Message', 'group' => 'Hello Group', 'suggestion' => 'Hello Suggestion'];
        $message = ObjectManager::getInstance()->create(Message::class, $arguments);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Hello Message', $message->getMessage());
        $this->assertEquals('Hello Group', $message->getGroup());
        $this->assertEquals('Hello Suggestion', $message->getSuggestion());
    }
}
