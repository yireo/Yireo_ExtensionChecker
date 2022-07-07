<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Symfony\Component\Finder\Finder;
use Yireo\ExtensionChecker\Exception\NoFilesFoundException;

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
     * @return array
     * @throws NoFilesFoundException
     */
    public function getFilesFromFolder(string $folder): array
    {
        $this->finder->files()->in($folder);

        $files = [];
        foreach ($this->finder as $file) {
            if (!preg_match('/\.php$/', $file->getRelativePathname())) {
                continue;
            }

            if (strstr($file->getRelativePathname(), 'Test/')) {
                continue;
            }

            $files[] = $file->getRealPath();
        }
        
        if (empty($files)) {
            throw new NoFilesFoundException('No files found in folder "' . $folder . '"');
        }

        return $files;
    }
}
