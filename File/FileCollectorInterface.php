<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\File;

interface FileCollectorInterface
{
    /**
     * @param string $moduleFolder
     * @return array
     */
    public function getFilesFromModuleFolder(string $moduleFolder): array;
}
