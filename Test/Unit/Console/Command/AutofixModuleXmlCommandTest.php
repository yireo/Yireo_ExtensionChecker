<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Console\Command\AutofixModuleXmlCommand;
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class AutofixModuleXmlCommandTest extends TestCase
{
    use TemporaryFolder;

    public function testConfiguration()
    {
        $command = new AutofixModuleXmlCommand(
            $this->createMock(ModuleXmlSuggestion::class),
            new DiffGenerator(),
            new FileDriver()
        );

        $this->assertSame('yireo_extensionchecker:autofix:module-xml', $command->getName());
        $this->assertTrue($command->getDefinition()->hasArgument('module'));
    }

    public function testExecuteReplacesFile()
    {
        $currentModuleXml = '<config><module name="Foo_Bar" setup_version="1.0.0"/></config>' . "\n";
        $suggestedModuleXml = '<config><module name="Foo_Bar"/></config>' . "\n";
        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => $currentModuleXml]);
        $path = $moduleFolder . '/etc/module.xml';

        $commandTester = new CommandTester($this->getCommand($path, $suggestedModuleXml, new FileDriver()));
        $exitCode = $commandTester->execute(['module' => 'Foo_Bar']);
        $display = $commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame($suggestedModuleXml, file_get_contents($path));
        $this->assertStringContainsString('-<config><module name="Foo_Bar" setup_version="1.0.0"/></config>', $display);
        $this->assertStringContainsString('+<config><module name="Foo_Bar"/></config>', $display);
        $this->assertStringContainsString('Updated "' . $path . '"', $display);
    }

    public function testExecuteCreatesMissingFile()
    {
        $suggestedModuleXml = '<config><module name="Foo_Bar"/></config>' . "\n";
        $moduleFolder = $this->createTemporaryFolder();
        $path = $moduleFolder . '/etc/module.xml';

        $commandTester = new CommandTester($this->getCommand($path, $suggestedModuleXml, new FileDriver()));

        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar']));
        $this->assertSame($suggestedModuleXml, file_get_contents($path));
    }

    public function testExecuteWithoutChangesDoesNotWriteFile()
    {
        $moduleXml = '<config><module name="Foo_Bar"/></config>' . "\n";
        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => $moduleXml]);
        $path = $moduleFolder . '/etc/module.xml';

        $fileDriver = $this->createMock(FileDriver::class);
        $fileDriver->expects($this->never())->method('filePutContents');

        $commandTester = new CommandTester($this->getCommand($path, $moduleXml, $fileDriver));

        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar']));
        $this->assertStringContainsString('No changes needed', $commandTester->getDisplay());
    }

    private function getCommand(string $path, string $suggestedContents, FileDriver $fileDriver): AutofixModuleXmlCommand
    {
        $suggestion = $this->createMock(ModuleXmlSuggestion::class);
        $suggestion->method('getCurrentPath')->with('Foo_Bar')->willReturn($path);
        $suggestion->method('getCurrentContents')->with('Foo_Bar')->willReturnCallback(
            fn () => is_file($path) ? (string)file_get_contents($path) : ''
        );
        $suggestion->expects($this->once())
            ->method('getSuggestedContents')
            ->with('Foo_Bar', [])
            ->willReturn($suggestedContents);

        return new AutofixModuleXmlCommand($suggestion, new DiffGenerator(), $fileDriver);
    }
}
