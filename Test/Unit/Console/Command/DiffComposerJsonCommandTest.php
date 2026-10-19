<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Console\Command\DiffComposerJsonCommand;
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;

class DiffComposerJsonCommandTest extends TestCase
{
    private const PATH = '/tmp/Foo_Bar/composer.json';

    public function testConfiguration()
    {
        $command = new DiffComposerJsonCommand($this->createMock(ComposerJsonSuggestion::class), new DiffGenerator());
        $this->assertSame('yireo_extensionchecker:diff:composer-json', $command->getName());
        $this->assertTrue($command->getDefinition()->hasArgument('module'));
        $this->assertTrue($command->getDefinition()->hasOption('with-php'));
    }

    public function testExecuteWithoutDifferences()
    {
        $composerJson = '{"require": {"foo/bar": "^1.0"}}' . "\n";
        $commandTester = new CommandTester($this->getCommand($composerJson, $composerJson, false));

        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar']));
        $this->assertStringContainsString('No differences found', $commandTester->getDisplay());
    }

    public function testExecuteWithDifferences()
    {
        $currentComposerJson = "{\n    \"require\": {\n        \"foo/bar\": \"^1.0\"\n    }\n}\n";
        $suggestedComposerJson = "{\n    \"require\": {\n        \"foo/baz\": \"^2.0\"\n    }\n}\n";

        $commandTester = new CommandTester($this->getCommand($currentComposerJson, $suggestedComposerJson, false));
        $exitCode = $commandTester->execute(['module' => 'Foo_Bar']);
        $display = $commandTester->getDisplay();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('--- ' . self::PATH . "\n", $display);
        $this->assertStringContainsString('+++ ' . self::PATH . ' (suggested)' . "\n", $display);
        $this->assertStringContainsString('-        "foo/bar": "^1.0"', $display);
        $this->assertStringContainsString('+        "foo/baz": "^2.0"', $display);
    }

    public function testExecuteWithPhpOption()
    {
        $composerJson = '{}';
        $commandTester = new CommandTester($this->getCommand($composerJson, $composerJson, true));
        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar', '--with-php' => true]));
    }

    private function getCommand(
        string $currentContents,
        string $suggestedContents,
        bool $withPhp
    ): DiffComposerJsonCommand {
        $suggestion = $this->createMock(ComposerJsonSuggestion::class);
        $suggestion->method('getCurrentPath')->with('Foo_Bar')->willReturn(self::PATH);
        $suggestion->method('getCurrentContents')->with('Foo_Bar')->willReturn($currentContents);
        $suggestion->expects($this->once())
            ->method('getSuggestedContents')
            ->with('Foo_Bar', [ComposerJsonSuggestion::OPTION_WITH_PHP => $withPhp])
            ->willReturn($suggestedContents);

        return new DiffComposerJsonCommand($suggestion, new DiffGenerator());
    }
}
