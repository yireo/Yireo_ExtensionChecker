<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Util;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

class CacheFile
{
    public function __construct(
        private FileDriver $fileDriver
    ) {
    }

    public function read(string $file): ?string
    {
        if (false === $this->isSafeFolder($this->fileDriver->getParentDirectory($file))
            || false === $this->isSafeFile($file)
            || false === $this->fileDriver->isFile($file)) {
            return null;
        }

        try {
            return $this->fileDriver->fileGetContents($file);
        } catch (FileSystemException $exception) {
            return null;
        }
    }

    public function write(string $file, string $contents): bool
    {
        $folder = $this->fileDriver->getParentDirectory($file);
        $temporaryFile = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';

        try {
            if (false === $this->isSafeFolder($folder)) {
                return false;
            }

            if (false === $this->fileDriver->isExists($folder)) {
                $this->fileDriver->createDirectory($folder);
            }

            if (false === $this->isSafeFolder($folder) || false === $this->isSafeFile($file)) {
                return false;
            }

            $resource = $this->fileDriver->fileOpen($temporaryFile, 'x');
            $this->fileDriver->fileWrite($resource, $contents);
            $this->fileDriver->fileClose($resource);
            $this->fileDriver->rename($temporaryFile, $file);
        } catch (FileSystemException $exception) {
            if (false === is_link($temporaryFile) && $this->fileDriver->isFile($temporaryFile)) {
                $this->fileDriver->deleteFile($temporaryFile);
            }

            return false;
        }

        return true;
    }

    private function isSafeFolder(string $folder): bool
    {
        if (is_link($folder)) {
            return false;
        }

        return false === $this->fileDriver->isExists($folder) || $this->fileDriver->isDirectory($folder);
    }

    private function isSafeFile(string $file): bool
    {
        if (is_link($file)) {
            return false;
        }

        return false === $this->fileDriver->isExists($file) || $this->fileDriver->isFile($file);
    }
}
