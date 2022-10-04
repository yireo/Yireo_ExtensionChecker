<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\XmlLayout;

use Magento\Framework\Filesystem\Directory\ReadFactory as DirectoryReadFactory;
use Symfony\Component\Finder\FinderFactory;
use Yireo\ExtensionChecker\Exception\NoFilesFoundException;

class FileCollector
{
    private FinderFactory $finderFactory;
    private DirectoryReadFactory $directoryReadFactory;

    /**
     * ClassNameCollector constructor.
     * @param FinderFactory $finderFactory
     */
    public function __construct(
        FinderFactory $finderFactory,
        DirectoryReadFactory $directoryReadFactory
    ) {
        $this->finderFactory = $finderFactory;
        $this->directoryReadFactory = $directoryReadFactory;
    }

    /**
     * @param string $folder
     * @return array
     * @throws NoFilesFoundException
     */
    public function getFilesFromModuleFolder(string $moduleFolder): array
    {
        $directoryRead = $this->directoryReadFactory->create($moduleFolder);
        $searchFolder = $moduleFolder.'/view';
        if (!$directoryRead->isExist($searchFolder)) {
            return [];
        }

        $finder = $this->finderFactory->create();
        $finder->files()->in($searchFolder);

        $files = [];
        foreach ($finder as $file) {
            if (!preg_match('/\.xml$/', $file->getRelativePathname())) {
                continue;
            }

            $files[] = $file->getRealPath();
        }

        return $files;
    }
}
