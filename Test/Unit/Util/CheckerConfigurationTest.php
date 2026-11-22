<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Util;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
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
}
