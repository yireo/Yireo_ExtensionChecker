<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Scan;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerFile;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Scan\ScanComposerFile;

class ScanComposerFileTest extends TestCase
{
    private const LICENSE_LIST = '{"licenses":[{"licenseId":"OSL-3.0"},{"licenseId":"MIT"},{"licenseId":"1"}]}';

    private array $messages = [];

    /**
     * @dataProvider getKnownLicenses
     */
    #[DataProvider('getKnownLicenses')]
    public function testScanWithKnownLicenses(mixed $license)
    {
        $this->getScanComposerFile($license)->scan('Foo_Bar');
        $this->assertSame([], $this->messages);
    }

    public static function getKnownLicenses(): array
    {
        return [
            ['OSL-3.0'],
            [['OSL-3.0', 'MIT']],
        ];
    }

    public function testScanWithUnknownLicense()
    {
        $this->getScanComposerFile(['OSL-3.0', 'Proprietary'])->scan('Foo_Bar');
        $this->assertSame([
            ['Composer file mentions unsupported license: Proprietary', MessageGroupLabels::GROUP_COMPOSER_ISSUES],
        ], $this->messages);
    }

    /**
     * @dataProvider getInvalidLicenseEntries
     */
    #[DataProvider('getInvalidLicenseEntries')]
    public function testScanWithNonStringLicenseEntries(mixed $license)
    {
        $this->getScanComposerFile(['MIT', $license])->scan('Foo_Bar');
        $this->assertCount(1, $this->messages);
        $this->assertSame('Composer file mentions an invalid license', $this->messages[0][0]);
        $this->assertSame(MessageGroupLabels::GROUP_COMPOSER_ISSUES, $this->messages[0][1]);
    }

    public static function getInvalidLicenseEntries(): array
    {
        return [
            [true],
            [1],
            [null],
            [['MIT']],
        ];
    }

    /**
     * @dataProvider getInvalidLicenses
     */
    #[DataProvider('getInvalidLicenses')]
    public function testScanWithInvalidLicenseValue(mixed $license)
    {
        $this->getScanComposerFile($license)->scan('Foo_Bar');
        $this->assertCount(1, $this->messages);
        $this->assertSame('Composer file mentions an invalid license', $this->messages[0][0]);
        $this->assertSame(MessageGroupLabels::GROUP_COMPOSER_ISSUES, $this->messages[0][1]);
    }

    public static function getInvalidLicenses(): array
    {
        return [
            [true],
            [1],
            [1.5],
        ];
    }

    private function getScanComposerFile(mixed $license): ScanComposerFile
    {
        $composerFile = $this->createMock(ComposerFile::class);
        $composerFile->method('get')->with('license')->willReturn($license);

        $composerFileProvider = $this->createMock(ComposerFileProvider::class);
        $composerFileProvider->method('getComposerFileByModuleName')->with('Foo_Bar')->willReturn($composerFile);

        $messageBucket = $this->createMock(MessageBucket::class);
        $messageBucket->method('add')->willReturnCallback(function (string $message, string $group) {
            $this->messages[] = [$message, $group];
        });

        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn(new Response(200, [], self::LICENSE_LIST));

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->method('create')->willReturn($client);

        return new ScanComposerFile($composerFileProvider, $messageBucket, $clientFactory);
    }
}
