<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use ReflectionException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Exception\NoClassNameException;
use Yireo\ExtensionChecker\PhpClass\ClassInspector;
use Yireo\ExtensionChecker\PhpClass\ClassNameCollector;
use Yireo\ExtensionChecker\PhpClass\ComponentCollector;
use Yireo\ExtensionChecker\PhpClass\ModuleCollector;
use Yireo\ExtensionChecker\PhpClass\Tokenizer;

/**
 * Detect components from a module its PHP classes
 */
class PhpClassComponentDetector implements ComponentDetectorInterface
{
    private ClassInspector $classInspector;
    private ModuleCollector $moduleCollector;
    private ComponentCollector $componentCollector;
    private Tokenizer $tokenizer;
    private ClassNameCollector $classNameCollector;

    /**
     * @param ClassInspector $classInspector
     * @param ModuleCollector $moduleCollector
     * @param ComponentCollector $componentCollector
     * @param Tokenizer $tokenizer
     * @param ClassNameCollector $classNameCollector
     */
    public function __construct(
        ClassInspector $classInspector,
        ModuleCollector $moduleCollector,
        ComponentCollector $componentCollector,
        Tokenizer $tokenizer,
        ClassNameCollector $classNameCollector
    ) {
        $this->classInspector = $classInspector;
        $this->moduleCollector = $moduleCollector;
        $this->componentCollector = $componentCollector;
        $this->tokenizer = $tokenizer;
        $this->classNameCollector = $classNameCollector;
    }

    /**
     * @return Component[]
     * @throws ReflectionException
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        $classNames = $this->moduleCollector->getClassNamesFromModule($moduleName);

        $dependentClassNames = $this->classNameCollector->getDependentClassesFromClasses($classNames);
        print_r($dependentClassNames);exit;

        $components = $this->componentCollector->getComponentsByClasses($dependentClassNames);
        return array_merge($components, $this->scanClassesForPhpExtensions($classNames));
    }

    /**
     * @param string[] $classNames
     * @return Component[]
     */
    private function scanClassesForPhpExtensions(array $classNames): array
    {
        $stringTokens = $this->getStringTokensFromClassNames($classNames);
        return $this->detectPhpExtensionsFromTokens($stringTokens);
    }

    /**
     * @param string[] $classNames
     * @return string[]
     */
    private function getStringTokensFromClassNames(array $classNames): array
    {
        $stringTokens = [];
        foreach ($classNames as $className) {
            try {
                $fileName = $this->classInspector->setClassName($className)->getFilename();
            } catch (NoClassNameException $noClassNameException) {
                continue;
            }

            $newTokens = $this->tokenizer->getStringTokensFromFilename($fileName);
            $stringTokens = array_merge($stringTokens, $newTokens);
        }

        return $stringTokens;
    }

    /**
     * @param string[] $stringTokens
     * @return Component[]
     */
    private function detectPhpExtensionsFromTokens(array $stringTokens): array
    {
        $components = [];
        $phpExtensions = ['json', 'xml', 'pcre', 'gd', 'bcmath'];

        foreach ($phpExtensions as $phpExtension) {
            $phpExtensionFunctions = get_extension_funcs($phpExtension);
            foreach ($phpExtensionFunctions as $phpExtensionFunction) {
                if (in_array($phpExtensionFunction, $stringTokens)) {
                    $components[] = new Component(
                        'ext-' . $phpExtension,
                        'library',
                        'ext-' . $phpExtension,
                        '*',
                        true
                    );
                }
            }
        }

        return $components;
    }
}
