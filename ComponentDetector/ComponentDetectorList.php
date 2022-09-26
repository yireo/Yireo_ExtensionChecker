<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

class ComponentDetectorList
{
    /**
     * @var ComponentDetectorInterface[]
     */
    private array $componentDetectors;
    
    /**
     * @param ComponentDetectorInterface[] $componentDetectors
     */
    public function __construct(
        array $componentDetectors = []
    ) {
        $this->componentDetectors = $componentDetectors;
    }
    
    /**
     * @return ComponentDetectorInterface[]
     */
    public function getComponentDetectors(): array
    {
        return $this->componentDetectors;
    }
    
    /**
     * @param string $moduleName
     * @return Component
     */
    public function getComponentsByModuleName(string $moduleName)
    {
        $components = [];
        foreach ($this->componentDetectors as $componentDetector) {
            $components = array_merge($components, $componentDetector->getComponentsByModuleName($moduleName));
        }
        
        // @todo: Make sure all components are unique
        return $components;
    }
}
