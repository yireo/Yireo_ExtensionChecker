<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

use Symfony\Component\Finder\FinderFactory;
use Yireo\ExtensionChecker\Exception\NoFilesFoundException;

class FileCollector
{
    /**
     * @var FinderFactory
     */
    private $finderFactory;

    /**
     * ClassNameCollector constructor.
     * @param FinderFactory $finderFactory
     */
    public function __construct(
        FinderFactory $finderFactory
    ) {
        $this->finderFactory = $finderFactory;
    }

    /**
     * @param string $folder
     * @return array
     * @throws NoFilesFoundException
     */
    public function getFilesFromFolder(string $folder): array
    {
        $finder = $this->finderFactory->create();
        $finder->files()->in($folder);

        $files = [];
        foreach ($finder as $file) {
            // Skip non-PHP files
            if (!preg_match('/\.php$/', $file->getRelativePathname())) {
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
