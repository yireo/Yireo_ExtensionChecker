<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use RuntimeException;

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
            } catch (RuntimeException $e) {
                continue;
            }

            $classNames[] = $className;
        }

        return $classNames;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getClassNameFromFile(string $file): string
    {
        $contents = file_get_contents($file);
        $namespace = $class = '';
        $foundNamespace = $foundClass = false;

        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $foundNamespace = true;
            }

            if (is_array($token) && ($token[0] == T_CLASS || $token[0] == T_INTERFACE)) {
                $foundClass = true;
            }

            if ($foundNamespace === true) {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    $namespace .= $token[1];
                } else {
                    if ($token === ';') {
                        $foundNamespace = false;
                    }
                }
            }

            if ($foundClass === true) {
                if (is_array($token) && $token[0] == T_STRING) {
                    $class = $token[1];
                    break;
                }
            }
        }

        if (empty($class)) {
            throw new RuntimeException(sprintf('Class is empty for file "%s"', $file));
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }
}
