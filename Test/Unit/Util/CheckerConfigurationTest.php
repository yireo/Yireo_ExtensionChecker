<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Util;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;
use Yireo\ExtensionChecker\Util\CheckerConfiguration;

class CheckerConfigurationTest extends TestCase
{
    use TemporaryFolder;

    public function testGetIgnoredComponentsCachesEachModuleSeparately()
    {
        $modulePaths = [
            'Vendor_First' => $this->createTemporaryFolder(),
            'Vendor_Second' => $this->createTemporaryFolder([
                '.yireo-extension-checker.json' => '{"ignore":["TddWizard_Fixtures","tddwizard/magento2-fixtures"]}',
            ]),
            'Vendor_Third' => $this->createTemporaryFolder([
                '.yireo-extension-checker.json' => '{"ignore":["Vendor_Other"]}',
            ]),
        ];
        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->expects($this->exactly(3))
            ->method('getPath')
            ->willReturnMap([
                [ComponentRegistrar::MODULE, 'Vendor_First', $modulePaths['Vendor_First']],
                [ComponentRegistrar::MODULE, 'Vendor_Second', $modulePaths['Vendor_Second']],
                [ComponentRegistrar::MODULE, 'Vendor_Third', $modulePaths['Vendor_Third']],
            ]);
        $configuration = new CheckerConfiguration($componentRegistrar);

        $this->assertSame([], $configuration->getIgnoredComponents('Vendor_First'));
        $this->assertSame(
            ['TddWizard_Fixtures', 'tddwizard/magento2-fixtures'],
            $configuration->getIgnoredComponents('Vendor_Second')
        );
        $this->assertSame(['Vendor_Other'], $configuration->getIgnoredComponents('Vendor_Third'));
        $this->assertSame([], $configuration->getIgnoredComponents('Vendor_First'));
        $this->assertSame(
            ['TddWizard_Fixtures', 'tddwizard/magento2-fixtures'],
            $configuration->getIgnoredComponents('Vendor_Second')
        );
        $this->assertSame(['Vendor_Other'], $configuration->getIgnoredComponents('Vendor_Third'));
    }

    public function testGetIgnoredComponentsWithInvalidConfiguration()
    {
        $configuration = $this->getConfiguration([
            'Vendor_Invalid' => ['.yireo-extension-checker.json' => '{"ignore":'],
            'Vendor_Scalar' => ['.yireo-extension-checker.json' => '"Magento_Catalog"'],
            'Vendor_NoIgnore' => ['.yireo-extension-checker.json' => '{"foo":"bar"}'],
            'Vendor_ScalarIgnore' => ['.yireo-extension-checker.json' => '{"ignore":"Magento_Catalog"}'],
        ]);

        $this->assertSame([], $configuration->getIgnoredComponents('Vendor_Invalid'));
        $this->assertSame([], $configuration->getIgnoredComponents('Vendor_Scalar'));
        $this->assertSame([], $configuration->getIgnoredComponents('Vendor_NoIgnore'));
        $this->assertSame([], $configuration->getIgnoredComponents('Vendor_ScalarIgnore'));
    }

    public function testIsIgnoredAndIsComponentIgnoredPerModule()
    {
        $configuration = $this->getConfiguration([
            'Vendor_First' => [],
            'Vendor_Second' => ['.yireo-extension-checker.json' => '{"ignore":["Magento_Catalog","tddwizard/magento2-fixtures"]}'],
        ]);

        $catalog = new Component('Magento_Catalog', 'module', 'magento/module-catalog');
        $fixtures = new Component('TddWizard_Fixtures', 'module', 'tddwizard/magento2-fixtures');

        $this->assertFalse($configuration->isIgnored('Vendor_First', 'Magento_Catalog'));
        $this->assertFalse($configuration->isComponentIgnored('Vendor_First', $catalog));
        $this->assertFalse($configuration->isComponentIgnored('Vendor_First', $fixtures));

        $this->assertTrue($configuration->isIgnored('Vendor_Second', 'Magento_Catalog'));
        $this->assertFalse($configuration->isIgnored('Vendor_Second', 'magento/module-catalog'));
        $this->assertTrue($configuration->isComponentIgnored('Vendor_Second', $catalog));
        $this->assertTrue($configuration->isComponentIgnored('Vendor_Second', $fixtures));
    }

    public function testNonStringIgnoreEntriesAreSkipped()
    {
        $configuration = $this->getConfiguration([
            'Vendor_Bool' => ['.yireo-extension-checker.json' => '{"ignore":[true, 0, null, 1.5, ["Magento_Store"], {"a":"b"}]}'],
            'Vendor_Mixed' => ['.yireo-extension-checker.json' => '{"ignore":[true, "Magento_Catalog", 0]}'],
        ]);

        $catalog = new Component('Magento_Catalog', 'module', 'magento/module-catalog');
        $store = new Component('Magento_Store', 'module', 'magento/module-store');

        $this->assertSame([], $configuration->getIgnoredComponents('Vendor_Bool'));
        $this->assertFalse($configuration->isIgnored('Vendor_Bool', 'Magento_Catalog'));
        $this->assertFalse($configuration->isIgnored('Vendor_Bool', '0'));
        $this->assertFalse($configuration->isComponentIgnored('Vendor_Bool', $catalog));
        $this->assertFalse($configuration->isComponentIgnored('Vendor_Bool', $store));

        $this->assertSame(['Magento_Catalog'], $configuration->getIgnoredComponents('Vendor_Mixed'));
        $this->assertTrue($configuration->isIgnored('Vendor_Mixed', 'Magento_Catalog'));
        $this->assertFalse($configuration->isIgnored('Vendor_Mixed', 'Magento_Store'));
        $this->assertTrue($configuration->isComponentIgnored('Vendor_Mixed', $catalog));
        $this->assertFalse($configuration->isComponentIgnored('Vendor_Mixed', $store));
    }

    public function testConfigurationIsNotSharedBetweenInstances()
    {
        $first = $this->getConfiguration(['Vendor_Shared' => []]);
        $this->assertSame([], $first->getIgnoredComponents('Vendor_Shared'));

        $second = $this->getConfiguration([
            'Vendor_Shared' => ['.yireo-extension-checker.json' => '{"ignore":["Magento_Catalog"]}'],
        ]);
        $this->assertSame(['Magento_Catalog'], $second->getIgnoredComponents('Vendor_Shared'));
    }

    /**
     * @param array<string, array<string, string>> $modules
     */
    private function getConfiguration(array $modules): CheckerConfiguration
    {
        $map = [];
        foreach ($modules as $moduleName => $files) {
            $map[] = [ComponentRegistrar::MODULE, $moduleName, $this->createTemporaryFolder($files)];
        }

        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')->willReturnMap($map);

        return new CheckerConfiguration($componentRegistrar);
    }
}
