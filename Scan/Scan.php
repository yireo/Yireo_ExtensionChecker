<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;
use Symfony\Component\Console\Output\Output;
use function Symfony\Component\DependencyInjection\Tests\Fixtures\factoryFunction;

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
     * @var ModuleList
     */
    private $moduleList;
    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;
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
     * @param ModuleList $moduleList
     * @param ComponentRegistrar $componentRegistrar
     * @param ClassCollector $classCollector
     * @param ClassInspector $classInspector
     */
    public function __construct(
        ModuleList $moduleList,
        ComponentRegistrar $componentRegistrar,
        ClassCollector $classCollector,
        ClassInspector $classInspector
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->classCollector = $classCollector;
        $this->classInspector = $classInspector;
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
        if (!in_array($moduleName, $this->moduleList->getNames())) {
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
        $moduleFolder = $this->getModuleFolder();
        $classes = $this->classCollector->getClassesFromFolder($moduleFolder);
        $allDependencies = [];

        foreach ($classes as $class) {
            $msg = sprintf('Class "%s" has the following dependencies: ', $class);
            $this->output->writeln($msg);

            $dependencies = $this->classInspector->setClassName($class)->getDependencies();
            $allDependencies = array_merge($allDependencies, $dependencies);

            foreach ($dependencies as $dependency) {
                $this->classInspector->setClassName((string)$dependency);
                $msg = ' -> ' . $dependency;
                if ($this->classInspector->isDeprecated()) {
                    $msg .= ' DEPRECATED!!!!';
                }

                $this->output->writeln($msg);
            }
        }

        $components = $this->getComponentsByClasses($allDependencies);
        $this->output->writeln('Dependencies of this module:');
        foreach ($components as $component) {
            $this->output->writeln('- ' . $component);
        }
    }

    /**
     * @return string
     */
    private function getModuleFolder(): string
    {
        return $this->componentRegistrar->getPath('module', $this->moduleName);
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
