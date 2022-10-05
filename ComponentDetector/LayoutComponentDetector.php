<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Util\ModuleInfo;
use Yireo\ExtensionChecker\XmlLayout\FileCollector;
use Yireo\ExtensionChecker\XmlLayout\ComponentCollector;

/**
 * Detect components from a module its XML layout files
 */
class LayoutComponentDetector implements ComponentDetectorInterface
{
    private FileCollector $fileCollector;
    private ModuleInfo $moduleInfo;
    private ComponentCollector $componentCollector;

    public function __construct(
        FileCollector $fileCollector,
        ModuleInfo $moduleInfo,
        ComponentCollector $componentCollector
    ) {
        $this->fileCollector = $fileCollector;
        $this->moduleInfo = $moduleInfo;
        $this->componentCollector = $componentCollector;
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
            $components = array_merge($components, $this->componentCollector->getComponentsFromFile($file));
        }

        return $components;
    }
}
