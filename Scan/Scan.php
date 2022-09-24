<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Composer\Semver\Semver;
use InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\Exception\ComponentNotFoundException;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Exception\NoFilesFoundException;
use Yireo\ExtensionChecker\Report\Message;
use Yireo\ExtensionChecker\Report\MessageFactory;
use Yireo\ExtensionChecker\Util\ModuleInfo;

class Scan
{
    /**
     * @var Message[]
     */
    private $messages = [];
    
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
     * @var ModuleInfo
     */
    private $moduleInfo;
    
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
     * @var ComponentFactory
     */
    private $componentFactory;
    
    /**
     * @var MessageFactory
     */
    private $messageFactory;
    
    /**
     * Scan constructor.
     *
     * @param ModuleInfo $moduleInfo
     * @param FileCollector $fileCollector
     * @param ClassCollector $classCollector
     * @param ClassInspector $classInspector
     * @param Composer $composer
     * @param ComponentFactory $componentFactory
     * @param MessageFactory $messageFactory
     */
    public function __construct(
        ModuleInfo $moduleInfo,
        FileCollector $fileCollector,
        ClassCollector $classCollector,
        ClassInspector $classInspector,
        Composer $composer,
        ComponentFactory $componentFactory,
        MessageFactory $messageFactory
    ) {
        $this->moduleInfo = $moduleInfo;
        $this->fileCollector = $fileCollector;
        $this->classCollector = $classCollector;
        $this->classInspector = $classInspector;
        $this->composer = $composer;
        $this->componentFactory = $componentFactory;
        $this->messageFactory = $messageFactory;
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
        if ($this->moduleInfo->isKnown($moduleName) === false) {
            $message = sprintf('Module "%s" is unknown', $moduleName);
            throw new InvalidArgumentException($message);
        }
        
        $this->moduleName = $moduleName;
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
     * @throws ReflectionException
     */
    public function scan()
    {
        $moduleFolder = $this->moduleInfo->getModuleFolder($this->moduleName);
        $files = $this->fileCollector->getFilesFromFolder($moduleFolder);
        $classNames = $this->getClassesFromFiles($files);
        $allClassDependencies = $this->getDependentClassesFromClasses($classNames);
        $this->scanClassesForPhpExtensions($classNames);
        $components = $this->scanClassDependenciesForComponents($allClassDependencies);
        $this->scanComposerDependencies($components);
        $this->scanComposerRequirements();
    }
    
    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
    
    /**
     * @param string[] $files
     * @return string[]
     */
    private function getClassesFromFiles(array $files): array
    {
        $classNames = [];
        foreach ($files as $file) {
            try {
                $classNames[] = $this->classCollector->getClassNameFromFile($file);
            } catch (Throwable $e) {
                $this->addDebug($e->getMessage());
                continue;
            }
        }
        
        if (!count($classNames) > 0) {
            $this->addDebug('No PHP classes detected');
        }
        
        return $classNames;
    }
    
    /**
     * @param string[] $classes
     * @return string[]
     */
    private function getDependentClassesFromClasses(array $classes): array
    {
        $classDependencies = [];
        foreach ($classes as $class) {
            $this->addDebug('PHP class detected: ' . $class);
            try {
                $tmpClassDependencies = $this->classInspector->setClassName($class)->getDependencies();
            } catch (ReflectionException $exception) {
                $this->addDebug('Reflection exception from class inspector [' . $class . ']: ' . $exception->getMessage());
                continue;
            }
            
            foreach ($tmpClassDependencies as $classDependency) {
                $this->addDebug('PHP dependency detected: ' . $classDependency);
                $this->reportDeprecatedClass($classDependency, $class);
            }
            
            $classDependencies = array_merge($classDependencies, $tmpClassDependencies);
        }
        
        $classDependencies = array_unique($classDependencies);
        return $classDependencies;
    }
    
    /**
     * @param string[] $classDependencies
     * @return Component[]
     */
    private function scanClassDependenciesForComponents(array $classDependencies): array
    {
        $components = $this->getComponentsByClasses($classDependencies);
        $components = array_merge($components, $this->getComponentsByGuess());
        $components = array_unique($components);
        
        $moduleInfo = $this->moduleInfo->getModuleInfo($this->moduleName);
        foreach ($components as $component) {
            $componentName = $component->getComponentName();
            if ($componentName === $this->moduleName) {
                continue;
            }
            
            if ($this->moduleInfo->isKnown($componentName) && !in_array($componentName, $moduleInfo['sequence'])) {
                $this->addWarning(sprintf('Dependency "%s" not found module.xml', $componentName));
            }
        }
        
        if ($this->hideNeedless === true) {
            return $components;
        }
        
        foreach ($moduleInfo['sequence'] as $module) {
            if (!in_array($module, $components)) {
                $this->addWarning(sprintf('Dependency "%s" from module.xml possibly not needed.', $module));
            }
        }
        
        return $components;
    }
    
    /**
     * @param Component[] $components
     */
    private function scanComposerDependencies(array $components)
    {
        if ($this->hasComposerFile() === false) {
            return;
        }
        
        $packageInfo = $this->moduleInfo->getPackageInfo($this->moduleName);
        $packageNames = [];
        
        foreach ($components as $component) {
            $packageName = $component->getPackageName();
            if ($packageName === $packageInfo['name']) {
                continue;
            }
            
            if (!in_array($packageName, $packageInfo['dependencies'])) {
                $version = $component->getPackageVersion();
                $msg = sprintf('Dependency "%s" not found in composer.json. ', $packageName);
                $msg .= sprintf('Current version is %s. ', $version);
                $msg .= sprintf('Perhaps use %s?', $this->getSuggestedVersion($version));
                $this->addWarning($msg);
            }
        }
        
        if ($this->hideNeedless === true) {
            return;
        }
        
        $this->reportUnneededDependency($packageInfo['dependencies'], $components);
    }
    
    /**
     * @param string[] $currentDependencies
     * @param Component[] $components
     * @return void
     */
    private function reportUnneededDependency(array $currentDependencies, array $components)
    {
        foreach ($currentDependencies as $currentDependency) {
            if ($this->isDependencyNeeded($currentDependency, $components)) {
                continue;
            }
            
            $msg = sprintf('Dependency "%s" from composer.json possibly not needed.', $currentDependency);
            $this->addWarning($msg);
        }
    }
    
    /**
     * @param string $dependency
     * @param Component[] $components
     * @return bool
     */
    private function isDependencyNeeded(string $dependency, array $components): bool
    {
        foreach ($components as $component) {
            if ($component->getPackageName() === $dependency) {
                return true;
            }
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
            $this->addWarning($msg);
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
            $this->handleComposerRequirement($requirement, $requirementVersion);
        }
        
        if (isset($composerData['repositories'])) {
            $this->addDebug('A composer package should not have a "repositories" section');
        }
    }
    
    private function handleComposerRequirement($requirement, $requirementVersion)
    {
        if (!preg_match('/^ext-/', $requirement) && $requirementVersion === '*') {
            $version = $this->getVersionByPackage($requirement);
            $msg = 'Composer dependency "' . $requirement . '" is set to version *. ';
            $msg .= sprintf('Current version is %s. ', $version);
            $msg .= sprintf('Perhaps use %s?', $this->getSuggestedVersion($version));
            $this->addWarning($msg);
        }
        
        if ($requirement === 'php') {
            $currentVersion = phpversion();
            if (!Semver::satisfies($currentVersion, $requirementVersion)) {
                $msg = 'Required PHP version "' . $requirementVersion . '" does not match your current PHP version ' . $currentVersion;
                $this->addWarning($msg);
            }
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
        
        $packageInfo = $this->moduleInfo->getPackageInfo($this->moduleName);
        
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
                    $this->addWarning($msg);
                    break;
                }
            }
            
            if (!$this->hideNeedless && !$isNeeded && in_array('ext-' . $phpExtension, $packageInfo['dependencies'])) {
                $msg = sprintf('PHP extension "ext-%s" from composer.json possibly not needed.', $phpExtension);
                $this->addWarning($msg);
                break;
            }
        }
    }
    
    /**
     * @return Component[]
     */
    private function getComponentsByClasses(array $classNames): array
    {
        $components = [];
        foreach ($classNames as $className) {
            try {
                $component = $this->classInspector->setClassName($className)->getComponentByClass();
            } catch (ReflectionException|ComponentNotFoundException $e) {
                continue;
            }
            
            if ($component->getComponentName() === $this->moduleName) {
                continue;
            }
            
            $components[] = $component;
        }
        
        return $components;
    }
    
    /**
     * @return Component[]
     */
    private function getComponentsByGuess(): array
    {
        $components = [];
        
        try {
            $moduleFolder = $this->moduleInfo->getModuleFolder($this->moduleName);
        } catch (ModuleNotFoundException $moduleNotFoundException) {
            $this->addDebug('ModuleNotFoundException: ' . $moduleNotFoundException->getMessage());
            return $components;
        }
        
        if (is_dir($moduleFolder . '/Setup') || is_dir($moduleFolder . '/Block')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Store');
        }
        
        if (is_file($moduleFolder . '/etc/schema.graphqls')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_GraphQl');
        }
        
        if (is_dir($moduleFolder . '/etc/graphql')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_GraphQl');
        }
        
        if (is_dir($moduleFolder . '/etc/frontend')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Store');
        }
        
        if (is_dir($moduleFolder . '/etc/adminhtml')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Backend');
        }
        
        return $components;
    }
    
    /**
     * @param string $package
     *
     * @return string
     */
    public function getVersionByPackage(string $package): string
    {
        return $this->composer->getVersionByPackage($package);
    }
    
    /**
     * @return bool
     */
    private function hasComposerFile(): bool
    {
        try {
            return (bool)$this->getComposerFile();
        } catch (ComponentNotFoundException $componentNotFoundException) {
            return false;
        }
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
     * @throws ComponentNotFoundException
     */
    private function getComposerFile(): string
    {
        return $this->moduleInfo->getComposerFile($this->moduleName);
    }
    
    /**
     * @param string $text
     * @return void
     */
    private function addWarning(string $text)
    {
        $this->messages[] = $this->messageFactory->createWarning($text);
    }
    
    /**
     * @param string $text
     * @return void
     */
    private function addDebug(string $text)
    {
        $this->messages[] = $this->messageFactory->createDebug($text);
    }
    
    
    /**
     * @param string $version
     * @return string
     */
    private function getSuggestedVersion(string $version): string
    {
        $versionParts = explode('.', $version);
        if ((int)$versionParts[0] === 0) {
            return '~' . $version;
        }
        
        return '^' . $versionParts[0] . '.' . $versionParts[1];
    }
}
