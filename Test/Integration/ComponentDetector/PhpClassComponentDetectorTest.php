<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\ComponentDetector;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\ComponentDetector\PhpClassComponentDetector;
use Yireo\ExtensionChecker\Test\Integration\Behaviour\AssertContainsByComponentName;

class PhpClassComponentDetectorTest extends TestCase
{
    use AssertContainsByComponentName;

    public function testGetComponentsByModuleName()
    {
        $componentDetector = ObjectManager::getInstance()->get(PhpClassComponentDetector::class);
        $components = $componentDetector->getComponentsByModuleName('Magento_Catalog');
        $this->assertNotEmpty($components);
        $this->assertContainsByComponentName('Magento_Checkout', $components);
    }

    public function testDevRequirementsForTestClasses()
    {
        $componentDetector = ObjectManager::getInstance()->get(PhpClassComponentDetector::class);
        $components = $componentDetector->getComponentsByModuleName('Yireo_ExtensionChecker');

        $devComponentNames = [];
        $nonDevComponentNames = [];
        foreach ($components as $component) {
            if ($component->isDevRequirement()) {
                $devComponentNames[] = $component->getComponentName();
                continue;
            }

            $nonDevComponentNames[] = $component->getComponentName();
        }

        $this->assertContains('phpunit/phpunit', $devComponentNames);
        $this->assertNotContains('phpunit/phpunit', $nonDevComponentNames);
        $this->assertContains('magento/framework', $nonDevComponentNames);
    }
}
