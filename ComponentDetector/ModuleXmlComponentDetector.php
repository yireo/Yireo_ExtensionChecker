<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\Util\ModuleInfo;

/**
 * Detect components from a module its XML layout files
 */
class ModuleXmlComponentDetector implements ComponentDetectorInterface
{
    private ModuleInfo $moduleInfo;
    private ComponentFactory $componentFactory;
    
    public function __construct(
        ModuleInfo $moduleInfo,
        ComponentFactory $componentFactory
    ) {
        $this->moduleInfo = $moduleInfo;
        $this->componentFactory = $componentFactory;
    }
    
    /**
     * @param string $moduleName
     * @return Component[]
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        $components = [];
        $moduleInfo = $this->moduleInfo->getModuleInfo($moduleName);
        foreach ($moduleInfo['sequence'] as $sequenceModuleName) {
            $components[] = $this->componentFactory->createByModuleName($sequenceModuleName);
        }
        
        return $components;
    }
}
