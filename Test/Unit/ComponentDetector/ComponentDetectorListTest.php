<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\ComponentDetector;

use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorInterface;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Config\RuntimeConfig;

class ComponentDetectorListTest extends TestCase
{
    public function testDevComponentWithSamePackageAsNonDevComponentIsRemoved()
    {
        $nonDev = new Component('Magento_Store', 'module', 'magento/module-store', '101.0.0', true);
        $dev = new Component('Magento\Store', 'library', 'Magento/Module-Store', '101.0.0', true, true);
        $otherDev = new Component('PHPUnit', 'library', 'phpunit/phpunit', '10.5.1', true, true);

        $componentDetector = $this->createMock(ComponentDetectorInterface::class);
        $componentDetector->method('getComponentsByModuleName')->willReturn([$nonDev, $dev, $otherDev]);

        $componentDetectorList = new ComponentDetectorList(
            $this->createMock(RuntimeConfig::class),
            [$componentDetector]
        );

        $components = array_values($componentDetectorList->getComponentsByModuleName('Foo_Bar'));
        $this->assertSame([$nonDev, $otherDev], $components);
    }
}
