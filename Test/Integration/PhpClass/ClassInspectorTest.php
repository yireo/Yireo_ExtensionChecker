<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\PhpClass;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\PhpClass\ClassInspector;

class ClassInspectorTest extends TestCase
{
    public function testGetDependencies()
    {
        $classInspector = ObjectManager::getInstance()->get(ClassInspector::class);
        $classInspector->setClassName(ProductRepository::class);
        $dependencies = $classInspector->getDependencies();
        $this->assertNotEmpty($dependencies);
        $this->assertContains(SearchCriteriaInterface::class, $dependencies, var_export($dependencies, true));
    }

    public function testIsDeprecated()
    {
        $classInspector = ObjectManager::getInstance()->get(ClassInspector::class);
        $classInspector->setClassName(Registry::class);
        $this->assertTrue($classInspector->isDeprecated());

        $classInspector->setClassName(ProductRepositoryInterface::class);
        $this->assertFalse($classInspector->isDeprecated());
    }

    /**
     * @param string $className
     * @param string $componentName
     * @return void
     * @throws FileSystemException
     * @throws NotFoundException
     * @throws \ReflectionException
     * @dataProvider getComponentByClassProvider
     */
    public function testGetComponentByClass(string $className, string $componentName)
    {
        $classInspector = ObjectManager::getInstance()->get(ClassInspector::class);
        $component = $classInspector->setClassName($className)->getComponentByClass();
        $this->assertEquals($componentName, $component->getComponentName());

        $classInspector->setClassName(UrlInterface::class);
        $component = $classInspector->getComponentByClass();
        $this->assertEquals('magento/framework', $component->getComponentName());
        $this->assertEquals('library', $component->getComponentType());
    }

    public function testGetPackageByClass()
    {
        $classInspector = ObjectManager::getInstance()->get(ClassInspector::class);
        $classInspector->setClassName(Registry::class);
        $this->assertEquals('magento/framework', $classInspector->getPackageByClass());

        $classInspector->setClassName(ProductRepositoryInterface::class);
        $this->assertEquals('magento/module-catalog', $classInspector->getPackageByClass());
    }

    public function getComponentByClassProvider(): array
    {
        return [
            [PackageInfo::class, 'magento/framework'],
            [CustomerRepositoryInterface::class, 'Magento_Customer'],
            [ClassInspectorTest::class, 'Yireo_ExtensionChecker'],
        ];
    }
}
