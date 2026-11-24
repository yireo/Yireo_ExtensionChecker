<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Util;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;
use Yireo\ExtensionChecker\Util\CacheFile;

class CacheFileTest extends TestCase
{
    use TemporaryFolder;

    public function testWriteCreatesFolderAndReadReturnsContents()
    {
        $file = $this->createTemporaryFolder() . '/cache/file.json';
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($file));
        $this->assertTrue($cacheFile->write($file, 'foobar'));
        $this->assertSame('foobar', $cacheFile->read($file));
        $this->assertTrue($cacheFile->write($file, 'barfoo'));
        $this->assertSame('barfoo', $cacheFile->read($file));
        $this->assertSame(['file.json'], array_values(array_diff((array)scandir(dirname($file)), ['.', '..'])));
    }

    public function testSymlinkedCacheFileIsNotReadOrWritten()
    {
        $outsideFile = $this->createTemporaryFolder(['secret.json' => 'secret']) . '/secret.json';
        $folder = $this->createTemporaryFolder();
        symlink($outsideFile, $folder . '/file.json');
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($folder . '/file.json'));
        $this->assertFalse($cacheFile->write($folder . '/file.json', 'foobar'));
        $this->assertSame('secret', file_get_contents($outsideFile));
        $this->assertTrue(is_link($folder . '/file.json'));
    }

    public function testCacheFileInSymlinkedFolderIsNotReadOrWritten()
    {
        $outsideFolder = $this->createTemporaryFolder(['file.json' => 'secret']);
        $folder = $this->createTemporaryFolder();
        symlink($outsideFolder, $folder . '/cache');
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($folder . '/cache/file.json'));
        $this->assertFalse($cacheFile->write($folder . '/cache/file.json', 'foobar'));
        $this->assertFalse($cacheFile->write($folder . '/cache/other.json', 'foobar'));
        $this->assertSame('secret', file_get_contents($outsideFolder . '/file.json'));
        $this->assertSame(['file.json'], array_values(array_diff((array)scandir($outsideFolder), ['.', '..'])));
    }

    public function testNonRegularCacheFileOrFolderIsNotReadOrWritten()
    {
        $folder = $this->createTemporaryFolder(['cache/file.json/nested' => 'foobar', 'notafolder' => 'foobar']);
        $cacheFile = new CacheFile(new FileDriver());

        $this->assertNull($cacheFile->read($folder . '/cache/file.json'));
        $this->assertFalse($cacheFile->write($folder . '/cache/file.json', 'foobar'));
        $this->assertNull($cacheFile->read($folder . '/notafolder/file.json'));
        $this->assertFalse($cacheFile->write($folder . '/notafolder/file.json', 'foobar'));
        $this->assertSame('foobar', file_get_contents($folder . '/notafolder'));
    }
}
