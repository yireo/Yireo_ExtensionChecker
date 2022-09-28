<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

class Tokenizer
{
    /**
     * @return string[]
     */
    public function getStringTokensFromFilename(string $filename): array
    {
        $source = file_get_contents($filename);
        $tokens = token_get_all($source);

        $functions = [];
        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_STRING) {
                $functions[] = $token[1];
            }
        }

        return $functions;
    }
}
