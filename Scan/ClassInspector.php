<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Magento\Framework\ObjectManager\ConfigInterface;
use ReflectionClass;
use ReflectionException;
use Throwable;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\Exception\ComponentNotFoundException;
use Yireo\ExtensionChecker\Exception\NoClassNameException;
use Yireo\ExtensionChecker\Util\ModuleInfo;

class ClassInspector
{
    /**
     * @var string
     */
    private $className = '';
    
    /**
     * @var array
     */
    private $registry = [];
    
    /**
     * @var Tokenizer
     */
    private $tokenizer;
    
    /**
     * @var ConfigInterface
     */
    private $objectManagerConfig;
    
    /**
     * @var ComponentFactory
     */
    private $componentFactory;
    
    /**
     * @var ModuleInfo
     */
    private $moduleInfo;
    
    /**
     * ClassInspector constructor.
     * @param Tokenizer $tokenizer
     * @param ConfigInterface $objectManagerConfig
     * @param ComponentFactory $componentFactory
     * @param ModuleInfo $moduleInfo
     */
    public function __construct(
        Tokenizer $tokenizer,
        ConfigInterface $objectManagerConfig,
        ComponentFactory $componentFactory,
        ModuleInfo $moduleInfo
    ) {
        $this->tokenizer = $tokenizer;
        $this->objectManagerConfig = $objectManagerConfig;
        $this->componentFactory = $componentFactory;
        $this->moduleInfo = $moduleInfo;
    }
    
    /**
     * @param string $className
     * @return $this
     * @throws NoClassNameException
     */
    public function setClassName(string $className)
    {
        if (!class_exists($className) && !interface_exists($className)) {
            throw new NoClassNameException('Class "'.$className.'" does not exist');
        }
        
        $this->className = $className;
        return $this;
    }
    
    /**
     * @return string[]
     * @throws ReflectionException
     */
    public function getDependencies(): array
    {
        $object = $this->getReflectionObject();
        $dependencies = [];
        $constructor = $object->getConstructor();
        if ($constructor) {
            $parameters = $constructor->getParameters();
            foreach ($parameters as $parameter) {
                if (!$parameter->getType()) {
                    continue;
                }
                
                $dependency = $this->normalizeClassName($parameter->getType()->getName());
                if (!class_exists($dependency)) {
                    continue;
                }
                
                if (in_array($dependency, ['array'])) {
                    continue;
                }
                
                $dependencies[] = $this->normalizeClassName($parameter->getType()->getName());
            }
        }
        
        $interfaceNames = $object->getInterfaceNames();
        foreach ($interfaceNames as $interfaceName) {
            if (!interface_exists($interfaceName)) {
                continue;
            }
            
            if (in_array($interfaceName, ['ArrayAccess'])) {
                continue;
            }
            
            $dependencies[] = $interfaceName;
        }
        return $dependencies;
    }
    
    /**
     * @param $class
     * @return string
     */
    private function normalizeClassName($class): string
    {
        return is_object($class) ? get_class($class) : (string)$class;
    }
    
    /**
     * @return bool
     */
    public function isDeprecated(): bool
    {
        try {
            $object = $this->getReflectionObject();
        } catch (ReflectionException|Throwable $exception) {
            return false;
        }
        
        if (!strstr((string)$object->getDocComment(), '@deprecated')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @return Component
     * @throws ReflectionException
     * @throws ComponentNotFoundException
     */
    public function getComponentByClass(): Component
    {
        $parts = explode('\\', $this->className);
        if (count($parts) < 2) {
            throw new ComponentNotFoundException('No component found for class "' . $this->className . '"');
        }
    
        $moduleName = $parts[0] . '_' . $parts[1];
        if ($this->moduleInfo->isKnown($moduleName)) {
            return $this->componentFactory->createByModuleName($moduleName);
        }
        
        $package = $this->getPackageByClass();
        if (!empty($package)) {
            return $this->componentFactory->createByLibraryName($package);
        }
    
        throw new ComponentNotFoundException('No component found for class "' . $this->className . '"');
    }
    
    /**
     * @return string
     * @throws ReflectionException
     */
    public function getPackageByClass(): string
    {
        $object = $this->getReflectionObject();
        $filename = $object->getFileName();
        if (empty($filename)) {
            return '';
        }
        
        if (!preg_match('/vendor\/([^\/]+)\/([^\/]+)\//', $filename, $match)) {
            return '';
        }
        
        return $match[1] . '/' . $match[2];
    }
    
    /**
     * @return string[]
     */
    public function getStringTokensFromFilename(): array
    {
        try {
            $object = $this->getReflectionObject();
        } catch (ReflectionException $e) {
            return [];
        }
        
        return $this->tokenizer->getStringTokensFromFilename($object->getFileName());
    }
    
    /**
     * @param $className
     * @return bool
     */
    private function isInstantiable($className): bool
    {
        $instanceType = $this->objectManagerConfig->getPreference($className);
        if (empty($instanceType)) {
            return class_exists($className) || interface_exists($className);
        }
        
        $reflectionClass = new ReflectionClass($instanceType);
        if ($reflectionClass->isInterface()) {
            return true;
        }
        
        if (!$reflectionClass->isInstantiable()) {
            return false;
        }
        
        if ($reflectionClass->isAbstract()) {
            return false;
        }
        
        return true;
    }
    
    
    /**
     * @throws ReflectionException
     */
    private function getReflectionObject(): ReflectionClass
    {
        if (isset($this->registry[$this->className])) {
            return $this->registry[$this->className];
        }
        
        if ($this->isInstantiable($this->className) === false) {
            throw new ReflectionException('Class does not exist');
        }
        
        $object = new ReflectionClass($this->className);
        $this->registry[$this->className] = $object;
        
        return $object;
    }
}
