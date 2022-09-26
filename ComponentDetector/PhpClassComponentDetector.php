<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use ReflectionException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\PhpClass\ClassNameCollector;
use Yireo\ExtensionChecker\PhpClass\ClassInspector;
use Yireo\ExtensionChecker\PhpClass\ComponentCollector;
use Yireo\ExtensionChecker\PhpClass\ModuleCollector;

/**
 * Detect components from a module its PHP classes
 */
class PhpClassComponentDetector implements ComponentDetectorInterface
{
    private ClassNameCollector $classNameCollector;
    private ClassInspector $classInspector;
    private ModuleCollector $moduleCollector;
    private ComponentCollector $componentCollector;
    
    /**
     * @param ClassNameCollector $classNameCollector
     * @param ClassInspector $classInspector
     * @param ModuleCollector $moduleCollector
     * @param ComponentCollector $componentCollector
     */
    public function __construct(
        ClassNameCollector $classNameCollector,
        ClassInspector $classInspector,
        ModuleCollector $moduleCollector,
        ComponentCollector $componentCollector
    ) {
        $this->classNameCollector = $classNameCollector;
        $this->classInspector = $classInspector;
        $this->moduleCollector = $moduleCollector;
        $this->componentCollector = $componentCollector;
    }
    
    /**
     * @return Component[]
     * @throws ReflectionException
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        $classNames = $this->moduleCollector->getClassNamesFromModule($moduleName);
        $components = $this->componentCollector->getComponentsByClasses($classNames);
        return array_merge($components, $this->scanClassesForPhpExtensions($classNames));
    }
    
    /**
     * @param string[] $classNames
     * @return Component[]
     * @throws ReflectionException
     */
    private function scanClassesForPhpExtensions(array $classNames): array
    {
        $components = [];
        $stringTokens = [];
        foreach ($classNames as $className) {
            $newTokens = $this->classInspector->setClassName($className)->getStringTokensFromFilename();
            $stringTokens = array_merge($stringTokens, $newTokens);
        }
        
        $stringTokens = array_unique($stringTokens);
        $phpExtensions = ['json', 'xml', 'pcre', 'gd', 'bcmath'];
        foreach ($phpExtensions as $phpExtension) {
            $phpExtensionFunctions = get_extension_funcs($phpExtension);
            foreach ($phpExtensionFunctions as $phpExtensionFunction) {
                if (in_array($phpExtensionFunction, $stringTokens)) {
                    $components[] = new Component(
                        'ext-' . $phpExtension,
                        'library',
                        'ext-' . $phpExtension,
                        '*'
                    );
                }
            }
        }
        
        return $components;
    }
}
