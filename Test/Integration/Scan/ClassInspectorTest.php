<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Scan;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\PackageInfo;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\PhpClass\ClassInspector;

class ClassInspectorTest extends TestCase
{
    /**
     * @param string $className
     * @param string $componentName
     * @return void
     * @dataProvider getComponentByClassProvider
     */
    public function testGetComponentByClass(string $className, string $componentName)
    {
        $classInspector = ObjectManager::getInstance()->get(ClassInspector::class);
        $component = $classInspector->setClassName($className)->getComponentByClass();
        $this->assertEquals($componentName, $component->getComponentName());
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