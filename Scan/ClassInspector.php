<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Magento\Framework\ObjectManager\ConfigInterface;
use ReflectionClass;
use ReflectionException;
use Throwable;

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
     * ClassInspector constructor.
     * @param Tokenizer $tokenizer
     * @param ConfigInterface $objectManagerConfig
     */
    public function __construct(
        Tokenizer $tokenizer,
        ConfigInterface $objectManagerConfig
    ) {
        $this->tokenizer = $tokenizer;
        $this->objectManagerConfig = $objectManagerConfig;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function setClassName(string $className)
    {
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
                if ($parameter->getType()) {
                    $dependencies[] = $this->normalizeClassName($parameter->getType()->getName());
                }
            }
        }

        return array_merge($dependencies, $object->getInterfaceNames());
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
     * @return string
     */
    public function getComponentByClass(): string
    {
        $parts = explode('\\', $this->className);
        if (empty($parts)) {
            return '';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0] . '_' . $parts[1];
    }

    /**
     * @return string
     */
    public function getPackageByClass(): string
    {
        try {
            $object = $this->getReflectionObject();
        } catch (ReflectionException $exception) {
            return '';
        }

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

        if (!class_exists($instanceType)) {
            return false;
        }

        $reflectionClass = new ReflectionClass($instanceType);

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
