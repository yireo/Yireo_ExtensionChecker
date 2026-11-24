<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Composer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Shell;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerFileFactory;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;
use Yireo\ExtensionChecker\Util\ModuleInfo;

class ComposerFileProviderTest extends TestCase
{
    use TemporaryFolder;

    private const INSTALLED_PACKAGES = '{"installed":[{"name":"foo/bar","version":"1.2.3"}]}';

    public function testGetAllComposerNamesRunsInRootAndRestoresWorkingDirectory()
    {
        $rootFolder = (string)realpath($this->createTemporaryFolder());
        $workingDirectory = getcwd();
        $workingDirectories = [];

        $shell = $this->createMock(Shell::class);
        $shell->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function (string $command) use (&$workingDirectories) {
                $workingDirectories[] = getcwd();
                return $command === 'composer show --help' ? '--no-scripts --no-plugins' : self::INSTALLED_PACKAGES;
            });

        $composerFileProvider = $this->getComposerFileProvider($rootFolder, $shell);

        $this->assertSame('1.2.3', $composerFileProvider->getVersionByComposerName('foo/bar'));
        $this->assertSame('1.2.3', $composerFileProvider->getVersionByComposerName('foo/bar'));
        $this->assertSame('', $composerFileProvider->getVersionByComposerName('foo/baz'));
        $this->assertSame([$rootFolder, $rootFolder], $workingDirectories);
        $this->assertSame($workingDirectory, getcwd());
    }

    public function testGetAllComposerNamesRestoresWorkingDirectoryOnFailure()
    {
        $workingDirectory = getcwd();
        $shell = $this->createMock(Shell::class);
        $shell->method('execute')->willThrowException(new LocalizedException(new Phrase('Command failed')));

        $composerFileProvider = $this->getComposerFileProvider($this->createTemporaryFolder(), $shell);

        try {
            $composerFileProvider->getAllComposerNames();
            $this->fail('Expected LocalizedException was not thrown');
        } catch (LocalizedException $exception) {
            $this->assertSame('Command failed', $exception->getMessage());
        }

        $this->assertSame($workingDirectory, getcwd());
    }

    public function testInstalledPackagesAreCachedPerInstance()
    {
        $rootFolder = $this->createTemporaryFolder();
        $firstShell = $this->createMock(Shell::class);
        $firstShell->expects($this->exactly(2))->method('execute')->willReturn(self::INSTALLED_PACKAGES);
        $secondShell = $this->createMock(Shell::class);
        $secondShell->expects($this->exactly(2))
            ->method('execute')
            ->willReturn('{"installed":[{"name":"foo/bar","version":"2.0.0"}]}');

        $this->assertSame('1.2.3', $this->getComposerFileProvider($rootFolder, $firstShell)->getVersionByComposerName('foo/bar'));
        $this->assertSame('2.0.0', $this->getComposerFileProvider($rootFolder, $secondShell)->getVersionByComposerName('foo/bar'));
    }

    private function getComposerFileProvider(string $rootFolder, Shell $shell): ComposerFileProvider
    {
        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn($rootFolder);

        return new ComposerFileProvider(
            new FileDriver(),
            $this->createMock(ComposerFileFactory::class),
            $directoryList,
            new Json(),
            $shell,
            $this->createMock(ModuleInfo::class)
        );
    }
}
