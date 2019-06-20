<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Magento\Framework\App\ObjectManager;
use ReflectionClass;
use ReflectionException;
use Throwable;

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
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * ClassInspector constructor.
     */
    public function __construct(
        Tokenizer $tokenizer
    ) {
        $this->tokenizer = $tokenizer;
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
     */
    public function getDependencies(): array
    {
        $dependencies = [];

        try {
            $object = $this->getReflectionObject();
        } catch (ReflectionException $exception) {
            return $dependencies;
        }

        $constructor = $object->getConstructor();
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
     */
    public function isDeprecated(): bool
    {
        try {
            $object = $this->getReflectionObject();
        } catch (ReflectionException $exception) {
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
        if (!preg_match('/vendor\/([^\/]+)\/([^\/]+)\//', $filename, $match)) {
            return '';
        }

        return $match[1] . '/' . $match[2];
    }

    /**
     * @return string[]
     * @throws ReflectionException
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
     * @throws ReflectionException
     */
    private function getReflectionObject(): ReflectionClass
    {
        if (isset($this->registry[$this->className])) {
            return $this->registry[$this->className];
        }

        $objectManager = ObjectManager::getInstance();
        $object = $objectManager->get($this->className);
        if (!$object instanceof $this->className) {
            throw new ReflectionException();
        }

        try {
            $object = new ReflectionClass($this->className);
        } catch (Throwable $e) {
            return null;
        }

        $this->registry[$this->className] = $object;

        return $object;
    }
}
