<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yireo\ExtensionChecker\Exception\NoFilesFoundException;

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
     * @var FileCollector
     */
    private $fileCollector;
    
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
     * @param FileCollector $fileCollector
     * @param ClassCollector $classCollector
     * @param ClassInspector $classInspector
     * @param Composer $composer
     */
    public function __construct(
        Module $module,
        FileCollector $fileCollector,
        ClassCollector $classCollector,
        ClassInspector $classInspector,
        Composer $composer
    ) {
        $this->module = $module;
        $this->fileCollector = $fileCollector;
        $this->classCollector = $classCollector;
        $this->classInspector = $classInspector;
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
        $files = $this->fileCollector->getFilesFromFolder($moduleFolder);
        if (empty($files)) {
            throw new NoFilesFoundException('No files found in folder "' . $moduleFolder . '"');
        }
        
        $classNames = [];
        foreach ($files as $file) {
            try {
                $classNames[] = $this->classCollector->getClassNameFromFile($file);
            } catch (Throwable $e) {
                $this->debug($e->getMessage());
                continue;
            }
        }
        
        if (!count($classNames)) {
            $this->debug('No PHP classes detected');
        }
        
        $allDependencies = [];
        foreach ($classNames as $className) {
            $this->debug('PHP class detected: ' . $className);
            try {
                $dependencies = $this->classInspector->setClassName($className)->getDependencies();
            } catch (ReflectionException $exception) {
                $this->debug('Reflection exception from class inspector [' . $className . ']: ' . $exception->getMessage());
                continue;
            }
            
            $allDependencies = array_merge($allDependencies, $dependencies);
            foreach ($dependencies as $dependency) {
                $this->debug('PHP dependency detected: ' . $dependency);
                $this->reportDeprecatedClass($dependency, $className);
            }
        }
        
        $this->scanClassesForPhpExtensions($classNames);
        $this->scanModuleDependencies($allDependencies);
        $this->scanComposerDependencies($allDependencies);
        $this->scanComposerRequirements();
        return $this->hasWarnings;
    }
    
    /**
     * @param array $classDependencies
     */
    private function scanModuleDependencies(array $classDependencies)
    {
        $components = $this->getComponentsByClasses($classDependencies);
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
            }
        }
        
        if ($this->hideNeedless === true) {
            return;
        }
        
        foreach ($moduleInfo['sequence'] as $module) {
            if (!in_array($module, $components)) {
                $msg = sprintf('Dependency "%s" from module.xml possibly not needed.', $module);
                $this->output->writeln($msg);
                $this->hasWarnings = true;
            }
        }
    }
    
    /**
     * @param array $classDependencies
     */
    private function scanComposerDependencies(array $classDependencies)
    {
        if ($this->hasComposerFile() === false) {
            return;
        }
        
        $packages = $this->getPackagesByClasses($classDependencies);
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
        
        $this->reportUnneededDependency($packageInfo['dependencies'], $packageNames);
    }
    
    /**
     * @param string[] $currentDependencies
     * @param string[] $packageNames
     * @return void
     */
    private function reportUnneededDependency(array $currentDependencies, array $packageNames)
    {
        foreach ($currentDependencies as $currentDependency) {
            if ($this->isDependencyNeeded($currentDependency, $packageNames)) {
                continue;
            }
            
            $msg = sprintf('Dependency "%s" from composer.json possibly not needed.', $currentDependency);
            $this->output->writeln($msg);
            $this->hasWarnings = true;
        }
    }
    
    /**
     * @param string $dependency
     * @param array $packageNames
     * @return bool
     */
    private function isDependencyNeeded(string $dependency, array $packageNames): bool
    {
        if (in_array($dependency, $packageNames)) {
            return true;
        }
        
        if (in_array($dependency, $this->validDependencies)) {
            return true;
        }
        
        if ($dependency === 'magento/framework') {
            return true;
        }
        
        if (preg_match('/^ext-/', $dependency)) {
            return true;
        }
        
        return false;
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
                    $msg = sprintf('Function "%s" requires PHP extension "ext-%s"', $phpExtensionFunction,
                        $phpExtension);
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
        
        if (is_dir($moduleFolder . '/Setup') || is_dir($moduleFolder . '/Block')) {
            $components[] = 'Magento_Store';
        }
        
        if (is_file($moduleFolder . '/etc/schema.graphqls')) {
            $components[] = 'Magento_GraphQl';
        }
        
        if (is_dir($moduleFolder . '/etc/graphql')) {
            $components[] = 'Magento_GraphQl';
        }
        
        if (is_dir($moduleFolder . '/etc/frontend')) {
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
     * @return array[]
     */
    private function getPackagesByClasses(array $classNames): array
    {
        $packages = [];
        foreach ($classNames as $className) {
            try {
                $package = $this->classInspector->setClassName($className)->getPackageByClass();
            } catch (ReflectionException $e) {
                $this->debug('Reflection exception in class inspector [' . $className . ']: ' . $e->getMessage());
                continue;
            }
            
            if (!$package) {
                $this->debug('Failed to get load class: ' . $className);
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
    
    /**
     * @return bool
     */
    private function isVerbose(): bool
    {
        return (bool)$this->input->getOption('verbose');
    }
    
    /**
     * @param string $text
     * @return void
     */
    private function debug(string $text)
    {
        if (!$this->isVerbose()) {
            return;
        }
        
        $this->output->writeln('* ' . $text);
    }
}
