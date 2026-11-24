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

    public function read(string $trustedFolder, string $relativePath): ?string
    {
        if (false === $this->isSafePath($trustedFolder, $relativePath)) {
            return null;
        }

        $file = $this->getPath($trustedFolder, $relativePath);

        try {
            if (false === $this->fileDriver->isFile($file)) {
                return null;
            }

            return $this->fileDriver->fileGetContents($file);
        } catch (FileSystemException $exception) {
            return null;
        }
    }

    public function write(string $trustedFolder, string $relativePath, string $contents): bool
    {
        if (false === $this->isSafePath($trustedFolder, $relativePath)) {
            return false;
        }

        $file = $this->getPath($trustedFolder, $relativePath);
        $temporaryFile = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';

        try {
            $folder = $this->fileDriver->getParentDirectory($file);
            if (false === $this->fileDriver->isExists($folder)) {
                $this->fileDriver->createDirectory($folder);
            }

            if (false === $this->isSafePath($trustedFolder, $relativePath)) {
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

    private function getPath(string $trustedFolder, string $relativePath): string
    {
        return rtrim($trustedFolder, '/') . '/' . $relativePath;
    }

    private function isSafePath(string $trustedFolder, string $relativePath): bool
    {
        $components = explode('/', $relativePath);
        $path = rtrim($trustedFolder, '/');
        $lastIndex = count($components) - 1;

        try {
            foreach ($components as $index => $component) {
                if ($component === '' || $component === '.' || $component === '..') {
                    return false;
                }

                $path .= '/' . $component;
                if (is_link($path)) {
                    return false;
                }

                if (false === $this->fileDriver->isExists($path)) {
                    continue;
                }

                $isValidType = $index === $lastIndex
                    ? $this->fileDriver->isFile($path)
                    : $this->fileDriver->isDirectory($path);
                if (false === $isValidType) {
                    return false;
                }
            }
        } catch (FileSystemException $exception) {
            return false;
        }

        return true;
    }
}
