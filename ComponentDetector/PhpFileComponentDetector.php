<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\File\FileCollectorInterface;
use Yireo\ExtensionChecker\Util\ModuleInfo;

/**
 * Detect components from a module its PHP files
 */
class PhpFileComponentDetector implements ComponentDetectorInterface
{
    private FileCollectorInterface $fileCollector;
    private ModuleInfo $moduleInfo;
    private FileReadFactory $fileReadFactory;
    private array $phpFileParsers;

    public function __construct(
        FileCollectorInterface $fileCollector,
        ModuleInfo $moduleInfo,
        FileReadFactory $fileReadFactory,
        array $phpFileParsers = []
    ) {
        $this->fileCollector = $fileCollector;
        $this->moduleInfo = $moduleInfo;
        $this->fileReadFactory = $fileReadFactory;
        $this->phpFileParsers = $phpFileParsers;
    }

    /**
     * @param string $moduleName
     * @return Component[]
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        $moduleFolder = $this->moduleInfo->getModuleFolder($moduleName);
        $files = $this->fileCollector->getFilesFromModuleFolder($moduleFolder);
        $components = [];

        foreach ($files as $file) {
            $fileContents = $this->getFileContents($file);
            foreach ($this->phpFileParsers as $phpFileParser) {
                $components = array_merge($components, $phpFileParser->getComponents($fileContents));
            }
        }

        return $components;
    }

    /**
     * @param string $file
     * @return string
     */
    private function getFileContents(string $file): string
    {
        $fileRead = $this->fileReadFactory->create($file, 'file');
        return $fileRead->readAll();
    }
}
