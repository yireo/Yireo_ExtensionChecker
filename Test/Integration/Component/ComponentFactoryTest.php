<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Component;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\Test\Integration\Behaviour\InstalledCorePackage;

class ComponentFactoryTest extends TestCase
{
    /**
     * @return void
     * @dataProvider createByModuleNameProvider
     */
    public function testCreateByModuleName(string $moduleName, string $packageName)
    {
        $componentFactory = ObjectManager::getInstance()->get(ComponentFactory::class);
        $component = $componentFactory->createByModuleName($moduleName);
        $this->assertEquals($moduleName, $component->getComponentName());
        $this->assertEquals(ComponentRegistrar::MODULE, $component->getComponentType());
        $this->assertEquals($packageName, $component->getPackageName());
    }

    public static function createByModuleNameProvider(): array
    {
        return [
            ['Magento_Sales', InstalledCorePackage::getName('module-sales')],
            ['Magento_Customer', InstalledCorePackage::getName('module-customer')],
            ['Yireo_ExtensionChecker', 'yireo/magento2-extensionchecker'],
        ];
    }

    /**
     * @return void
     * @dataProvider createByFrameworkProvider
     */
    public function testCreateByFramework(string $frameworkPackageName)
    {
        $componentFactory = ObjectManager::getInstance()->get(ComponentFactory::class);
        $component = $componentFactory->createByLibraryName($frameworkPackageName);
        $this->assertEquals($frameworkPackageName, $component->getComponentName());
        $this->assertEquals(ComponentRegistrar::LIBRARY, $component->getComponentType());
        $this->assertEquals($frameworkPackageName, $component->getPackageName());
    }

    public static function createByFrameworkProvider(): array
    {
        return [
            [InstalledCorePackage::getName('framework')],
            ['league/flysystem'],
            ['phpunit/phpunit'],
        ];
    }
}
