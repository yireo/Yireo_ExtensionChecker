<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use Magento\Setup\Exception;
use ReflectionException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var bool
     */
    private $hideDeprecated = false;

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
     * @var Composer
     */
    private $composer;

    /**
     * Scan constructor.
     *
     * @param Module $module
     * @param ClassCollector $classCollector
     * @param ClassInspector $classInspector
     * @param Composer $composer
     */
    public function __construct(
        Module $module,
        ClassCollector $classCollector,
        ClassInspector $classInspector,
        Composer $composer
    ) {
        $this->classCollector = $classCollector;
        $this->classInspector = $classInspector;
        $this->module = $module;
        $this->composer = $composer;
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
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param bool $hideDeprecated
     */
    public function setHideDeprecated(bool $hideDeprecated)
    {
        $this->hideDeprecated = $hideDeprecated;
    }

    /**
     * @throws ReflectionException
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
                $this->reportDeprecatedClass((string)$dependency, $class);
            }
        }

        $this->scanClassesForPhpExtensions($classes);

        $components = $this->getComponentsByClasses($allDependencies);
        $components = array_merge($components, $this->getComponentsByGuess());
        $components = array_unique($components);

        $packages = $this->getPackagesByClasses($allDependencies);
        $packageInfo = $this->module->getPackageInfo($this->moduleName);
        $moduleInfo = $this->module->getModuleInfo($this->moduleName);

        foreach ($components as $component) {
            if ($component === $this->moduleName) {
                continue;
            }

            if ($this->module->isKnown($component) && !in_array($component, $moduleInfo['sequence'])) {
                $msg = sprintf('Dependency "%s" not found module.xml', $component);
                $this->output->writeln($msg);
                continue;
            }
        }

        foreach ($packages as $package) {
            if ($package['name'] === $packageInfo['name']) {
                continue;
            }

            if (!in_array($package['name'], $packageInfo['dependencies'])) {
                $msg = sprintf('Dependency "%s" not found composer.json.', $package['name']);
                $msg .= ' ';
                $msg .= sprintf('Current version is %s', $package['version']);
                $this->output->writeln($msg);
            }
        }
    }

    /**
     * @param string $className
     * @param string $originalClassName
     */
    private function reportDeprecatedClass(string $className, string $originalClassName)
    {
        if ($this->hideDeprecated === true) {
            return;
        }

        $this->classInspector->setClassName($className);
        if ($this->classInspector->isDeprecated()) {
            $msg = sprintf('Use of deprecated dependency "%s" in "%s"', $className, $originalClassName);
            $this->output->writeln($msg);
        }
    }

    /**
     * @param array $classes
     *
     * @throws ReflectionException
     */
    private function scanClassesForPhpExtensions(array $classes)
    {
        $packageInfo = $this->module->getPackageInfo($this->moduleName);

        $stringTokens = [];
        foreach ($classes as $class) {
            $newTokens = $this->classInspector->setClassName($class)->getStringTokensFromFilename();
            $stringTokens = array_merge($stringTokens, $newTokens);
        }

        $stringTokens = array_unique($stringTokens);

        $phpExtensions = ['json', 'xml', 'pcre', 'gd', 'bcmath'];
        foreach ($phpExtensions as $phpExtension) {
            if (in_array('ext-' . $phpExtension, $packageInfo['dependencies'])) {
                continue;
            }

            $phpExtensionFunctions = get_extension_funcs($phpExtension);
            foreach ($phpExtensionFunctions as $phpExtensionFunction) {
                if (!in_array($phpExtensionFunction, $stringTokens)) {
                    continue;
                }

                $msg = sprintf('Function "%s" requires PHP extension "ext-%s"', $phpExtensionFunction, $phpExtension);
                $this->output->writeln($msg);
                break;
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
            if ($component === $this->moduleName) {
                continue;
            }

            $components[] = $component;
        }

        return $components;
    }

    /**
     * @return string[]
     */
    private function getComponentsByGuess(): array
    {
        $components = [];
        $moduleFolder = $this->module->getModuleFolder($this->moduleName);

        if (is_dir($moduleFolder . '/Setup')) {
            $components[] = 'Magento_Store';
        }

        if (is_dir($moduleFolder . '/etc/adminhtml')) {
            $components[] = 'Magento_Backend';
        }

        return $components;
    }

    /**
     * @param array $classes
     *
     * @return string[]
     */
    private function getPackagesByClasses(array $classes): array
    {
        $packages = [];
        foreach ($classes as $class) {
            $package = $this->classInspector->setClassName((string)$class)->getPackageByClass();
            if (!$package) {
                continue;
            }

            $packages[$package] = [
                'name' => $package,
                'version' => $this->getVersionByPackage($package),
            ];
        }

        return $packages;
    }

    /**
     * @param string $package
     *
     * @return string
     */
    private function getVersionByPackage(string $package): string
    {
        return $this->composer->getVersionByPackage($package);
    }
}
