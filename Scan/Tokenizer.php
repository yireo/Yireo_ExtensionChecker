<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Magento\Framework\App\Filesystem\DirectoryList;
use PHP_Token_Stream;

/**
 * Class Tokenizer
 * @package Jola\ExtensionChecker\Scan
 */
class Tokenizer
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * Tokenizer constructor.
     * @param DirectoryList $directoryList
     */
    public function __construct(
        DirectoryList $directoryList
    ) {
        $this->directoryList = $directoryList;
    }

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
