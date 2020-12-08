<?php

declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Scan
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
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
     * @var bool
     */
    private $hideNeedless = false;

    /**
     * @var bool
     */
    private $hasWarnings = false;

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
     * @var string[]
     */
    private $validDependencies = [
        'php',
        'magento/magento-composer-installer'
    ];

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
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
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
     * @param bool $hideNeedless
     */
    public function setHideNeedless(bool $hideNeedless)
    {
        $this->hideNeedless = $hideNeedless;
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    public function scan(): bool
    {
        $moduleFolder = $this->module->getModuleFolder($this->moduleName);
        $classes = $this->classCollector->getClassesFromFolder($moduleFolder);
        $allDependencies = [];

        foreach ($classes as $class) {
            $className = is_object($class) ? get_class($class) : (string)$class;
            $dependencies = $this->classInspector->setClassName($className)->getDependencies();
            $allDependencies = array_merge($allDependencies, $dependencies);
            foreach ($dependencies as $dependency) {
                $dependencyName = is_object($dependency) ? get_class($dependency) : (string)$dependency;
                $this->reportDeprecatedClass($dependencyName, $class);
            }
        }

        $this->scanClassesForPhpExtensions($classes);
        $this->scanModuleDependencies($allDependencies);
        $this->scanComposerDependencies($allDependencies);
        $this->scanComposerRequirements();
        return $this->hasWarnings;
    }

    /**
     * @param array $allDependencies
     */
    private function scanModuleDependencies(array $allDependencies)
    {
        $components = $this->getComponentsByClasses($allDependencies);
        $components = array_merge($components, $this->getComponentsByGuess());
        $components = array_unique($components);

        $moduleInfo = $this->module->getModuleInfo($this->moduleName);
        foreach ($components as $component) {
            if ($component === $this->moduleName) {
                continue;
            }

            if ($this->module->isKnown($component) && !in_array($component, $moduleInfo['sequence'])) {
                $msg = sprintf('Dependency "%s" not found module.xml', $component);
                $this->output->writeln($msg);
                $this->hasWarnings = true;
                continue;
            }
        }

        if ($this->hideNeedless === true) {
            return;
        }

        foreach($moduleInfo['sequence'] as $module)
        {
            if (!in_array($module, $components)) {
                $msg = sprintf('Dependency "%s" from module.xml possibly not needed.', $module);
                $this->output->writeln($msg);
                $this->hasWarnings = true;
            }
        }
    }

    /**
     * @param array $allDependencies
     */
    private function scanComposerDependencies(array $allDependencies)
    {
        if ($this->hasComposerFile() === false) {
            return;
        }

        $packages = $this->getPackagesByClasses($allDependencies);
        $packageInfo = $this->module->getPackageInfo($this->moduleName);

        $packageNames = [];

        foreach ($packages as $package) {
            if ($package['name'] === $packageInfo['name']) {
                continue;
            }

            $packageNames[] = $package['name'];

            if (!in_array($package['name'], $packageInfo['dependencies'])) {
                $msg = sprintf('Dependency "%s" not found composer.json.', $package['name']);
                $msg .= ' ';
                $msg .= sprintf('Current version is %s', $package['version']);
                $this->output->writeln($msg);
                $this->hasWarnings = true;
            }
        }

        if ($this->hideNeedless === true) {
            return;
        }

        foreach($packageInfo['dependencies'] as $packageInfo) {
            if (!in_array($packageInfo, $packageNames)
                && !in_array($packageInfo, $this->validDependencies)
                && !preg_match('/^ext-/', $packageInfo))
            {
                $msg = sprintf('Dependency "%s" from composer.json possibly not needed.', $packageInfo);
                $this->output->writeln($msg);
                $this->hasWarnings = true;
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
            $this->hasWarnings = true;
        }
    }

    private function scanComposerRequirements()
    {
        if ($this->hasComposerFile() === false) {
            return;
        }

        $composerData = $this->getComposerData();
        if (empty($composerData['require'])) {
            return;
        }

        $requirements = $composerData['require'];
        foreach ($requirements as $requirement => $requirementVersion) {
            if (!preg_match('/^ext-/', $requirement) && $requirementVersion === '*') {
                $msg = 'Composer dependency "' . $requirement . '" is set to version *.';
                $msg .= ' ';
                $msg .= sprintf('Current version is %s', $this->getVersionByPackage($requirement));
                $this->output->writeln($msg);
                $this->hasWarnings = true;
            }
        }

        if (isset($composerData['repositories'])) {
            $this->output->writeln('A composer package should not have a "repositories" section');
            $this->hasWarnings = true;
        }
    }

    /**
     * @param array $classes
     *
     * @throws ReflectionException
     */
    private function scanClassesForPhpExtensions(array $classes)
    {
        if ($this->hasComposerFile() === false) {
            return;
        }

        $packageInfo = $this->module->getPackageInfo($this->moduleName);

        $stringTokens = [];
        foreach ($classes as $class) {
            $newTokens = $this->classInspector->setClassName($class)->getStringTokensFromFilename();
            $stringTokens = array_merge($stringTokens, $newTokens);
        }

        $stringTokens = array_unique($stringTokens);

        $phpExtensions = ['json', 'xml', 'pcre', 'gd', 'bcmath'];
        foreach ($phpExtensions as $phpExtension) {
            $isNeeded = false;
            $phpExtensionFunctions = get_extension_funcs($phpExtension);
            foreach ($phpExtensionFunctions as $phpExtensionFunction) {

                if (in_array($phpExtensionFunction, $stringTokens)) {
                    $isNeeded = true;
                }

                if ($isNeeded && !in_array('ext-' . $phpExtension, $packageInfo['dependencies'])) {
                    $msg = sprintf('Function "%s" requires PHP extension "ext-%s"', $phpExtensionFunction, $phpExtension);
                    $this->output->writeln($msg);
                    $this->hasWarnings = true;
                    break;
                }

            }

            if (!$this->hideNeedless && !$isNeeded && in_array('ext-' . $phpExtension, $packageInfo['dependencies'])) {
                $msg = sprintf('PHP extension "ext-%s" from composer.json possibly not needed.', $phpExtension);
                $this->output->writeln($msg);
                $this->hasWarnings = true;
                break;
            }
        }
    }

    /**
     * @return string[]
     */
    private function getComponentsByClasses(array $classNames): array
    {
        $components = [];
        foreach ($classNames as $className) {
            $component = $this->classInspector->setClassName($className)->getComponentByClass();
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
     * @param string[] $classes
     *
     * @return string[]
     */
    private function getPackagesByClasses(array $classNames): array
    {
        $packages = [];
        foreach ($classNames as $className) {
            $package = $this->classInspector->setClassName($className)->getPackageByClass();
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

    /**
     * @return bool
     */
    private function hasComposerFile(): bool
    {
        return is_file($this->getComposerFile());
    }

    /**
     * @return bool
     */
    private function getComposerData(): array
    {
        if (!$this->hasComposerFile()) {
            return [];
        }

        $composerData = file_get_contents($this->getComposerFile());
        return json_decode($composerData, true);
    }

    /**
     * @return string
     */
    private function getComposerFile(): string
    {
        return $this->module->getModuleFolder($this->moduleName) . '/composer.json';
    }
}
