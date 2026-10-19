<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Console\Command\SuggestComposerJsonCommand;
use Yireo\ExtensionChecker\Console\Command\SuggestModuleXmlCommand;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;

class SuggestCommandsTest extends TestCase
{
    public function testSuggestModuleXml()
    {
        $moduleXml = '<config><module name="Foo_Bar"/></config>';
        $suggestion = $this->createMock(ModuleXmlSuggestion::class);
        $suggestion->expects($this->once())
            ->method('getSuggestedContents')
            ->with('Foo_Bar')
            ->willReturn($moduleXml . "\n");

        $commandTester = new CommandTester(new SuggestModuleXmlCommand($suggestion));

        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar']));
        $this->assertSame($moduleXml . PHP_EOL, $commandTester->getDisplay());
    }

    public function testSuggestComposerJson()
    {
        $composerJson = '{"require": {"php": ">=8.2 <8.6"}}';
        $suggestion = $this->createMock(ComposerJsonSuggestion::class);
        $suggestion->expects($this->once())
            ->method('getSuggestedContents')
            ->with('Foo_Bar', [ComposerJsonSuggestion::OPTION_WITH_PHP => true])
            ->willReturn($composerJson);

        $commandTester = new CommandTester(new SuggestComposerJsonCommand($suggestion));

        $this->assertSame(
            Command::SUCCESS,
            $commandTester->execute(['module' => 'Foo_Bar', '--with-php' => true])
        );
        $this->assertSame($composerJson . PHP_EOL, $commandTester->getDisplay());
    }
}
