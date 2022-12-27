<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\File\FileCollectorInterface;
use Yireo\ExtensionChecker\Util\ModuleInfo;
use Yireo\ExtensionChecker\ComponentCollector\XmlComponentCollector;

/**
 * Detect components from a module its PHTML template files
 */
class XmlComponentDetector implements ComponentDetectorInterface
{
    private FileCollectorInterface $fileCollector;
    private ModuleInfo $moduleInfo;
    private XmlComponentCollector $componentCollector;

    public function __construct(
        FileCollectorInterface $fileCollector,
        ModuleInfo $moduleInfo,
        XmlComponentCollector $componentCollector
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
        $files = $this->fileCollector->getFilesFromModuleFolder($moduleFolder.'/etc');
        $components = [];

        foreach ($files as $file) {
            if (basename($file) === 'module.xml') {
                continue;
            }

            $components = array_merge($components, $this->componentCollector->getComponentsFromFile($file));
        }

        return $components;
    }
}
