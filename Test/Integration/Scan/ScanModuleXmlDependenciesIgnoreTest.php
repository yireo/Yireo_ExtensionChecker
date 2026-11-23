<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Scan;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\ComponentDetector\ModuleXmlComponentDetector;
use Yireo\ExtensionChecker\Message\Message;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Scan\ScanModuleXmlDependencies;
use Yireo\ExtensionChecker\Util\CheckerConfiguration;

class ScanModuleXmlDependenciesIgnoreTest extends TestCase
{
    private array $folders = [];

    public function testIgnoreListIsAppliedPerModule()
    {
        $modulePaths = [
            'Vendor_First' => $this->createModuleFolder(),
            'Vendor_Second' => $this->createModuleFolder('{"ignore":["Magento_Catalog","magento/module-catalog"]}'),
        ];

        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')->willReturnMap([
            [ComponentRegistrar::MODULE, 'Vendor_First', $modulePaths['Vendor_First']],
            [ComponentRegistrar::MODULE, 'Vendor_Second', $modulePaths['Vendor_Second']],
        ]);

        $moduleXmlComponentDetector = $this->createMock(ModuleXmlComponentDetector::class);
        $moduleXmlComponentDetector->method('getComponentsByModuleName')->willReturn([]);

        $objectManager = ObjectManager::getInstance();
        $messageBucket = $objectManager->get(MessageBucket::class);
        $messageBucket->clean();

        $scan = $objectManager->create(ScanModuleXmlDependencies::class, [
            'moduleXmlComponentDetector' => $moduleXmlComponentDetector,
            'checkerConfiguration' => new CheckerConfiguration($componentRegistrar),
        ]);

        $components = [new Component('Magento_Catalog', 'module', 'magento/module-catalog', '', true)];
        $scan->scan('Vendor_First', $components);
        $scan->scan('Vendor_Second', $components);

        $modules = array_map(fn (Message $message) => $message->getModule(), $messageBucket->getMessages());
        $messageBucket->clean();

        $this->assertContains('Vendor_First', $modules);
        $this->assertNotContains('Vendor_Second', $modules);
    }

    private function createModuleFolder(string $configuration = ''): string
    {
        $folder = sys_get_temp_dir() . '/yireo-extensionchecker-' . uniqid('', true);
        mkdir($folder, 0777, true);
        $this->folders[] = $folder;

        if ($configuration !== '') {
            file_put_contents($folder . '/.yireo-extension-checker.json', $configuration);
        }

        return $folder;
    }

    protected function tearDown(): void
    {
        foreach ($this->folders as $folder) {
            if (file_exists($folder . '/.yireo-extension-checker.json')) {
                unlink($folder . '/.yireo-extension-checker.json');
            }

            rmdir($folder);
        }

        parent::tearDown();
    }
}
