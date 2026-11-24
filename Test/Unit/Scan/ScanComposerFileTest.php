<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Scan;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerFile;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Scan\ScanComposerFile;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;
use Yireo\ExtensionChecker\Util\CacheFile;

class ScanComposerFileTest extends TestCase
{
    use TemporaryFolder;

    private const LICENSE_LIST_URL
        = 'https://raw.githubusercontent.com/spdx/license-list-data/refs/tags/v3.29.0/json/licenses.json';
    private const CACHE_FILE = '/yireo_extensionchecker/spdx-licenses-v3.29.0.json';
    private const FETCH_WARNING = 'Unable to fetch the SPDX license list, skipping license check';
    private const LICENSE_LIST = '{"licenses":[{"licenseId":"OSL-3.0"},{"licenseId":"MIT"},{"licenseId":"1"}]}';

    private array $messages = [];
    private string $varFolder = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->varFolder = $this->createTemporaryFolder();
    }

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

    public function testScanFetchesLicenseListWithTimeoutsAndWritesCache()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->with(self::LICENSE_LIST_URL, ['timeout' => 10, 'connect_timeout' => 5])
            ->willReturn(new Response(200, [], self::LICENSE_LIST));

        $this->getScanComposerFile('Proprietary', $client)->scan('Foo_Bar');

        $this->assertSame([
            ['Composer file mentions unsupported license: Proprietary', MessageGroupLabels::GROUP_COMPOSER_ISSUES],
        ], $this->messages);
        $this->assertSame(
            ['OSL-3.0', 'MIT', '1'],
            json_decode((string)file_get_contents($this->varFolder . self::CACHE_FILE), true)
        );
    }

    public function testScanUsesCachedLicenseList()
    {
        mkdir(dirname($this->varFolder . self::CACHE_FILE));
        file_put_contents($this->varFolder . self::CACHE_FILE, '["Proprietary"]');

        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('get');

        $this->getScanComposerFile('Proprietary', $client)->scan('Foo_Bar');
        $this->assertSame([], $this->messages);
    }

    public function testScanIgnoresInvalidCachedLicenseList()
    {
        mkdir(dirname($this->varFolder . self::CACHE_FILE));
        file_put_contents($this->varFolder . self::CACHE_FILE, '[true]');

        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('get')->willReturn(new Response(200, [], self::LICENSE_LIST));

        $this->getScanComposerFile('MIT', $client)->scan('Foo_Bar');
        $this->assertSame([], $this->messages);
        $this->assertSame(
            ['OSL-3.0', 'MIT', '1'],
            json_decode((string)file_get_contents($this->varFolder . self::CACHE_FILE), true)
        );
    }

    public function testScanIgnoresSymlinkedCacheFile()
    {
        $outsideFile = $this->createTemporaryFolder(['outside.json' => '["Proprietary"]']) . '/outside.json';
        mkdir(dirname($this->varFolder . self::CACHE_FILE));
        symlink($outsideFile, $this->varFolder . self::CACHE_FILE);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('get')->willReturn(new Response(200, [], self::LICENSE_LIST));

        $this->getScanComposerFile('Proprietary', $client)->scan('Foo_Bar');

        $this->assertSame([
            ['Composer file mentions unsupported license: Proprietary', MessageGroupLabels::GROUP_COMPOSER_ISSUES],
        ], $this->messages);
        $this->assertSame('["Proprietary"]', file_get_contents($outsideFile));
        $this->assertTrue(is_link($this->varFolder . self::CACHE_FILE));
    }

    public function testScanIgnoresSymlinkedCacheFolder()
    {
        $outsideFolder = $this->createTemporaryFolder();
        symlink($outsideFolder, dirname($this->varFolder . self::CACHE_FILE));

        $this->getScanComposerFile('MIT')->scan('Foo_Bar');

        $this->assertSame([], $this->messages);
        $this->assertSame(['.', '..'], scandir($outsideFolder));
    }

    public function testScanSkipsLicenseCheckWhenFetchFails()
    {
        $client = $this->createMock(Client::class);
        $client->method('get')->willThrowException(new TransferException('Connection timed out'));

        $this->getScanComposerFile('Proprietary', $client)->scan('Foo_Bar');

        $this->assertSame([[self::FETCH_WARNING, MessageGroupLabels::GROUP_COMPOSER_ISSUES]], $this->messages);
        $this->assertFileDoesNotExist($this->varFolder . self::CACHE_FILE);
    }

    /**
     * @dataProvider getLicenseListResponses
     */
    #[DataProvider('getLicenseListResponses')]
    public function testLicenseListIsFetchedOncePerInstance(bool $fails, array $expectedMessages)
    {
        $composerFiles = [];
        foreach (['Foo_Bar' => 'Proprietary', 'Foo_Baz' => true] as $moduleName => $license) {
            $composerFile = $this->createMock(ComposerFile::class);
            $composerFile->method('get')->with('license')->willReturn($license);
            $composerFiles[$moduleName] = $composerFile;
        }

        $composerFileProvider = $this->createMock(ComposerFileProvider::class);
        $composerFileProvider->method('getComposerFileByModuleName')
            ->willReturnCallback(fn (string $moduleName) => $composerFiles[$moduleName]);

        $messageBucket = $this->createMock(MessageBucket::class);
        $messageBucket->method('add')->willReturnCallback(function (string $message, string $group) {
            $this->messages[] = $message;
        });

        $client = $this->createMock(Client::class);
        if ($fails) {
            $client->expects($this->once())->method('get')->willThrowException(new TransferException('Timeout'));
        } else {
            $client->expects($this->once())->method('get')->willReturn(new Response(200, [], self::LICENSE_LIST));
        }

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->expects($this->once())->method('create')->willReturn($client);

        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getPath')->willReturn($this->varFolder);

        $cacheFile = $this->createMock(CacheFile::class);
        $cacheFile->expects($this->once())->method('read')->willReturn(null);
        $cacheFile->expects($fails ? $this->never() : $this->once())->method('write')->willReturn(true);

        $scanComposerFile = new ScanComposerFile(
            $composerFileProvider,
            $messageBucket,
            $clientFactory,
            $directoryList,
            $cacheFile
        );

        $scanComposerFile->scan('Foo_Bar');
        $scanComposerFile->scan('Foo_Baz');
        $scanComposerFile->scan('Foo_Bar');

        $this->assertSame($expectedMessages, $this->messages);
    }

    public static function getLicenseListResponses(): array
    {
        return [
            'failure' => [true, [
                self::FETCH_WARNING,
                'Composer file mentions an invalid license',
            ]],
            'success' => [false, [
                'Composer file mentions unsupported license: Proprietary',
                'Composer file mentions an invalid license',
                'Composer file mentions unsupported license: Proprietary',
            ]],
        ];
    }

    /**
     * @dataProvider getInvalidLicenseLists
     */
    #[DataProvider('getInvalidLicenseLists')]
    public function testScanSkipsLicenseCheckWithInvalidLicenseList(string $licenseList)
    {
        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $licenseList));

        $this->getScanComposerFile(['Proprietary', true], $client)->scan('Foo_Bar');

        $this->assertSame([
            [self::FETCH_WARNING, MessageGroupLabels::GROUP_COMPOSER_ISSUES],
            ['Composer file mentions an invalid license', MessageGroupLabels::GROUP_COMPOSER_ISSUES],
        ], $this->messages);
        $this->assertFileDoesNotExist($this->varFolder . self::CACHE_FILE);
    }

    public static function getInvalidLicenseLists(): array
    {
        return [
            ['not json'],
            ['"licenses"'],
            ['{"foo":[]}'],
            ['{"licenses":"MIT"}'],
            ['{"licenses":[]}'],
            ['{"licenses":["MIT"]}'],
            ['{"licenses":[{"licenseId":true}]}'],
            ['{"licenses":[{"licenseId":"MIT"},{"name":"No id"}]}'],
        ];
    }

    private function getScanComposerFile(mixed $license, ?Client $client = null): ScanComposerFile
    {
        $composerFile = $this->createMock(ComposerFile::class);
        $composerFile->method('get')->with('license')->willReturn($license);

        $composerFileProvider = $this->createMock(ComposerFileProvider::class);
        $composerFileProvider->method('getComposerFileByModuleName')->with('Foo_Bar')->willReturn($composerFile);

        $messageBucket = $this->createMock(MessageBucket::class);
        $messageBucket->method('add')->willReturnCallback(function (string $message, string $group) {
            $this->messages[] = [$message, $group];
        });

        if (null === $client) {
            $client = $this->createMock(Client::class);
            $client->method('get')->willReturn(new Response(200, [], self::LICENSE_LIST));
        }

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->method('create')->willReturn($client);

        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getPath')->with(DirectoryList::VAR_DIR)->willReturn($this->varFolder);

        return new ScanComposerFile(
            $composerFileProvider,
            $messageBucket,
            $clientFactory,
            $directoryList,
            new CacheFile(new FileDriver())
        );
    }
}
