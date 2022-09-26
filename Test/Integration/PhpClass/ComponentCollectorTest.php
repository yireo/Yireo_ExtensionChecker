<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\PhpClass\ComponentCollector;

class ComponentCollectorTest extends TestCase
{
    public function testGetComponentsByClasses()
    {
        $componentCollector = ObjectManager::getInstance()->get(ComponentCollector::class);
        $classes = [ProductInterface::class];
        $components = $componentCollector->getComponentsByClasses($classes);
        $this->assertComponentsContainModuleName('Magento_Catalog', $components);
    }
    
    private function assertComponentsContainModuleName(string $moduleName, array $components)
    {
        $this->assertNotEmpty(array_filter($components, fn(Component $component) =>
            $component->getComponentName() === $moduleName)
        );
    }
}
