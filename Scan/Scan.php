<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use Symfony\Component\Console\Output\Output;

/**
 * Class Scan
 *
 * @package Yireo\ExtensionChecker\Scan
 */
class Scan
{
    /**
     * @var Output
     */
    private $output;

    /**
     * @var string
     */
    private $moduleName = '';

    /**
     * @var Module
     */
    private $module;

    /**
     * @var ClassCollector
     */
    private $classCollector;

    /**
     * @var ClassInspector
     */
    private $classInspector;

    /**
     * Scan constructor.
     *
     * @param Module $module
     * @param ClassCollector $classCollector
     * @param ClassInspector $classInspector
     */
    public function __construct(
        Module $module,
        ClassCollector $classCollector,
        ClassInspector $classInspector
    ) {
        $this->classCollector = $classCollector;
        $this->classInspector = $classInspector;
        $this->module = $module;
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * @param string $moduleName
     */
    public function setModuleName(string $moduleName): void
    {
        if ($this->module->isKnown($moduleName) === false) {
            $message = sprintf('Module "%s" is unknown', $moduleName);
            throw new InvalidArgumentException($message);
        }

        $this->moduleName = $moduleName;
    }

    /**
     * @param Output $output
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

    /**
     *
     */
    public function scan()
    {
        $moduleFolder = $this->module->getModuleFolder($this->moduleName);
        $classes = $this->classCollector->getClassesFromFolder($moduleFolder);
        $allDependencies = [];

        foreach ($classes as $class) {
            $dependencies = $this->classInspector->setClassName($class)->getDependencies();
            $allDependencies = array_merge($allDependencies, $dependencies);

            foreach ($dependencies as $dependency) {
                $this->classInspector->setClassName((string)$dependency);
                if ($this->classInspector->isDeprecated()) {
                    $msg = sprintf('Use of deprecated dependency "%s" in "%s"', $dependency, $class);
                    $this->output->writeln($msg);
                }
            }
        }

        $components = $this->getComponentsByClasses($allDependencies);
        $packageInfo = $this->module->getPackageInfo($this->moduleName);
        $moduleInfo = $this->module->getModuleInfo($this->moduleName);

        foreach ($components as $component) {
            if (!in_array($component, $packageInfo['requirements'])) {
                $msg = sprintf('Dependency "%s" not found composer.json', $component);
                $this->output->writeln($msg);
            }

            if ($this->module->isKnown($component) && !in_array($component, $moduleInfo['sequence'])) {
                $msg = sprintf('Dependency "%s" not found module.xml', $component);
                $this->output->writeln($msg);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getComponentsByClasses(array $classes): array
    {
        $components = [];
        foreach ($classes as $class) {
            $component = $this->classInspector->setClassName((string)$class)->getComponentByClass();
            $components[] = $component;
        }

        return array_unique($components);
    }
}
