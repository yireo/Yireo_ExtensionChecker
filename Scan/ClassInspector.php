<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use ReflectionClass;
use ReflectionException;

/**
 * Class ClassInspector
 *
 * @package Yireo\ExtensionChecker\Scan
 */
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
     * ClassInspector constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName(string $className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        $dependencies = [];

        try {
            $class = $this->getReflectionClass();
        } catch (ReflectionException $exception) {
            return $dependencies;
        }

        $constructor = $class->getConstructor();
        if (!$constructor) {
            return $dependencies;
        }

        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {
            if (!$parameter->getClass()) {
                continue;
            }

            $dependencies[] = $parameter->getType();
        }

        return $dependencies;
    }

    /**
     * @return bool
     * @todo This might be beautified a bit
     */
    public function isDeprecated(): bool
    {
        try {
            $class = $this->getReflectionClass();
        } catch (ReflectionException $exception) {
            return false;
        }

        if (!strstr((string)$class->getDocComment(), '@deprecated')) {
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
     * @throws ReflectionException
     */
    private function getReflectionClass(): ReflectionClass
    {
        if (isset($this->registry[$this->className])) {
            return $this->registry[$this->className];
        }

        $class = new ReflectionClass($this->className);
        $this->registry[$this->className] = $class;

        return $class;
    }
}
