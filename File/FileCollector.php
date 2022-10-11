<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\File;

use Magento\Framework\Filesystem\Directory\ReadFactory as DirectoryReadFactory;
use Symfony\Component\Finder\FinderFactory;
use Yireo\ExtensionChecker\Exception\NoFilesFoundException;

class FileCollector implements FileCollectorInterface
{
    private FinderFactory $finderFactory;
    private DirectoryReadFactory $directoryReadFactory;
    private string $fileSuffix;
    private string $fileFolder;

    /**
     * ClassNameCollector constructor.
     * @param FinderFactory $finderFactory
     * @param DirectoryReadFactory $directoryReadFactory
     * @param string $fileSuffix
     * @param string $fileFolder
     */
    public function __construct(
        FinderFactory $finderFactory,
        DirectoryReadFactory $directoryReadFactory,
        string $fileSuffix = '',
        string $fileFolder = ''
    ) {
        $this->finderFactory = $finderFactory;
        $this->directoryReadFactory = $directoryReadFactory;
        $this->fileSuffix = $fileSuffix;
        $this->fileFolder = $fileFolder;
    }

    /**
     * @param string $folder
     * @return array
     * @throws NoFilesFoundException
     */
    public function getFilesFromModuleFolder(string $moduleFolder): array
    {
        $directoryRead = $this->directoryReadFactory->create($moduleFolder);
        $searchFolder = $moduleFolder.'/'.$this->fileFolder;
        if (!$directoryRead->isExist($searchFolder)) {
            return [];
        }

        $finder = $this->finderFactory->create();
        $finder->files()->in($searchFolder);

        $files = [];
        foreach ($finder as $file) {
            if (!preg_match('/'.$this->fileSuffix.'$/', $file->getRelativePathname())) {
                continue;
            }

            $files[] = $file->getRealPath();
        }

        return $files;
    }
}
