<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Composer\ComposerProvider;
use Yireo\ExtensionChecker\Composer\ComposerRequirementsMerger;
use Yireo\ExtensionChecker\Composer\MagentoPackageResolver;
use Yireo\ExtensionChecker\Console\Command\AutofixComposerJsonCommand;
use Yireo\ExtensionChecker\Console\Command\AutofixModuleXmlCommand;
use Yireo\ExtensionChecker\Console\Command\DiffComposerJsonCommand;
use Yireo\ExtensionChecker\Console\Command\DiffModuleXmlCommand;
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Exception\UnsafeFileException;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class SuggestionFileGuardTest extends TestCase
{
    use TemporaryFolder;

    private const OUTSIDE_CONTENTS = '{"outside": true}';

    /**
     * @dataProvider getCommandTypes
     */
    #[DataProvider('getCommandTypes')]
    public function testCommandRefusesSymlinkedFile(string $commandType, string $file)
    {
        $outsideFile = $this->createTemporaryFolder(['secret.txt' => self::OUTSIDE_CONTENTS]) . '/secret.txt';
        $moduleFolder = $this->createTemporaryFolder();
        mkdir($moduleFolder . '/etc');
        symlink($outsideFile, $moduleFolder . '/' . $file);

        $this->assertCommandIsRefused($commandType, $moduleFolder);
        $this->assertSame(self::OUTSIDE_CONTENTS, file_get_contents($outsideFile));
    }

    /**
     * @dataProvider getModuleXmlCommandTypes
     */
    #[DataProvider('getModuleXmlCommandTypes')]
    public function testCommandRefusesFileInSymlinkedFolder(string $commandType)
    {
        $outsideFolder = $this->createTemporaryFolder();
        $moduleFolder = $this->createTemporaryFolder();
        symlink($outsideFolder, $moduleFolder . '/etc');

        $this->assertCommandIsRefused($commandType, $moduleFolder);
        $this->assertSame(['.', '..'], scandir($outsideFolder));
    }

    public static function getModuleXmlCommandTypes(): array
    {
        return [
            ['diff:module-xml'],
            ['autofix:module-xml'],
        ];
    }

    public static function getCommandTypes(): array
    {
        return [
            ['diff:module-xml', 'etc/module.xml'],
            ['autofix:module-xml', 'etc/module.xml'],
            ['diff:composer-json', 'composer.json'],
            ['autofix:composer-json', 'composer.json'],
        ];
    }

    public function testAutofixStillWritesRegularFileInsideModule()
    {
        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => '<config/>' . "\n"]);
        $this->assertAutofixWritesModuleXml($moduleFolder);
    }

    public function testAutofixAllowsSymlinkedModuleFolder()
    {
        $realModuleFolder = $this->createTemporaryFolder(['etc/module.xml' => '<config/>' . "\n"]);
        $moduleFolder = $this->createTemporaryFolder() . '/module';
        symlink($realModuleFolder, $moduleFolder);

        $this->assertAutofixWritesModuleXml($moduleFolder);
    }

    private function assertAutofixWritesModuleXml(string $moduleFolder)
    {
        $commandTester = new CommandTester($this->getCommand('autofix:module-xml', $moduleFolder));
        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar']));
        $moduleXml = (string)file_get_contents($moduleFolder . '/etc/module.xml');
        $this->assertStringContainsString('<module name="Foo_Bar"/>', $moduleXml);
    }

    private function assertCommandIsRefused(string $commandType, string $moduleFolder)
    {
        $commandTester = new CommandTester($this->getCommand($commandType, $moduleFolder));

        try {
            $commandTester->execute(['module' => 'Foo_Bar']);
            $this->fail('Expected UnsafeFileException was not thrown');
        } catch (UnsafeFileException $exception) {
            $this->assertStringContainsString('outside of module folder', $exception->getMessage());
        }
    }

    private function getCommand(string $commandType, string $moduleFolder): Command
    {
        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')
            ->with(ComponentRegistrar::MODULE, 'Foo_Bar')
            ->willReturn($moduleFolder);

        $componentDetectorList = $this->createMock(ComponentDetectorList::class);
        $componentDetectorList->method('getComponentsByModuleName')->willReturn([]);
        $magentoPackageResolver = $this->createMock(MagentoPackageResolver::class);

        $moduleXmlSuggestion = new ModuleXmlSuggestion(
            $componentRegistrar,
            $componentDetectorList,
            $magentoPackageResolver
        );

        $composerJsonSuggestion = new ComposerJsonSuggestion(
            $componentRegistrar,
            $componentDetectorList,
            new ComposerProvider($this->createMock(ComposerFileProvider::class)),
            new ComposerRequirementsMerger(),
            $magentoPackageResolver
        );

        return match ($commandType) {
            'diff:module-xml' => new DiffModuleXmlCommand($moduleXmlSuggestion, new DiffGenerator()),
            'autofix:module-xml' => new AutofixModuleXmlCommand(
                $moduleXmlSuggestion,
                new DiffGenerator(),
                new FileDriver()
            ),
            'diff:composer-json' => new DiffComposerJsonCommand($composerJsonSuggestion, new DiffGenerator()),
            'autofix:composer-json' => new AutofixComposerJsonCommand(
                $composerJsonSuggestion,
                new DiffGenerator(),
                new FileDriver()
            ),
        };
    }
}
