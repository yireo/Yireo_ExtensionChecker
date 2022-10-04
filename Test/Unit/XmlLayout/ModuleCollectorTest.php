<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\XmlLayout;

use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\XmlLayout\ModuleCollector;
use Magento\Framework\Filesystem\File\Read as FileRead;
use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;

class ModuleCollectorTest extends TestCase
{
    public function testGetModulesFromFile()
    {
        $fileContents = <<<EOF
<block template="Magento_Catalog::sample.phtml" /><block template="Magento_Checkout::sample.phtml" />
<block template="Magento_Catalog::example.phtml" />
EOF;
        $fileRead = $this->getMockBuilder(FileRead::class)->disableOriginalConstructor()->getMock();
        $fileRead->method('readAll')->willReturn($fileContents);

        $fileReadFactory = $this->getMockBuilder(FileReadFactory::class)->disableOriginalConstructor()->getMock();
        $fileReadFactory->method('create')->willReturn($fileRead);
        $moduleCollector = new ModuleCollector($fileReadFactory);
        $modules = $moduleCollector->getModulesFromFile('foobar.xml');
        $this->assertContains('Magento_Catalog', $modules);
        $this->assertContains('Magento_Checkout', $modules);
    }
}