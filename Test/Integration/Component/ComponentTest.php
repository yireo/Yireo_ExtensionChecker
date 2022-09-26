<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Component;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;

class ComponentTest extends TestCase
{
    public function testComponent()
    {
        $arguments = [
            'componentName' => 'Yireo_Example',
            'componentType' => 'module',
            'packageName' => 'yireo/example',
            'packageVersion' => '0.0.1'
        ];
        $component = ObjectManager::getInstance()->create(Component::class, $arguments);
        $this->assertEquals('Yireo_Example', $component->getComponentName());
        $this->assertEquals('module', $component->getComponentType());
        $this->assertEquals('yireo/example', $component->getPackageName());
        $this->assertEquals('0.0.1', $component->getPackageVersion());
        $this->assertEquals('Yireo_Example',(string)$component);
    }
}
