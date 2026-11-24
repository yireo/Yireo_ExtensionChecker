<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Console\Command\ScanCommand;
use Yireo\ExtensionChecker\Message\Message;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Scan\Scan;

class ScanCommandTest extends TestCase
{
    /**
     * @dataProvider getFlagValues
     */
    #[DataProvider('getFlagValues')]
    public function testFlags(array $options, bool $expectedValue)
    {
        $runtimeConfig = new RuntimeConfig();
        $scan = $this->createMock(Scan::class);
        $scan->expects($this->once())->method('scan')->with('Foo_Bar', '');

        $command = new ScanCommand(
            $scan,
            $this->createMock(SerializerInterface::class),
            $runtimeConfig,
            new MessageBucket($this->createMock(ObjectManagerInterface::class), $runtimeConfig)
        );

        $commandTester = new CommandTester($command);
        $optionNames = ['skip-license-check', 'hide-deprecated', 'hide-needless'];
        $input = ['--module' => 'Foo_Bar'];
        foreach ($options as $value) {
            foreach ($optionNames as $optionName) {
                $input['--' . $optionName] = $value;
            }
        }

        $this->assertSame(0, $commandTester->execute($input));
        $this->assertSame($expectedValue, $runtimeConfig->isSkipLicenseCheck());
        $this->assertSame($expectedValue, $runtimeConfig->isHideDeprecated());
        $this->assertSame($expectedValue, $runtimeConfig->isHideNeedless());
    }

    public static function getFlagValues(): array
    {
        return [
            'absent' => [[], false],
            'without value' => [[null], true],
            'with value 1' => [['1'], true],
            'with value 0' => [['0'], false],
        ];
    }

    public function testJsonFormatWithDecoratedVerboseOutput()
    {
        $runtimeConfig = new RuntimeConfig();
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
        $messageBucket = new MessageBucket($objectManager, $runtimeConfig);

        $scan = $this->createMock(Scan::class);
        $scan->method('scan')->willReturnCallback(function () use ($runtimeConfig, $messageBucket) {
            $runtimeConfig->debugMessage('Component by module name: Foo_Bar');
            $messageBucket->add(
                'Use <info>x</info> and <error>',
                MessageGroupLabels::GROUP_COMPOSER_ISSUES,
                'Fix <comment>it</comment>',
                'Foo_Bar'
            );
        });

        $command = new ScanCommand($scan, new Json(), $runtimeConfig, $messageBucket);
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute(
            ['--module' => 'Foo_Bar', '--format' => 'json'],
            ['decorated' => true, 'verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'capture_stderr_separately' => true]
        );

        $this->assertSame(1, $exitCode);
        $this->assertSame([
            [
                'message' => 'Use <info>x</info> and <error>',
                'group' => MessageGroupLabels::GROUP_COMPOSER_ISSUES,
                'suggestion' => 'Fix <comment>it</comment>',
                'module' => 'Foo_Bar',
            ],
        ], json_decode($commandTester->getDisplay(), true));
        $this->assertStringContainsString('Component by module name: Foo_Bar', $commandTester->getErrorOutput());
    }

    public function testJsonFormatSendsErrorsToErrorOutput()
    {
        $runtimeConfig = new RuntimeConfig();
        $scan = $this->createMock(Scan::class);
        $scan->method('scan')->willReturnCallback(function () use ($runtimeConfig) {
            $runtimeConfig->debugMessage('Component by module name: Foo_Bar');
            throw new RuntimeException('Module "Foo_Bar" is <error>broken</error>');
        });

        $command = new ScanCommand($scan, new Json(), $runtimeConfig, $this->getMessageBucket($runtimeConfig));
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute(
            ['--module' => 'Foo_Bar', '--format' => 'json'],
            ['decorated' => true, 'verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'capture_stderr_separately' => true]
        );

        $this->assertSame(1, $exitCode);
        $this->assertSame('', $commandTester->getDisplay());
        $errorOutput = $commandTester->getErrorOutput();
        $this->assertStringContainsString('Component by module name: Foo_Bar', $errorOutput);
        $this->assertStringContainsString('Module "Foo_Bar" is <error>broken</error>', $errorOutput);
    }

    public function testJsonFormatWithSingleOutputStream()
    {
        $runtimeConfig = new RuntimeConfig();
        $messageBucket = $this->getMessageBucket($runtimeConfig);
        $scan = $this->createMock(Scan::class);
        $scan->method('scan')->willReturnCallback(function () use ($runtimeConfig, $messageBucket) {
            $runtimeConfig->debugMessage('Component by module name: Foo_Bar');
            $messageBucket->add('Use <info>x</info>', MessageGroupLabels::GROUP_COMPOSER_ISSUES, '', 'Foo_Bar');
        });

        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE, true);
        $command = new ScanCommand($scan, new Json(), $runtimeConfig, $messageBucket);
        $exitCode = $command->run(new ArrayInput(['--module' => 'Foo_Bar', '--format' => 'json']), $output);

        $this->assertSame(1, $exitCode);
        $this->assertSame([
            [
                'message' => 'Use <info>x</info>',
                'group' => MessageGroupLabels::GROUP_COMPOSER_ISSUES,
                'suggestion' => '',
                'module' => 'Foo_Bar',
            ],
        ], json_decode($output->fetch(), true));
    }

    public function testJsonFormatWithSingleOutputStreamOmitsErrors()
    {
        $runtimeConfig = new RuntimeConfig();
        $scan = $this->createMock(Scan::class);
        $scan->method('scan')->willReturnCallback(function () use ($runtimeConfig) {
            $runtimeConfig->debugMessage('Component by module name: Foo_Bar');
            throw new RuntimeException('Broken');
        });

        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE, true);
        $command = new ScanCommand($scan, new Json(), $runtimeConfig, $this->getMessageBucket($runtimeConfig));
        $exitCode = $command->run(new ArrayInput(['--module' => 'Foo_Bar', '--format' => 'json']), $output);

        $this->assertSame(1, $exitCode);
        $this->assertSame('', $output->fetch());
    }

