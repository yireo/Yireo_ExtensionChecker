<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Console\Command\DiffModuleXmlCommand;
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;

class DiffModuleXmlCommandTest extends TestCase
{
    private const PATH = '/tmp/Foo_Bar/etc/module.xml';

    public function testConfiguration()
    {
        $command = new DiffModuleXmlCommand($this->createMock(ModuleXmlSuggestion::class), new DiffGenerator());
        $this->assertSame('yireo_extensionchecker:diff:module-xml', $command->getName());
        $this->assertTrue($command->getDefinition()->hasArgument('module'));
        $this->assertTrue($command->getDefinition()->getArgument('module')->isRequired());
    }

    public function testExecuteWithoutDifferences()
    {
        $moduleXml = '<config><module name="Foo_Bar"/></config>' . "\n";
        $commandTester = new CommandTester($this->getCommand($moduleXml, $moduleXml));
        $exitCode = $commandTester->execute(['module' => 'Foo_Bar']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No differences found', $commandTester->getDisplay());
    }

    public function testExecuteWithDifferences()
    {
        $currentModuleXml = <<<EOF
<config>
    <module name="Foo_Bar">
        <sequence>
            <module name="Foo_Obsolete"/>
        </sequence>
    </module>
</config>

EOF;
        $suggestedModuleXml = str_replace('Foo_Obsolete', 'Magento_Store', $currentModuleXml);

        $commandTester = new CommandTester($this->getCommand($currentModuleXml, $suggestedModuleXml));
        $exitCode = $commandTester->execute(['module' => 'Foo_Bar']);
        $display = $commandTester->getDisplay();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('--- ' . self::PATH . "\n", $display);
        $this->assertStringContainsString('+++ ' . self::PATH . ' (suggested)' . "\n", $display);
        $this->assertStringContainsString('-            <module name="Foo_Obsolete"/>', $display);
        $this->assertStringContainsString('+            <module name="Magento_Store"/>', $display);
    }

    private function getCommand(string $currentContents, string $suggestedContents): DiffModuleXmlCommand
    {
        $suggestion = $this->createMock(ModuleXmlSuggestion::class);
        $suggestion->method('getCurrentPath')->with('Foo_Bar')->willReturn(self::PATH);
        $suggestion->method('getCurrentContents')->with('Foo_Bar')->willReturn($currentContents);
        $suggestion->expects($this->once())
            ->method('getSuggestedContents')
            ->with('Foo_Bar', [])
            ->willReturn($suggestedContents);

        return new DiffModuleXmlCommand($suggestion, new DiffGenerator());
    }
}
