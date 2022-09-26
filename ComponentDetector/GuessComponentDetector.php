<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Util\ModuleInfo;

/**
 * Detect components from a module its JS files
 */
class GuessComponentDetector implements ComponentDetectorInterface
{
    private ComponentFactory $componentFactory;
    private ModuleInfo $moduleInfo;
    
    public function __construct(
        ComponentFactory $componentFactory,
        ModuleInfo $moduleInfo
    ) {
        $this->componentFactory = $componentFactory;
        $this->moduleInfo = $moduleInfo;
    }
    
    /**
     * @param string $moduleName
     * @return Component
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        $components = [];
        
        try {
            $moduleFolder = $this->moduleInfo->getModuleFolder($moduleName);
        } catch (ModuleNotFoundException $moduleNotFoundException) {
            $this->addDebug('ModuleNotFoundException: ' . $moduleNotFoundException->getMessage());
            return $components;
        }
        
        if (is_dir($moduleFolder . '/Setup') || is_dir($moduleFolder . '/Block')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Store');
        }
        
        if (is_file($moduleFolder . '/etc/schema.graphqls')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_GraphQl');
        }
        
        if (is_dir($moduleFolder . '/etc/graphql')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_GraphQl');
        }
        
        if (is_dir($moduleFolder . '/etc/frontend')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Store');
        }
        
        if (is_dir($moduleFolder . '/etc/adminhtml')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Backend');
        }
        
        return $components;
    }
}
