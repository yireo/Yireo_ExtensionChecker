<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use InvalidArgumentException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Shell;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Composer\ComposerFile;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Console\Command\CheckMagentoVersionCommand;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class CheckMagentoVersionCommandTest extends TestCase
{
    use TemporaryFolder;

    private const SHELL_COMMAND
        = 'composer show magento/product-community-edition %s --format=json -a --no-plugins --no-scripts';

    /**
     * @dataProvider getInvalidMagentoVersions
     */
    #[DataProvider('getInvalidMagentoVersions')]
    public function testExecuteRejectsInvalidMagentoVersion(string $magentoVersion)
    {
        $shell = $this->createMock(Shell::class);
        $shell->expects($this->never())->method('execute');

        $commandTester = new CommandTester($this->getCommand($this->createTemporaryFolder(), $shell));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid Magento version');
        $commandTester->execute(['--module' => 'Foo_Bar', '--magento-version' => $magentoVersion]);
    }

    public static function getInvalidMagentoVersions(): array
    {
        return [
            ['2.4.7; id'],
            ['2.4.7/../x'],
            ['x2.4.7'],
            ['2.4.7 && id'],
            ["2.4.7\nid"],
            ["2.4.7\n"],
            ['2.4'],
            [''],
        ];
    }

    /**
     * @dataProvider getValidMagentoVersions
     */
    #[DataProvider('getValidMagentoVersions')]
    public function testExecutePassesMagentoVersionAsShellArgument(string $magentoVersion)
    {
        $rootFolder = $this->createTemporaryFolder();
        $shell = $this->createMock(Shell::class);
        $shell->expects($this->once())
            ->method('execute')
            ->with(self::SHELL_COMMAND, [$magentoVersion])
            ->willReturn((string)json_encode(['requires' => ['foo/bar' => '1.0.0']]));

        $commandTester = new CommandTester($this->getCommand($rootFolder, $shell));
        $exitCode = $commandTester->execute(['--module' => 'Foo_Bar', '--magento-version' => $magentoVersion]);

        $this->assertSame(0, $exitCode);
        $cacheFile = $rootFolder . '/var/composer_home/magento-product-community-edition-' . $magentoVersion . '.json';
        $this->assertFileExists($cacheFile);
        $this->assertSame(['requires' => ['foo/bar' => '1.0.0']], json_decode(file_get_contents($cacheFile), true));
    }

    public static function getValidMagentoVersions(): array
    {
        return [
            ['2.4.7'],
            ['2.4.7-p3'],
        ];
    }

    public function testExecuteUsesCacheFile()
    {
        $rootFolder = $this->createTemporaryFolder([
            'var/composer_home/magento-product-community-edition-2.4.7.json' => json_encode([
                'requires' => ['foo/bar' => '1.0.0'],
            ]),
        ]);

        $shell = $this->createMock(Shell::class);
        $shell->expects($this->never())->method('execute');

        $commandTester = new CommandTester($this->getCommand($rootFolder, $shell));
        $this->assertSame(0, $commandTester->execute(['--module' => 'Foo_Bar', '--magento-version' => '2.4.7']));
    }

    public function testExecuteDoesNotCacheInvalidOutput()
    {
        $rootFolder = $this->createTemporaryFolder();
        $shell = $this->createMock(Shell::class);
        $shell->method('execute')->willReturn('not json');

        $commandTester = new CommandTester($this->getCommand($rootFolder, $shell));

        try {
            $commandTester->execute(['--module' => 'Foo_Bar', '--magento-version' => '2.4.7']);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $exception) {
            $this->assertFileDoesNotExist(
                $rootFolder . '/var/composer_home/magento-product-community-edition-2.4.7.json'
            );
        }
    }

    public function testExecuteWarnsWhenRequirementIsNotSatisfied()
    {
        $shell = $this->createMock(Shell::class);
        $shell->method('execute')->willReturn((string)json_encode(['requires' => ['foo/bar' => '2.0.0']]));

        $commandTester = new CommandTester($this->getCommand($this->createTemporaryFolder(), $shell));
        $exitCode = $commandTester->execute(['--module' => 'Foo_Bar', '--magento-version' => '2.4.7']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Not satisfied', $commandTester->getDisplay());
    }

    private function getCommand(string $rootFolder, Shell $shell): CheckMagentoVersionCommand
    {
        $composerFile = $this->createMock(ComposerFile::class);
        $composerFile->method('getRequirements')->willReturn(['foo/bar' => '^1.0']);

        $composerFileProvider = $this->createMock(ComposerFileProvider::class);
        $composerFileProvider->method('getComposerFileByModuleName')->willReturn($composerFile);

        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn($rootFolder);

        $command = new CheckMagentoVersionCommand($composerFileProvider, $directoryList, $shell, new FileDriver());
        $application = new Application();
        $application->add($command);

        return $command;
    }
}