    public function testTableFormatStillShowsErrorsOnOutput()
    {
        $runtimeConfig = new RuntimeConfig();
        $scan = $this->createMock(Scan::class);
        $scan->method('scan')->willThrowException(new RuntimeException('Broken'));

        $output = new BufferedOutput();
        $command = new ScanCommand($scan, new Json(), $runtimeConfig, $this->getMessageBucket($runtimeConfig));

        $this->assertSame(1, $command->run(new ArrayInput(['--module' => 'Foo_Bar']), $output));
        $this->assertSame("Broken\n", $output->fetch());
    }

    public function testVerboseJsonScanWithOnlyDebugMessagesSendsThemToErrorOutput()
    {
        $runtimeConfig = new RuntimeConfig();
        $messageBucket = $this->getMessageBucket($runtimeConfig);
        $command = new ScanCommand($this->getDebugScan($messageBucket, false), new Json(), $runtimeConfig, $messageBucket);
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute(
            ['--module' => 'Foo_Bar', '--format' => 'json'],
            ['decorated' => true, 'verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'capture_stderr_separately' => true]
        );

        $this->assertSame(0, $exitCode);
        $this->assertSame([], json_decode($commandTester->getDisplay(), true));
        $this->assertSame("[]\n", $commandTester->getDisplay());
        $errorOutput = $commandTester->getErrorOutput();
        $this->assertStringContainsString('Found class "Foo\\Bar<info>x</info>"', $errorOutput);
        $this->assertStringNotContainsString("\e[32mx", $errorOutput);
    }

    public function testVerboseJsonScanWithOnlyDebugMessagesOnSingleOutputStream()
    {
        $runtimeConfig = new RuntimeConfig();
        $messageBucket = $this->getMessageBucket($runtimeConfig);
        $command = new ScanCommand($this->getDebugScan($messageBucket, false), new Json(), $runtimeConfig, $messageBucket);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE, true);

        $this->assertSame(0, $command->run(new ArrayInput(['--module' => 'Foo_Bar', '--format' => 'json']), $output));
        $this->assertSame("[]\n", $output->fetch());
    }

    public function testVerboseJsonScanExcludesDebugMessagesFromFindings()
    {
        $runtimeConfig = new RuntimeConfig();
        $messageBucket = $this->getMessageBucket($runtimeConfig);
        $command = new ScanCommand($this->getDebugScan($messageBucket, true), new Json(), $runtimeConfig, $messageBucket);
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute(
            ['--module' => 'Foo_Bar', '--format' => 'json'],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE, 'capture_stderr_separately' => true]
        );

        $this->assertSame(1, $exitCode);
        $this->assertSame([
            [
                'message' => 'Real finding',
                'group' => MessageGroupLabels::GROUP_COMPOSER_ISSUES,
                'suggestion' => '',
                'module' => 'Foo_Bar',
            ],
        ], json_decode($commandTester->getDisplay(), true));
        $this->assertStringContainsString('Found class', $commandTester->getErrorOutput());
    }

    public function testVerboseTableScanWithOnlyDebugMessagesExitsWithZero()
    {
        $runtimeConfig = new RuntimeConfig();
        $messageBucket = $this->getMessageBucket($runtimeConfig);
        $command = new ScanCommand($this->getDebugScan($messageBucket, false), new Json(), $runtimeConfig, $messageBucket);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute(
            ['--module' => 'Foo_Bar'],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Found class', $commandTester->getDisplay());
        $this->assertStringContainsString('Debug', $commandTester->getDisplay());
    }

    public function testTableScanWithFindingsStillExitsWithOne()
    {
        $runtimeConfig = new RuntimeConfig();
        $messageBucket = $this->getMessageBucket($runtimeConfig);
        $command = new ScanCommand($this->getDebugScan($messageBucket, true), new Json(), $runtimeConfig, $messageBucket);
        $commandTester = new CommandTester($command);

        $this->assertSame(1, $commandTester->execute(['--module' => 'Foo_Bar']));
        $this->assertStringContainsString('Real finding', $commandTester->getDisplay());
        $this->assertStringNotContainsString('Found class', $commandTester->getDisplay());
    }

    private function getDebugScan(MessageBucket $messageBucket, bool $withFinding): Scan
    {
        $scan = $this->createMock(Scan::class);
        $scan->method('scan')->willReturnCallback(function () use ($messageBucket, $withFinding) {
            $messageBucket->debug('Found class "Foo\\Bar<info>x</info>"');
            if ($withFinding) {
                $messageBucket->add('Real finding', MessageGroupLabels::GROUP_COMPOSER_ISSUES, '', 'Foo_Bar');
            }
        });

        return $scan;
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
