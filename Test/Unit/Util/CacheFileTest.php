<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Util;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;
use Yireo\ExtensionChecker\Util\CacheFile;

class CacheFileTest extends TestCase
{
    use TemporaryFolder;

    public function testWriteCreatesFoldersAndReadReturnsContents()
    {
        $varFolder = $this->createTemporaryFolder();
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($varFolder, 'cache/nested/file.json'));
        $this->assertTrue($cacheFile->write($varFolder, 'cache/nested/file.json', 'foobar'));
        $this->assertSame('foobar', $cacheFile->read($varFolder, 'cache/nested/file.json'));
        $this->assertTrue($cacheFile->write($varFolder, 'cache/nested/file.json', 'barfoo'));
        $this->assertSame('barfoo', $cacheFile->read($varFolder, 'cache/nested/file.json'));
        $this->assertSame(
            ['file.json'],
            array_values(array_diff((array)scandir($varFolder . '/cache/nested'), ['.', '..']))
        );
    }

    public function testSymlinkedTrustedFolderIsAllowed()
    {
        $realVarFolder = $this->createTemporaryFolder();
        $varFolder = $this->createTemporaryFolder() . '/var';
        symlink($realVarFolder, $varFolder);
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertTrue($cacheFile->write($varFolder, 'cache/file.json', 'foobar'));
        $this->assertSame('foobar', $cacheFile->read($varFolder, 'cache/file.json'));
        $this->assertSame('foobar', file_get_contents($realVarFolder . '/cache/file.json'));
    }

    public function testSymlinkedCacheFileIsNotReadOrWritten()
    {
        $outsideFile = $this->createTemporaryFolder(['secret.json' => 'secret']) . '/secret.json';
        $varFolder = $this->createTemporaryFolder();
        mkdir($varFolder . '/cache');
        symlink($outsideFile, $varFolder . '/cache/file.json');
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($varFolder, 'cache/file.json'));
        $this->assertFalse($cacheFile->write($varFolder, 'cache/file.json', 'foobar'));
        $this->assertSame('secret', file_get_contents($outsideFile));
        $this->assertTrue(is_link($varFolder . '/cache/file.json'));
    }

    /**
     * @dataProvider getSymlinkedFolders
     */
    #[DataProvider('getSymlinkedFolders')]
    public function testCacheFileBelowSymlinkedFolderIsNotReadOrWritten(string $symlinkedFolder, string $file)
    {
        $outsideFolder = $this->createTemporaryFolder();
        $varFolder = $this->createTemporaryFolder();
        if (dirname($symlinkedFolder) !== '.') {
            mkdir($varFolder . '/' . dirname($symlinkedFolder), 0777, true);
        }

        symlink($outsideFolder, $varFolder . '/' . $symlinkedFolder);
        $outsideFile = $outsideFolder . substr($file, strlen($symlinkedFolder));
        if (!is_dir(dirname($outsideFile))) {
            mkdir(dirname($outsideFile), 0777, true);
        }

        file_put_contents($outsideFile, 'secret');
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($varFolder, $file));
        $this->assertFalse($cacheFile->write($varFolder, $file, 'foobar'));
        $this->assertFalse($cacheFile->write($varFolder, $file . '.other', 'foobar'));
        $this->assertSame('secret', file_get_contents($outsideFile));
        $this->assertFileDoesNotExist($outsideFile . '.other');
    }

    public static function getSymlinkedFolders(): array
    {
        return [
            'immediate parent' => ['composer_home', 'composer_home/file.json'],
            'top-level intermediate folder' => ['yireo_extensionchecker', 'yireo_extensionchecker/nested/deeper/file.json'],
            'nested intermediate folder' => ['yireo_extensionchecker/nested', 'yireo_extensionchecker/nested/deeper/file.json'],
        ];
    }

    public function testMissingFoldersBelowSymlinkedFolderAreNotCreated()
    {
        $outsideFolder = $this->createTemporaryFolder();
        $varFolder = $this->createTemporaryFolder();
        symlink($outsideFolder, $varFolder . '/yireo_extensionchecker');
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertFalse($cacheFile->write($varFolder, 'yireo_extensionchecker/nested/file.json', 'foobar'));
        $this->assertSame(['.', '..'], scandir($outsideFolder));
    }

    /**
     * @dataProvider getInvalidRelativePaths
     */
    #[DataProvider('getInvalidRelativePaths')]
    public function testInvalidRelativePathsAreRefused(string $relativePath)
    {
        $parentFolder = $this->createTemporaryFolder(['secret.json' => 'secret']);
        mkdir($parentFolder . '/var');
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($parentFolder . '/var', $relativePath));
        $this->assertFalse($cacheFile->write($parentFolder . '/var', $relativePath, 'foobar'));
        $this->assertSame('secret', file_get_contents($parentFolder . '/secret.json'));
    }

    public static function getInvalidRelativePaths(): array
    {
        return [
            ['../secret.json'],
            ['cache/../../secret.json'],
            ['/secret.json'],
            ['cache//file.json'],
            ['./file.json'],
            [''],
        ];
    }

    public function testNonRegularCacheFileOrFolderIsNotReadOrWritten()
    {
        $varFolder = $this->createTemporaryFolder(['cache/file.json/nested' => 'foobar', 'notafolder' => 'foobar']);
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($varFolder, 'cache/file.json'));
        $this->assertFalse($cacheFile->write($varFolder, 'cache/file.json', 'foobar'));
        $this->assertNull($cacheFile->read($varFolder, 'notafolder/file.json'));
        $this->assertFalse($cacheFile->write($varFolder, 'notafolder/file.json', 'foobar'));
        $this->assertSame('foobar', file_get_contents($varFolder . '/notafolder'));
    }
}
