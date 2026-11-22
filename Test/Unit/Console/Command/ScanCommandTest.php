<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Console\Command\ScanCommand;
use Yireo\ExtensionChecker\Message\MessageBucket;
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
}
