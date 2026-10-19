<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Suggestion;

interface SuggestionInterface
{
    /**
     * Absolute path of the file that this suggestion applies to
     */
    public function getCurrentPath(string $moduleName): string;

    /**
     * Current contents of the file, or an empty string when the file does not exist yet
     */
    public function getCurrentContents(string $moduleName): string;

    /**
     * Suggested contents of the file
     */
    public function getSuggestedContents(string $moduleName, array $options = []): string;
}
