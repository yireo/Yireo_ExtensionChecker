<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Scan;

use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Composer\ComposerFile;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Composer\ComposerProvider;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Scan\ScanComposerRequirements;
use Yireo\ExtensionChecker\Util\CheckerConfiguration;

class ScanComposerRequirementsTest extends TestCase
{
    private array $messages = [];

    /**
     * @dataProvider alternativePackageNamesProvider
     */
    public function testMissingDependencyNotReportedWhenAlternativeIsRequired(string $required, string $detected)
    {
        $this->scan([$required => '^104.0'], [
            new Component('Magento_Catalog', 'module', $detected, '104.0.0', true),
        ]);

        $this->assertNotContains(MessageGroupLabels::GROUP_MISSING_COMPOSER_DEP, $this->getMessageGroups());
        $this->assertNotContains(MessageGroupLabels::GROUP_UNNECESSARY_COMPOSER_DEP, $this->getMessageGroups());
    }

    public static function alternativePackageNamesProvider(): array
    {
        return [
            ['mage-os/module-catalog', 'magento/module-catalog'],
            ['magento/module-catalog', 'mage-os/module-catalog'],
        ];
    }

    public function testMissingDependencyIsReported()
    {
        $this->scan([], [
            new Component('Magento_Catalog', 'module', 'magento/module-catalog', '104.0.0', true),
        ]);

        $this->assertContains(MessageGroupLabels::GROUP_MISSING_COMPOSER_DEP, $this->getMessageGroups());
    }

    public function testSoftRequirementIsNotReported()
    {
        $this->scan([], [
            new Component('Magento_Catalog', 'module', 'magento/module-catalog', '104.0.0'),
        ]);

        $this->assertNotContains(MessageGroupLabels::GROUP_MISSING_COMPOSER_DEP, $this->getMessageGroups());
    }

    public function testUnnecessaryDependencyIsReported()
    {
        $this->scan(['mage-os/module-catalog' => '^104.0'], []);

        $this->assertContains(MessageGroupLabels::GROUP_UNNECESSARY_COMPOSER_DEP, $this->getMessageGroups());
    }

    private function scan(array $requirements, array $components): void
    {
        $this->messages = [];

        $composerFile = $this->createMock(ComposerFile::class);
        $composerFile->method('getRequirements')->willReturn($requirements);

        $composerFileProvider = $this->createMock(ComposerFileProvider::class);
        $composerFileProvider->method('getComposerFileByModuleName')->willReturn($composerFile);

        $messageBucket = $this->createMock(MessageBucket::class);
        $messageBucket->method('add')->willReturnCallback(function (string $message, string $group) {
            $this->messages[] = ['message' => $message, 'group' => $group];
        });

        $checkerConfiguration = $this->createMock(CheckerConfiguration::class);
        $checkerConfiguration->method('isIgnored')->willReturn(false);
        $checkerConfiguration->method('isComponentIgnored')->willReturn(false);

        $scanComposerRequirements = new ScanComposerRequirements(
            $composerFileProvider,
            $messageBucket,
            $this->createMock(ComposerProvider::class),
            new RuntimeConfig(),
            $checkerConfiguration
        );

        $scanComposerRequirements->scan('Foo_Bar', $components);
    }

    private function getMessageGroups(): array
    {
        return array_column($this->messages, 'group');
    }
}
