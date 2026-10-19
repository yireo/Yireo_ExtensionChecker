<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Console\Command\AutofixComposerJsonCommand;
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class AutofixComposerJsonCommandTest extends TestCase
{
    use TemporaryFolder;

    public function testConfiguration()
    {
        $command = new AutofixComposerJsonCommand(
            $this->createMock(ComposerJsonSuggestion::class),
            new DiffGenerator(),
            new FileDriver()
        );

        $this->assertSame('yireo_extensionchecker:autofix:composer-json', $command->getName());
        $this->assertTrue($command->getDefinition()->hasArgument('module'));
        $this->assertTrue($command->getDefinition()->hasOption('with-php'));
    }

    public function testExecuteReplacesFile()
    {
        $currentComposerJson = "{\n    \"require\": {\n        \"foo/bar\": \"^1.0\"\n    }\n}\n";
        $suggestedComposerJson = "{\n    \"require\": {\n        \"foo/baz\": \"^2.0\"\n    }\n}\n";
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $currentComposerJson]);
        $path = $moduleFolder . '/composer.json';

        $commandTester = new CommandTester($this->getCommand($path, $suggestedComposerJson, false));
        $exitCode = $commandTester->execute(['module' => 'Foo_Bar']);
        $display = $commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame($suggestedComposerJson, file_get_contents($path));
        $this->assertStringContainsString('-        "foo/bar": "^1.0"', $display);
        $this->assertStringContainsString('+        "foo/baz": "^2.0"', $display);
        $this->assertStringContainsString('Updated "' . $path . '"', $display);
    }

    public function testExecuteWithPhpOption()
    {
        $currentComposerJson = "{\n    \"require\": {}\n}\n";
        $suggestedComposerJson = "{\n    \"require\": {\n        \"php\": \">=8.2 <8.6\"\n    }\n}\n";
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $currentComposerJson]);
        $path = $moduleFolder . '/composer.json';

        $commandTester = new CommandTester($this->getCommand($path, $suggestedComposerJson, true));

        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar', '--with-php' => true]));
        $this->assertSame($suggestedComposerJson, file_get_contents($path));
    }

    public function testExecuteWithoutChanges()
    {
        $composerJson = "{\n    \"require\": {}\n}\n";
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $composerJson]);
        $path = $moduleFolder . '/composer.json';
        $modificationTime = filemtime($path);

        $commandTester = new CommandTester($this->getCommand($path, $composerJson, false));

        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar']));
        $this->assertStringContainsString('No changes needed', $commandTester->getDisplay());
        $this->assertSame($composerJson, file_get_contents($path));
        $this->assertSame($modificationTime, filemtime($path));
    }

    private function getCommand(string $path, string $suggestedContents, bool $withPhp): AutofixComposerJsonCommand
    {
        $suggestion = $this->createMock(ComposerJsonSuggestion::class);
        $suggestion->method('getCurrentPath')->with('Foo_Bar')->willReturn($path);
        $suggestion->method('getCurrentContents')->with('Foo_Bar')->willReturnCallback(
            fn () => (string)file_get_contents($path)
        );
        $suggestion->expects($this->once())
            ->method('getSuggestedContents')
            ->with('Foo_Bar', [ComposerJsonSuggestion::OPTION_WITH_PHP => $withPhp])
            ->willReturn($suggestedContents);

        return new AutofixComposerJsonCommand($suggestion, new DiffGenerator(), new FileDriver());
    }
}
