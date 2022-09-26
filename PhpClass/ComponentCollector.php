<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

use ReflectionException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Exception\ComponentNotFoundException;

class ComponentCollector
{
    private ClassInspector $classInspector;
    
    public function __construct(
        ClassInspector $classInspector
    ) {
        $this->classInspector = $classInspector;
    }
    
    /**
     * @param array $classNames
     * @return Component[]
     * @todo Move to another class
     */
    public function getComponentsByClasses(array $classNames): array
    {
        $components = [];
        foreach ($classNames as $className) {
            try {
                $component = $this->classInspector->setClassName($className)->getComponentByClass();
            } catch (ReflectionException|ComponentNotFoundException $e) {
                continue;
            }
            
            $components[] = $component;
        }
        
        return $components;
    }
}