<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
}
