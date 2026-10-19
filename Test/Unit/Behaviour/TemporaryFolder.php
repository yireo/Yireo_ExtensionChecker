<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Behaviour;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait TemporaryFolder
{
    private array $temporaryFolders = [];

    /**
     * @param array<string, string> $files Relative file paths mapped to their contents
     * @param string|null $folderName Fixed folder name to use instead of a random one
     */
    private function createTemporaryFolder(array $files = [], ?string $folderName = null): string
    {
        $folder = sys_get_temp_dir() . '/' . ($folderName ?? 'yireo-extensionchecker-' . uniqid('', true));
        $this->removeFolder($folder);
        mkdir($folder, 0777, true);
        $this->temporaryFolders[] = $folder;

        foreach ($files as $file => $contents) {
            $path = $folder . '/' . $file;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, $contents);
        }

        return $folder;
    }

    private function removeTemporaryFolders(): void
    {
        foreach ($this->temporaryFolders as $folder) {
            $this->removeFolder($folder);
        }

        $this->temporaryFolders = [];
    }

    private function removeFolder(string $folder): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($folder);
    }

    protected function tearDown(): void
    {
        $this->removeTemporaryFolders();
        parent::tearDown();
    }
}
