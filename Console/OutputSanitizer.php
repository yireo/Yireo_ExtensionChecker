<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Console;

use Symfony\Component\Console\Formatter\OutputFormatter;

trait OutputSanitizer
{
    protected function stripControlCharacters(string $text): string
    {
        $strippedText = preg_replace('/[\x{00}-\x{08}\x{0B}-\x{1F}\x{7F}-\x{9F}]/u', '', $text);
        if (is_string($strippedText)) {
            return $strippedText;
        }

        return (string)preg_replace('/[\x00-\x08\x0B-\x1F\x7F-\x9F]/', '', $text);
    }

    protected function escapeOutput(string $text): string
    {
        return OutputFormatter::escape($this->stripControlCharacters($text));
    }
}
