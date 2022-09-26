<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\ComponentDetector;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\ComponentDetector\PhpClassComponentDetector;

class PhpClassComponentDetectorTest extends TestCase
{
    public function testGetComponentsByModuleName()
    {
        $componentDetector = ObjectManager::getInstance()->get(PhpClassComponentDetector::class);
        $components = $componentDetector->getComponentsByModuleName('Magento_Catalog');
        $this->assertNotEmpty($components);
        $this->assertContainsByComponentName('Magento_Checkout', $components);
    }
    
    /**
     * @param string $componentName
     * @param Component[] $components
     * @return void
     */
    private function assertContainsByComponentName(string $componentName, array $components = [])
    {
        $componentFound = false;
        foreach ($components as $component) {
            if ($component->getComponentName() === $componentName) {
                $componentFound = true;
                break;
            }
        }
        
        $this->assertTrue($componentFound);
    }
}