<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

use ReflectionException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Exception\ComponentNotFoundException;
use Yireo\ExtensionChecker\Exception\NoClassNameException;
use Yireo\ExtensionChecker\Message\MessageBucket;

class ComponentCollector
{
    private ClassInspector $classInspector;
    private MessageBucket $messageBucket;

    /**
     * @param ClassInspector $classInspector
     * @param MessageBucket $messageBucket
     */
    public function __construct(
        ClassInspector $classInspector,
        MessageBucket $messageBucket
    ) {
        $this->classInspector = $classInspector;
        $this->messageBucket = $messageBucket;
    }

    /**
     * @param array $classNames
     * @return Component[]
     */
    public function getComponentsByClasses(array $classNames): array
    {
        $components = [];
        foreach ($classNames as $className) {
            $this->messageBucket->debug('Found class "' . $className . '"');

            try {
                $component = $this->classInspector->setClassName($className)->getComponentByClass();
            } catch (ReflectionException|ComponentNotFoundException|NoClassNameException $e) {
                continue;
            }

            $this->messageBucket->debug('Found component "' . $component->getComponentName() . '"');
            $components[] = $component;
        }

        return $components;
    }
}
