<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Yireo\ExtensionChecker\ComponentDetector\ModuleXmlComponentDetector;
use Yireo\ExtensionChecker\Message\MessageBucket;

class ScanModuleXmlDependencies
{
    private ModuleXmlComponentDetector $moduleXmlComponentDetector;
    private MessageBucket $messageBucket;
    
    /**
     * @param ModuleXmlComponentDetector $moduleXmlComponentDetector
     * @param MessageBucket $messageBucket
     */
    public function __construct(
        ModuleXmlComponentDetector $moduleXmlComponentDetector,
        MessageBucket $messageBucket
    ) {
        $this->moduleXmlComponentDetector = $moduleXmlComponentDetector;
        $this->messageBucket = $messageBucket;
    }
    
    /**
     * @param string $moduleName
     * @param array $components
     * @return void
     */
    public function scan(string $moduleName, array $components)
    {
        $moduleXmlComponents = $this->moduleXmlComponentDetector->getComponentsByModuleName($moduleName);
        foreach ($components as $component) {
            $isComponentFoundInModuleXml = false;
            foreach ($moduleXmlComponents as $moduleXmlComponent) {
                if ($component->getComponentName() === $moduleXmlComponent->getComponentName()) {
                    $isComponentFoundInModuleXml = true;
                    break;
                }
            }
            
            if (!$isComponentFoundInModuleXml) {
                $this->messageBucket->addWarning(sprintf(
                    'Dependency "%s" not found module.xml',
                    $component->getComponentName()
                ));
            }
        }
        
        foreach ($moduleXmlComponents as $moduleXmlComponent) {
            $isModuleXmlComponentFoundInDetectedComponents = false;
            foreach ($components as $component) {
                if ($component->getComponentName() === $moduleXmlComponent->getComponentName()) {
                    $isModuleXmlComponentFoundInDetectedComponents = true;
                    break;
                }
            }
            
            if (!$isModuleXmlComponentFoundInDetectedComponents) {
                $this->messageBucket->addWarning(sprintf(
                    'Dependency "%s" from module.xml possibly not needed.',
                    $moduleXmlComponent->getComponentName()
                ));
            }
        }
    }
}
