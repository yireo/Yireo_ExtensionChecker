<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use RuntimeException;
use Throwable;

class ClassCollector
{
    /**
     * @var FileCollector
     */
    private $fileCollector;

    /**
     * ClassCollector constructor.
     *
     */
    public function __construct(
        FileCollector $fileCollector
    ) {
        $this->fileCollector = $fileCollector;
    }

    /**
     * @param string $folder
     *
     * @return array
     */
    public function getClassesFromFolder(string $folder): array
    {
        $classNames = [];
        $files = $this->fileCollector->getFilesFromFolder($folder);

        foreach ($files as $file) {
            try {
                $className = $this->getClassNameFromFile($file);
            } catch (Throwable $e) {
                continue;
            }

            $classNames[] = $this->normalizeClassName($className);
        }

        return $classNames;
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
     * @param string $file
     *
     * @return string
     */
    private function getClassNameFromFile(string $file): string
    {
        $contents = file_get_contents($file);
        $tokens = token_get_all($contents);
        $namespace = $this->findNamespaceInTokens($tokens);
        $class = $this->findClassNameInTokens($tokens);

        if (empty($class)) {
            throw new RuntimeException(sprintf('Class is empty for file "%s"', $file));
        }

        return $namespace ? $namespace . '\\' . $class : $class;
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
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED])) {
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
     * @param array $tokens
     * @return string
     */
    private function findClassNameInTokens(array $tokens): string
    {
        $foundClass = false;
        $class = '';

        foreach ($tokens as $token) {
            if (is_array($token) && ($token[0] === T_CLASS || $token[0] === T_INTERFACE)) {
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
