<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Yireo\ExtensionChecker\Exception\EmptyClassNameException;
use Yireo\ExtensionChecker\Exception\UnreadableFileException;

class ClassCollector
{
    /**
     * @param string $file
     *
     * @return string
     * @throws UnreadableFileException
     * @throws EmptyClassNameException
     */
    public function getClassNameFromFile(string $file): string
    {
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
                if (is_array($token) && @in_array($token[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED])) {
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
