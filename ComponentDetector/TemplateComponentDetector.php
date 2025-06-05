<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\File\FileCollectorInterface;
use Yireo\ExtensionChecker\Util\ModuleInfo;
use Yireo\ExtensionChecker\ComponentCollector\TemplateComponentCollector;

/**
 * Detect components from a module its PHTML template files
 */
class TemplateComponentDetector implements ComponentDetectorInterface
{
    private FileCollectorInterface $fileCollector;
    private ModuleInfo $moduleInfo;
    private TemplateComponentCollector $componentCollector;

    public function __construct(
        FileCollectorInterface $fileCollector,
        ModuleInfo $moduleInfo,
        TemplateComponentCollector $componentCollector
    ) {
        $this->fileCollector = $fileCollector;
        $this->moduleInfo = $moduleInfo;
        $this->componentCollector = $componentCollector;
    }

    /**
     * @param string $moduleName
     * @return Component[]
     * @throws FileSystemException
     * @throws NotFoundException
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        $moduleFolder = $this->moduleInfo->getModuleFolder($moduleName);
        $files = $this->fileCollector->getFilesFromModuleFolder($moduleFolder);
        $components = [];

        foreach ($files as $file) {
            $components = array_merge($components, $this->componentCollector->getComponentsFromFile($file));
        }

        return $components;
    }
}
