<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Scan;

use Composer\Command\AboutCommand;
use Magento\Framework\App\Cache;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Widget\Block\BlockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\Scan\ClassInspector;
use Yireo\ExtensionChecker\Scan\Tokenizer;
use Yireo\ExtensionChecker\Util\ModuleInfo;

class ClassInspectorTest extends TestCase
{
    /**
     * @param $package
     * @param $className
     * @return void
     * @dataProvider getPackagesAndClasses
     */
    public function testGetPackageByClass($package, $className)
    {
        $tokenizer = $this->getMockBuilder(Tokenizer::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(ConfigInterface::class)->disableOriginalConstructor()->getMock();
        $componentFactory = $this->getMockBuilder(ComponentFactory::class)->disableOriginalConstructor()->getMock();
        $moduleInfo = $this->getMockBuilder(ModuleInfo::class)->disableOriginalConstructor()->getMock();

        $classInspector = new ClassInspector($tokenizer, $config, $componentFactory, $moduleInfo);
        $this->assertEquals($package, $classInspector->setClassName($className)->getPackageByClass());
    }

    /**
     * @return string[][]
     */
    public function getPackagesAndClasses(): array
    {
        return [
            ['psr/log', LoggerInterface::class],
            ['composer/composer', AboutCommand::class],
            ['magento/framework', Cache::class],
            ['magento/module-widget', BlockInterface::class]
        ];
    }
}