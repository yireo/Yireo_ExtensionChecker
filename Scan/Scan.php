<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;
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
     * Scan constructor.
     *
     * @param ModuleList $moduleList
     * @param ComponentRegistrar $componentRegistrar
     * @param ClassCollector $classCollector
     */
    public function __construct(
        ModuleList $moduleList,
        ComponentRegistrar $componentRegistrar,
        ClassCollector $classCollector
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->classCollector = $classCollector;
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

        foreach ($classes as $class) {
            $this->output->writeln($class);
        }
    }

    /**
     * @return string
     */
    private function getModuleFolder(): string
    {
        return $this->componentRegistrar->getPath('module', $this->moduleName);
    }
}
