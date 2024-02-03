<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\ComponentDetector;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\ComponentDetector\ModuleXmlComponentDetector;
use Yireo\ExtensionChecker\Test\Integration\Behaviour\AssertContainsByComponentName;

class ModuleXmlComponentDetectorTest extends TestCase
{
    use AssertContainsByComponentName;

    public function testGetComponentsByModuleName()
    {
        $componentDetector = ObjectManager::getInstance()->get(ModuleXmlComponentDetector::class);
        $components = $componentDetector->getComponentsByModuleName('Magento_Catalog');
        $this->assertNotEmpty($components, 'No components detected');
        $dump = var_export($components, true);
        $this->assertContainsByComponentName('Magento_Eav', $components, $dump);
    }
}
