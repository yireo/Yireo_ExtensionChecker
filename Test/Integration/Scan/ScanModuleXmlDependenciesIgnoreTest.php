<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Scan;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Message\Message;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Scan\ScanModuleXmlDependencies;

class ScanModuleXmlDependenciesIgnoreTest extends TestCase
{
    private array $folders = [];

    public function testIgnoreListIsAppliedPerModule()
    {
        $suffix = uniqid();
        $firstModule = 'YireoTest_First' . $suffix;
        $secondModule = 'YireoTest_Second' . $suffix;
        $this->registerModule($firstModule);
        $this->registerModule($secondModule, '{"ignore":["Magento_Catalog","magento/module-catalog"]}');

        $messageBucket = ObjectManager::getInstance()->get(MessageBucket::class);
        $messageBucket->clean();

        $scan = ObjectManager::getInstance()->create(ScanModuleXmlDependencies::class);
        $components = [new Component('Magento_Catalog', 'module', 'magento/module-catalog', '', true)];
        $scan->scan($firstModule, $components);
        $scan->scan($secondModule, $components);

        $modules = array_map(fn (Message $message) => $message->getModule(), $messageBucket->getMessages());
        $messageBucket->clean();

        $this->assertContains($firstModule, $modules);
        $this->assertNotContains($secondModule, $modules);
    }

    private function registerModule(string $moduleName, string $configuration = ''): void
    {
        $folder = sys_get_temp_dir() . '/' . $moduleName;
        mkdir($folder . '/etc', 0777, true);
        $this->folders[] = $folder;

        file_put_contents(
            $folder . '/etc/module.xml',
            '<?xml version="1.0"?><config><module name="' . $moduleName . '"/></config>'
        );

        if ($configuration !== '') {
            file_put_contents($folder . '/.yireo-extension-checker.json', $configuration);
        }

        ComponentRegistrar::register(ComponentRegistrar::MODULE, $moduleName, $folder);
    }

    protected function tearDown(): void
    {
        foreach ($this->folders as $folder) {
            @unlink($folder . '/.yireo-extension-checker.json');
            @unlink($folder . '/etc/module.xml');
            @rmdir($folder . '/etc');
            @rmdir($folder);
        }

        parent::tearDown();
    }
}
