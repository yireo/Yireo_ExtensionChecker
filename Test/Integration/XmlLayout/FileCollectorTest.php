<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Util\ModuleInfo;

class FileCollectorTest extends TestCase
{
    public function testGetFilesFromModuleFolder()
    {
        $moduleInfo = ObjectManager::getInstance()->get(ModuleInfo::class);
        $fileCollector = ObjectManager::getInstance()->get('Yireo\ExtensionChecker\Virtual\LayoutFileCollector');
        $files = $fileCollector->getFilesFromModuleFolder($moduleInfo->getModuleFolder('Magento_Catalog'));
        $this->assertNotEmpty($files);

        $files = $fileCollector->getFilesFromModuleFolder($moduleInfo->getModuleFolder('Yireo_ExtensionChecker'));
        $this->assertEmpty($files);
    }
}