<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Console\Command\AutofixModuleXmlCommand;
use Yireo\ExtensionChecker\Console\Command\DiffModuleXmlCommand;
use Yireo\ExtensionChecker\Console\Command\ScanCommand;
use Yireo\ExtensionChecker\Console\Command\SuggestModuleXmlCommand;
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Message\Message;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Scan\Scan;
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class ConsoleOutputEscapingTest extends TestCase
{
    use TemporaryFolder;

    private const PAYLOAD = "</error><info>x\e[2J\x07\u{9b}";
    private const SANITIZED_PAYLOAD = '</error><info>x[2J';

    public function testScanEscapesExceptionMessage()
    {
        $runtimeConfig = new RuntimeConfig();
        $scan = $this->createMock(Scan::class);
        $scan->method('scan')->willThrowException(new RuntimeException('Broken ' . self::PAYLOAD));

        $commandTester = new CommandTester($this->getScanCommand($scan, $runtimeConfig));
        $this->assertSame(1, $commandTester->execute(['--module' => 'Foo_Bar'], ['decorated' => true]));

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Broken ' . self::SANITIZED_PAYLOAD, $display);
        $this->assertNoControlSequences($display);
        $this->assertStringNotContainsString("\e[32mx", $display);
    }

    public function testScanEscapesTableCells()
    {
        $runtimeConfig = new RuntimeConfig();
        $messageBucket = $this->getMessageBucket($runtimeConfig);
        $scan = $this->createMock(Scan::class);
        $scan->method('scan')->willReturnCallback(function () use ($messageBucket) {
            $messageBucket->add(
                'Message ' . self::PAYLOAD,
                MessageGroupLabels::GROUP_COMPOSER_ISSUES,
                'Suggestion ' . self::PAYLOAD,
                'Module ' . self::PAYLOAD
            );
        });

        $commandTester = new CommandTester($this->getScanCommand($scan, $runtimeConfig, $messageBucket));
        $this->assertSame(1, $commandTester->execute(['--module' => 'Foo_Bar'], ['decorated' => true]));

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Message ' . self::SANITIZED_PAYLOAD, $display);
        $this->assertStringContainsString('Suggestion ' . self::SANITIZED_PAYLOAD, $display);
        $this->assertStringContainsString('Module ' . self::SANITIZED_PAYLOAD, $display);
        $this->assertNoControlSequences($display);
        $this->assertStringNotContainsString("\e[32mx", $display);
    }

    public function testDiffStripsControlCharactersFromRawDiff()
    {
        $suggestion = $this->createMock(ModuleXmlSuggestion::class);
        $suggestion->method('getCurrentPath')->willReturn('/tmp/Foo_Bar/etc/module.xml');
        $suggestion->method('getCurrentContents')->willReturn("<config>\n</config>\n");
        $suggestion->method('getSuggestedContents')->willReturn("<config>\n<!-- " . self::PAYLOAD . " -->\n</config>\n");

        $commandTester = new CommandTester(new DiffModuleXmlCommand($suggestion, new DiffGenerator()));
        $this->assertSame(Command::FAILURE, $commandTester->execute(['module' => 'Foo_Bar'], ['decorated' => true]));

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString("+<!-- " . self::SANITIZED_PAYLOAD . " -->\n", $display);
        $this->assertNoControlSequences($display);
    }

    public function testAutofixEscapesPathAndStripsControlCharactersFromDiff()
    {
        $folderName = 'yireo-extensionchecker-<error>x' . "\e[2J" . uniqid();
        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => "<config>\n</config>\n"], $folderName);
        $path = $moduleFolder . '/etc/module.xml';

        $suggestion = $this->createMock(ModuleXmlSuggestion::class);
        $suggestion->method('getCurrentPath')->willReturn($path);
        $suggestion->method('getCurrentContents')->willReturnCallback(fn () => (string)file_get_contents($path));
        $suggestion->method('getSuggestedContents')->willReturn("<config>\n<!-- " . self::PAYLOAD . " -->\n</config>\n");

        $command = new AutofixModuleXmlCommand($suggestion, new DiffGenerator(), new FileDriver());
        $commandTester = new CommandTester($command);
        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar'], ['decorated' => true]));

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString("+<!-- " . self::SANITIZED_PAYLOAD . " -->\n", $display);
        $this->assertStringContainsString('Updated "' . sys_get_temp_dir() . '/yireo-extensionchecker-<error>x[2J', $display);
        $this->assertNoControlSequences($display);
        $this->assertStringContainsString(self::PAYLOAD, (string)file_get_contents($path));
    }

    public function testAutofixEscapesPathWithoutChanges()
    {
        $path = '/tmp/' . self::PAYLOAD . '/module.xml';
        $suggestion = $this->createMock(ModuleXmlSuggestion::class);
        $suggestion->method('getCurrentPath')->willReturn($path);
        $suggestion->method('getCurrentContents')->willReturn("<config/>\n");
        $suggestion->method('getSuggestedContents')->willReturn("<config/>\n");

        $command = new AutofixModuleXmlCommand($suggestion, new DiffGenerator(), new FileDriver());
        $commandTester = new CommandTester($command);
        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar'], ['decorated' => true]));

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('No changes needed for "/tmp/' . self::SANITIZED_PAYLOAD . '/module.xml"', $display);
        $this->assertNoControlSequences($display);
        $this->assertStringNotContainsString("\e[32mx", $display);
    }

    public function testSuggestStripsControlCharacters()
    {
        $suggestion = $this->createMock(ModuleXmlSuggestion::class);
        $suggestion->method('getSuggestedContents')->willReturn("<config><!-- " . self::PAYLOAD . " --></config>\n");

        $commandTester = new CommandTester(new SuggestModuleXmlCommand($suggestion));
        $this->assertSame(Command::SUCCESS, $commandTester->execute(['module' => 'Foo_Bar'], ['decorated' => true]));

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('<config><!-- ' . self::SANITIZED_PAYLOAD . ' --></config>', $display);
        $this->assertNoControlSequences($display);
    }

    private function assertNoControlSequences(string $display): void
    {
        $this->assertStringNotContainsString("\e[2J", $display);
        $this->assertStringNotContainsString("\x07", $display);
        $this->assertStringNotContainsString("\u{9b}", $display);
    }

    private function getScanCommand(
        Scan $scan,
        RuntimeConfig $runtimeConfig,
        ?MessageBucket $messageBucket = null
    ): ScanCommand {
        return new ScanCommand(
            $scan,
            $this->createMock(SerializerInterface::class),
            $runtimeConfig,
            $messageBucket ?? $this->getMessageBucket($runtimeConfig)
        );
    }

    private function getMessageBucket(RuntimeConfig $runtimeConfig): MessageBucket
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('create')->willReturnCallback(
            fn (string $className, array $arguments) => new Message(
                new MessageGroupLabels(),
                $arguments['message'],
                $arguments['group'],
                $arguments['suggestion'],
                $arguments['module']
            )
        );

        return new MessageBucket($objectManager, $runtimeConfig);
    }
}
