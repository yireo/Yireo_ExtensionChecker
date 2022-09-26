<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
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
        $this->assertContains(SearchCriteriaBuilder::class, $dependencies, var_export($dependencies, true));
    }
    
    public function testIsDeprecated()
    {
        $classInspector = ObjectManager::getInstance()->get(ClassInspector::class);
        $classInspector->setClassName(Registry::class);
        $this->assertTrue($classInspector->isDeprecated());
    
        $classInspector->setClassName(ProductRepositoryInterface::class);
        $this->assertFalse($classInspector->isDeprecated());
    }
    
    public function testGetComponentByClass()
    {
        $classInspector = ObjectManager::getInstance()->get(ClassInspector::class);
        $classInspector->setClassName(ProductRepositoryInterface::class);
        $component = $classInspector->getComponentByClass();
        $this->assertEquals('Magento_Catalog', $component->getComponentName());
        $this->assertEquals('module', $component->getComponentType());
        
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
    
    public function testGetStringTokensFromFilename()
    {
        $classInspector = ObjectManager::getInstance()->get(ClassInspector::class);
        $classInspector->setClassName(self::class);
        $stringTokens = $classInspector->getStringTokensFromFilename();
        $this->assertNotEmpty($stringTokens);
        $this->assertContains('strict_types', $stringTokens);
        $this->assertContains('ClassInspectorTest', $stringTokens);
        $this->assertContains('ObjectManager', $stringTokens);
        $this->assertContains('assertContains', $stringTokens);
    }
}
