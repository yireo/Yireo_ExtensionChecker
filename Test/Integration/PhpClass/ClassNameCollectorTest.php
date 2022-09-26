<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Exception\UnreadableFileException;
use Yireo\ExtensionChecker\PhpClass\ClassNameCollector;

class ClassNameCollectorTest extends TestCase
{
    public function testGetClassNameFromFile()
    {
        $classNameCollector = ObjectManager::getInstance()->get(ClassNameCollector::class);
        $this->assertEquals(self::class, $classNameCollector->getClassNameFromFile(__FILE__));
        
        $this->expectException(UnreadableFileException::class);
        $classNameCollector->getClassNameFromFile('test.php');
    }
    
    public function testGetClassNamesFromFiles()
    {
        $classNameCollector = ObjectManager::getInstance()->get(ClassNameCollector::class);
        $this->assertContains(self::class, $classNameCollector->getClassNamesFromFiles([__FILE__]));
        
        $this->expectException(UnreadableFileException::class);
        $classNameCollector->getClassNameFromFile('test.php');
    }
}
