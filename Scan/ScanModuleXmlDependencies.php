<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\ComponentDetector\ModuleXmlComponentDetector;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Util\CheckerConfiguration;

class ScanModuleXmlDependencies
{
    private ModuleXmlComponentDetector $moduleXmlComponentDetector;
    private MessageBucket $messageBucket;
    private RuntimeConfig $runtimeConfig;
    private CheckerConfiguration $checkerConfiguration;

    /**
     * @param ModuleXmlComponentDetector $moduleXmlComponentDetector
     * @param MessageBucket $messageBucket
     */
    public function __construct(
        ModuleXmlComponentDetector $moduleXmlComponentDetector,
        MessageBucket $messageBucket,
        RuntimeConfig $runtimeConfig,
        CheckerConfiguration $checkerConfiguration
    ) {
        $this->moduleXmlComponentDetector = $moduleXmlComponentDetector;
        $this->messageBucket = $messageBucket;
        $this->runtimeConfig = $runtimeConfig;
        $this->checkerConfiguration = $checkerConfiguration;
    }

    /**
     * @param string $moduleName
     * @param Component[] $components
     * @return void
     */
    public function scan(string $moduleName, array $components)
    {
        $moduleXmlComponents = $this->moduleXmlComponentDetector->getComponentsByModuleName($moduleName);
        foreach ($components as $component) {
            if ($component->getComponentType() !== 'module') {
                continue;
            }

            $isComponentFoundInModuleXml = false;
            foreach ($moduleXmlComponents as $moduleXmlComponent) {
                if ($component->getComponentName() === $moduleXmlComponent->getComponentName()) {
                    $isComponentFoundInModuleXml = true;
                    break;
                }
            }

            if (!$isComponentFoundInModuleXml) {
                $message = 'Module "'.$component->getComponentName().'" has no module.xml entry';
                $this->messageBucket->add($message, MessageGroupLabels::GROUP_MISSING_MODULEXML_DEP, '', $moduleName);
            }
        }

        foreach ($moduleXmlComponents as $moduleXmlComponent) {
            if ($this->runtimeConfig->isModuleWhitelisted($moduleXmlComponent->getComponentName())) {
                break;
            }

            $isModuleXmlComponentFoundInDetectedComponents = false;
            foreach ($components as $component) {
                if ($this->checkerConfiguration->isIgnored($moduleName, $moduleXmlComponent->getComponentName())) {
                    $isModuleXmlComponentFoundInDetectedComponents = true;
                    break;
                }

                if ($component->getComponentName() === $moduleXmlComponent->getComponentName()) {
                    $isModuleXmlComponentFoundInDetectedComponents = true;
                    break;
                }
            }

            if (false === $isModuleXmlComponentFoundInDetectedComponents
                && false === $this->runtimeConfig->isHideNeedless()) {
                $message = 'Module "%1" is possibly not needed in module.xml';
                $this->messageBucket->add(
                    (string)__($message, $moduleXmlComponent->getComponentName()),
                    MessageGroupLabels::GROUP_UNNECESSARY_MODULEXML_DEP,
                    '',
                    $moduleName
                );
            }
        }
    }
}
