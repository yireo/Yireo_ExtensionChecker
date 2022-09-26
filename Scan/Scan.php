<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use ReflectionException;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Util\ModuleInfo;

class Scan
{
    private ModuleInfo $moduleInfo;
    private ComponentDetectorList $componentDetectorList;
    private ScanModuleXmlDependencies $scanModuleXmlDependencies;
    private ScanDeprecatedClasses $scanDeprecatedClasses;
    private ScanComposerRequirements $scanComposerRequirements;
    private RuntimeConfig $runtimeConfig;

    /**
     * Scan constructor.
     *
     * @param ModuleInfo $moduleInfo
     * @param ComponentDetectorList $componentDetectorList
     * @param ScanModuleXmlDependencies $scanModuleXmlDependencies
     * @param ScanDeprecatedClasses $scanDeprecatedClasses
     * @param ScanComposerRequirements $scanComposerRequirements
     * @param RuntimeConfig $runtimeConfig
     */
    public function __construct(
        ModuleInfo $moduleInfo,
        ComponentDetectorList $componentDetectorList,
        ScanModuleXmlDependencies $scanModuleXmlDependencies,
        ScanDeprecatedClasses $scanDeprecatedClasses,
        ScanComposerRequirements $scanComposerRequirements,
        RuntimeConfig $runtimeConfig
    ) {
        $this->moduleInfo = $moduleInfo;
        $this->componentDetectorList = $componentDetectorList;
        $this->scanModuleXmlDependencies = $scanModuleXmlDependencies;
        $this->scanDeprecatedClasses = $scanDeprecatedClasses;
        $this->scanComposerRequirements = $scanComposerRequirements;
        $this->runtimeConfig = $runtimeConfig;
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
        $components = array_filter($components, fn($component) => $component->getComponentName() !== $moduleName);

        $this->scanModuleXmlDependencies->scan($moduleName, $components);
        $this->scanComposerRequirements->scan($moduleName, $components);

        if (!$this->runtimeConfig->isHideDeprecated()) {
            $this->scanDeprecatedClasses->scan($moduleName);
        }
    }
}
