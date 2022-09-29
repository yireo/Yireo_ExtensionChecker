<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

use Magento\Framework\Filesystem\File\ReadFactory;

class Tokenizer
{
    private ReadFactory $readFactory;

    /**
     * @param ReadFactory $readFactory
     */
    public function __construct(
        ReadFactory $readFactory
    ) {
        $this->readFactory = $readFactory;
    }

    /**
     * @return string[]
     */
    public function getStringTokensFromFilename(string $filename): array
    {
        $source = $this->getSourceFromFilename($filename);
        if (empty($source)) {
            return [];
        }

        $tokens = token_get_all($source);

        $functions = [];
        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_STRING) {
                $functions[] = $token[1];
            }
        }

        return $functions;
    }

    /**
     * @param string $filename
     * @return array
     */
    public function getImportedClassnamesFromFile(string $filename): array
    {
        $source = $this->getSourceFromFilename($filename);
        if (empty($source)) {
            return [];
        }

        return $this->getImportedClassnamesFromSource($source);
    }

    /**
     * @param string $source
     * @return array
     */
    public function getImportedClassnamesFromSource(string $source): array
    {
        if (!preg_match_all('/^use (.*)\;$/m', $source, $matches)) {
            return [];
        }

        $importedClassNames = [];
        foreach ($matches[1] as $match) {
            $match = preg_replace('/ as (.*)/', '', $match);

            $importedClassNames[] = $match;
        }

        return $importedClassNames;
    }

    /**
     * @param string $filename
     * @return array|string
     */
    private function getSourceFromFilename(string $filename)
    {
        if (empty($filename)) {
            return '';
        }

        $read = $this->readFactory->create($filename, 'file');
        return $read->readAll();
    }
}
