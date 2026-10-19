<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Suggestion;

use Magento\Framework\Component\ComponentRegistrar;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;

abstract class AbstractSuggestion implements SuggestionInterface
{
    public function __construct(
        protected ComponentRegistrar $componentRegistrar
    ) {
    }

    public function getCurrentContents(string $moduleName): string
    {
        $path = $this->getCurrentPath($moduleName);
        if (false === is_file($path)) {
            return '';
        }

        return (string)file_get_contents($path);
    }

    protected function getModulePath(string $moduleName): string
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        if (empty($modulePath)) {
            throw new ModuleNotFoundException('Module "' . $moduleName . '" is not registered');
        }

        return rtrim($modulePath, '/');
    }

    /**
     * Make sure the suggested contents only end with a newline if the current file does so too
     */
    protected function matchTrailingNewline(string $contents, string $currentContents): string
    {
        $contents = rtrim($contents, "\n");
        if ($currentContents !== '' && false === str_ends_with($currentContents, "\n")) {
            return $contents;
        }

        return $contents . "\n";
    }
}
