<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use ReflectionException;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Util\ModuleInfo;

class Scan
{
    private ModuleInfo $moduleInfo;
    private ComponentDetectorList $componentDetectorList;
    private ScanModuleXmlDependencies $scanModuleXmlDependencies;
    private ScanDeprecatedClasses $scanDeprecatedClasses;
    private ScanComposerRequirements $scanComposerRequirements;
    
    /**
     * Scan constructor.
     *
     * @param ModuleInfo $moduleInfo
     * @param ComponentDetectorList $componentDetectorList
     * @param ScanModuleXmlDependencies $scanModuleXmlDependencies
     * @param ScanDeprecatedClasses $scanDeprecatedClasses
     * @param ScanComposerRequirements $scanComposerRequirements
     */
    public function __construct(
        ModuleInfo $moduleInfo,
        ComponentDetectorList $componentDetectorList,
        ScanModuleXmlDependencies $scanModuleXmlDependencies,
        ScanDeprecatedClasses $scanDeprecatedClasses,
        ScanComposerRequirements $scanComposerRequirements
    ) {
        $this->moduleInfo = $moduleInfo;
        $this->componentDetectorList = $componentDetectorList;
        $this->scanModuleXmlDependencies = $scanModuleXmlDependencies;
        $this->scanDeprecatedClasses = $scanDeprecatedClasses;
        $this->scanComposerRequirements = $scanComposerRequirements;
    }
    
    /**
     * @throws ReflectionException
     */
    public function scan(string $moduleName)
    {
        if ($this->moduleInfo->isKnown($moduleName) === false) {
            $message = sprintf('Module "%s" is unknown', $moduleName);
            throw new InvalidArgumentException($message);
        }
        
        $components = $this->componentDetectorList->getComponentsByModuleName($moduleName);
        // @todo: Remove current module from components list array_filter with callback
        
        $this->scanModuleXmlDependencies->scan($moduleName, $components);
        $this->scanComposerRequirements->scan($moduleName, $components);
        $this->scanDeprecatedClasses->scan($moduleName);
    }
}
