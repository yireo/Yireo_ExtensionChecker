<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Util;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Util\ModuleInfo;

class ModuleInfoTest extends TestCase
{
    public function testIsKnown()
    {
        $moduleInfo = ObjectManager::getInstance()->get(ModuleInfo::class);
        $this->assertTrue($moduleInfo->isKnown('Magento_Catalog'));
        $this->assertFalse($moduleInfo->isKnown('Foo_Bar'));
    }
    
    public function testGetModuleFolder()
    {
        $moduleInfo = ObjectManager::getInstance()->get(ModuleInfo::class);
        $this->assertDirectoryExists($moduleInfo->getModuleFolder('Magento_Catalog'));
        
        $this->expectException(ModuleNotFoundException::class);
        $moduleInfo->getModuleFolder('Foo_Bar423432');
    }
    
    public function testGetModuleInfo()
    {
        $moduleInfo = ObjectManager::getInstance()->get(ModuleInfo::class);
        $this->assertNotEmpty($moduleInfo->getModuleInfo('Magento_Catalog'));
    
        $this->expectException(ModuleNotFoundException::class);
        $moduleInfo->getModuleFolder('Foo_Bar423432');
    }
}