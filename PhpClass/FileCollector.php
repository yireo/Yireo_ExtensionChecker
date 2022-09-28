<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

use Symfony\Component\Finder\Finder;
use Yireo\ExtensionChecker\Exception\NoFilesFoundException;

class FileCollector
{
    /**
     * @var Finder
     */
    private $finder;

    /**
     * ClassNameCollector constructor.
     * @param Finder $finder
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

            if (str_contains($file->getRelativePathname(), 'Test.php')) {
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
