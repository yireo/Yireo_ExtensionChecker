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
use Yireo\ExtensionChecker\Util\CacheFile;

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

    public function testExecuteIgnoresSymlinkedCacheFile()
    {
        $outsideFile = $this->createTemporaryFolder(['outside.json' => '{"requires":{}}']) . '/outside.json';
        $rootFolder = $this->createTemporaryFolder();
        mkdir($rootFolder . '/var/composer_home', 0777, true);
        symlink($outsideFile, $rootFolder . '/var/composer_home/magento-product-community-edition-2.4.7.json');

        $shell = $this->createMock(Shell::class);
        $shell->expects($this->once())
            ->method('execute')
            ->willReturn((string)json_encode(['requires' => ['foo/bar' => '1.0.0']]));

        $commandTester = new CommandTester($this->getCommand($rootFolder, $shell));
        $this->assertSame(0, $commandTester->execute(['--module' => 'Foo_Bar', '--magento-version' => '2.4.7']));
        $this->assertSame('{"requires":{}}', file_get_contents($outsideFile));
    }

    public function testExecuteIgnoresSymlinkedCacheFolder()
    {
        $outsideFolder = $this->createTemporaryFolder();
        $rootFolder = $this->createTemporaryFolder();
        mkdir($rootFolder . '/var');
        symlink($outsideFolder, $rootFolder . '/var/composer_home');

        $shell = $this->createMock(Shell::class);
        $shell->expects($this->once())
            ->method('execute')
            ->willReturn((string)json_encode(['requires' => ['foo/bar' => '1.0.0']]));

        $commandTester = new CommandTester($this->getCommand($rootFolder, $shell));
        $this->assertSame(0, $commandTester->execute(['--module' => 'Foo_Bar', '--magento-version' => '2.4.7']));
        $this->assertSame(['.', '..'], scandir($outsideFolder));
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

    /**
     * @dataProvider getRequirementComparisons
     */
    #[DataProvider('getRequirementComparisons')]
    public function testExecuteComparesExactVersionsIncludingPatchSuffixes(
        string $magentoRequiredVersion,
        string $moduleConstraint,
        bool $verbose,
        int $expectedExitCode,
        ?string $expectedMessage
    ) {
        $shell = $this->createMock(Shell::class);
        $shell->method('execute')->willReturn((string)json_encode([
            'requires' => ['foo/bar' => $magentoRequiredVersion, 'foo/other' => '1.0.0'],
        ]));

        $command = $this->getCommand($this->createTemporaryFolder(), $shell, ['foo/bar' => $moduleConstraint]);
        $commandTester = new CommandTester($command);
        $input = ['--module' => 'Foo_Bar', '--magento-version' => '2.4.7-p3'];
        if ($verbose) {
            $input['--verbose'] = true;
        }

        $this->assertSame($expectedExitCode, $commandTester->execute($input));

        $display = $commandTester->getDisplay();
        $this->assertStringNotContainsString('foo/other', $display);
        if (null === $expectedMessage) {
            $this->assertStringNotContainsString('foo/bar', $display);
            return;
        }

        $this->assertMatchesRegularExpression(
            '#\\| foo/bar +\\| ' . preg_quote($magentoRequiredVersion, '#') . ' +\\| '
            . preg_quote($moduleConstraint, '#') . ' +\\| ' . $expectedMessage . ' +\\|#',
            $display
        );
    }

    public static function getRequirementComparisons(): array
    {
        return [
            'patched version satisfied' => ['1.2.3-p3', '^1.0', true, 1, 'Satisfied'],
            'patched version not satisfied' => ['2.0.0-p1', '^1.0', false, 1, 'Not satisfied'],
            'patched version below minimum' => ['1.2.3-p1', '^1.2.4', false, 1, 'Not satisfied'],
            'patched version silent when satisfied' => ['1.2.3-p3', '^1.0', false, 0, null],
            'range is skipped' => ['^2.0', '^1.0', true, 1, null],
            'invalid module constraint' => ['1.2.3', 'not a constraint', false, 1, 'Invalid constraint'],
        ];
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

    private function getCommand(
        string $rootFolder,
        Shell $shell,
        array $moduleRequirements = ['foo/bar' => '^1.0']
    ): CheckMagentoVersionCommand {
        $composerFile = $this->createMock(ComposerFile::class);
        $composerFile->method('getRequirements')->willReturn($moduleRequirements);

        $composerFileProvider = $this->createMock(ComposerFileProvider::class);
        $composerFileProvider->method('getComposerFileByModuleName')->willReturn($composerFile);

        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn($rootFolder);

        $command = new CheckMagentoVersionCommand(
            $composerFileProvider,
            $directoryList,
            $shell,
            new CacheFile(new FileDriver())
        );
        $application = new Application();
        $application->add($command);

        return $command;
    }
}
