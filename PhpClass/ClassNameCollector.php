<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

use ReflectionException;
use Throwable;
use Yireo\ExtensionChecker\Exception\EmptyClassNameException;
use Yireo\ExtensionChecker\Exception\NoClassNameException;
use Yireo\ExtensionChecker\Exception\UnreadableFileException;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;

class ClassNameCollector
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
     * @param string[] $files
     * @return string[]
     */
    public function getClassNamesFromFiles(array $files): array
    {
        $classNames = [];

        foreach ($files as $file) {
            if (basename($file) === 'registration.php') {
                continue;
            }

            try {
                $classNames[] = $this->getClassNameFromFile($file);
            } catch (Throwable $e) {
                $this->messageBucket->add($e->getMessage(), MessageGroupLabels::GROUP_EXCEPTION);
                continue;
            }
        }

        if (!count($classNames) > 0) {
            $this->messageBucket->add('No PHP classes detected for files', MessageGroupLabels::GROUP_EXCEPTION);
        }

        return $classNames;
    }

    /**
     * @param string $file
     *
     * @return string
     * @throws UnreadableFileException
     * @throws EmptyClassNameException
     */
    public function getClassNameFromFile(string $file): string
    {
        if (!file_exists($file)) {
            throw new UnreadableFileException('File "' . $file . '" does not exist');
        }

        $contents = file_get_contents($file);
        if (empty($contents)) {
            throw new UnreadableFileException('Empty contents for file "' . $file . '"');
        }

        $tokens = token_get_all($contents);
        if (empty($tokens)) {
            throw new UnreadableFileException('Contents for file "' . $file . '" deliver zero tokens');
        }

        $namespace = $this->findNamespaceInTokens($tokens);
        $class = $this->findClassNameInTokens($tokens);

        if (empty($class)) {
            throw new EmptyClassNameException(sprintf('Class is empty for file "%s"', $file));
        }

        $class = $namespace ? $namespace . '\\' . $class : $class;
        return $this->normalizeClassName($class);
    }

    /**
     * @param string[] $classNames
     * @return string[]
     */
    public function getDependentClassesFromClasses(array $classNames): array
    {
        $allClassNames = [];
        foreach ($classNames as $className) {
            try {
                $tmpClassNames = $this->classInspector->setClassName($className)->getDependencies();
            } catch (NoClassNameException $exception) {
                $message = 'NoClassNameException for "' . $className . '": ' . $exception->getMessage();
                $this->messageBucket->add($message, MessageGroupLabels::GROUP_EXCEPTION);
                continue;
            } catch (ReflectionException $exception) {
                $message = 'ReflectionException for "' . $className . '": ' . $exception->getMessage();
                $this->messageBucket->add($message, MessageGroupLabels::GROUP_EXCEPTION);
                continue;
            }

            $allClassNames = array_merge($allClassNames, $tmpClassNames);
        }

        return array_unique($allClassNames);
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
     * @param array $tokens
     * @return string
     */
    private function findNamespaceInTokens(array $tokens): string
    {
        $foundNamespace = false;
        $namespace = '';

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $foundNamespace = true;
            }

            if ($foundNamespace === true) {
                if (is_array($token) && in_array((string)$token[0], $this->getNamespaceTokens())) {
                    $namespace .= $token[1];
                } else {
                    if ($token === ';') {
                        $foundNamespace = false;
                    }
                }
            }
        }

        return $namespace;
    }

    /**
     * @return array
     */
    private function getNamespaceTokens(): array
    {
        $namespaceTokens = [T_STRING, T_NS_SEPARATOR];
        if (defined('T_NAME_QUALIFIED')) {
            $namespaceTokens[] = T_NAME_QUALIFIED;
        }

        if (defined('T_NAME_FULLY_QUALIFIED')) {
            $namespaceTokens[] = T_NAME_FULLY_QUALIFIED;
        }

        if (defined('T_NAME_RELATIVE')) {
            $namespaceTokens[] = T_NAME_RELATIVE;
        }

        return $namespaceTokens;
    }

    /**
     * @param array $tokens
     * @return string
     */
    private function findClassNameInTokens(array $tokens): string
    {
        $foundClass = false;
        $class = '';

        foreach ($tokens as $token) {
            if (is_array($token) && ($token[0] === T_CLASS || $token[0] === T_INTERFACE || $token[0] === T_TRAIT)) {
                $foundClass = true;
            }

            if ($foundClass === true) {
                if (is_array($token) && $token[0] === T_STRING) {
                    $class = $token[1];
                    break;
                }
            }
        }

        return $class;
    }
}
