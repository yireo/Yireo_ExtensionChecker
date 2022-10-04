<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

//use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\PhpClass\Tokenizer;

class TokenizerTest extends TestCase
{
    public function testGetStringTokensFromFilename()
    {
        $tokenizer = ObjectManager::getInstance()->get(Tokenizer::class);
        $stringTokens = $tokenizer->getStringTokensFromFilename(__FILE__);
        $this->assertNotEmpty($stringTokens);
        $this->assertContains('strict_types', $stringTokens, var_export($stringTokens, true));
        $this->assertContains('TokenizerTest', $stringTokens, var_export($stringTokens, true));
        $this->assertContains('TestCase', $stringTokens, var_export($stringTokens, true));
        $this->assertContains('getInstance', $stringTokens, var_export($stringTokens, true));
        $this->assertContains('assertContains', $stringTokens, var_export($stringTokens, true));
    }

    public function testGetImportedClassnamesFromSource()
    {
        $tokenizer = ObjectManager::getInstance()->get(Tokenizer::class);
        $classNames = $tokenizer->getImportedClassnamesFromFile(__FILE__);
        $this->assertNotEmpty($classNames);
        $this->assertContains(Tokenizer::class, $classNames);
        $this->assertContains(ObjectManager::class, $classNames);
        $this->assertContains(TestCase::class, $classNames);
    }
}
