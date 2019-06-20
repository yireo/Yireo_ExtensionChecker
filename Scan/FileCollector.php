<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Symfony\Component\Finder\Finder;

/**
 * Class FileCollector
 *
 * @package Yireo\ExtensionChecker\Scan
 */
class FileCollector
{
    /**
     * @var Finder
     */
    private $finder;

    /**
     * ClassCollector constructor.
     *
     */
    public function __construct(
        Finder $finder
    ) {
        $this->finder = $finder;
    }

    /**
     * @param string $folder
     *
     * @return array
     */
    public function getFilesFromFolder(string $folder): array
    {
        $this->finder->files()->in($folder);

        $files = [];
        foreach ($this->finder as $file) {
            if (!preg_match('/\.php$/', $file->getRelativePathname())) {
                continue;
            }

            if (preg_match('/^Test\//', $file->getRelativePathname())) {
                continue;
            }

            $files[] = $file->getRealPath();
        }

        return $files;
    }
}
