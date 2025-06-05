<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\ValidatorException;
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
    private ScanComposerFile $scanComposerFile;

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
        ScanComposerFile $scanComposerFile,
        RuntimeConfig $runtimeConfig
    ) {
        $this->moduleInfo = $moduleInfo;
        $this->componentDetectorList = $componentDetectorList;
        $this->scanModuleXmlDependencies = $scanModuleXmlDependencies;
        $this->scanDeprecatedClasses = $scanDeprecatedClasses;
        $this->scanComposerRequirements = $scanComposerRequirements;
        $this->scanComposerFile = $scanComposerFile;
        $this->runtimeConfig = $runtimeConfig;
    }

    /**
     * @param string $moduleName
     * @param string $modulePath
     * @throws FileSystemException
     * @throws NotFoundException
     * @throws ValidatorException
     */
    public function scan(string $moduleName, string $modulePath)
    {
        if (!empty($moduleName) && $this->moduleInfo->isKnown($moduleName) === false) {
            $message = sprintf('Module "%s" is unknown', $moduleName);
            throw new InvalidArgumentException($message);
        }

        if (empty($moduleName) && !empty($modulePath)) {
            $moduleName = $this->moduleInfo->getModuleNameFromPath($modulePath);
        }

        $components = $this->componentDetectorList->getComponentsByModuleName($moduleName);
        $this->scanModuleXmlDependencies->scan($moduleName, $components);
        $this->scanComposerRequirements->scan($moduleName, $components);

        if (!$this->runtimeConfig->isHideDeprecated()) {
            $this->scanDeprecatedClasses->scan($moduleName);
        }

        if (!$this->runtimeConfig->isSkipLicenseCheck()) {
            $this->scanComposerFile->scan($moduleName);
        }
    }
}
