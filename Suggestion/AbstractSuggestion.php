<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Suggestion;

use Magento\Framework\Component\ComponentRegistrar;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Exception\UnsafeFileException;

abstract class AbstractSuggestion implements SuggestionInterface
{
    public function __construct(
        protected ComponentRegistrar $componentRegistrar
    ) {
    }

    public function assertSafeCurrentPath(string $moduleName): void
    {
        $path = $this->getCurrentPath($moduleName);
        $modulePath = realpath($this->getModulePath($moduleName));
        $realPath = $this->getRealPath($path);

        if (is_link($path)
            || false === $modulePath
            || false === $realPath
            || ($realPath !== $modulePath && false === str_starts_with($realPath, $modulePath . '/'))
        ) {
            throw new UnsafeFileException(
                'Refusing to access "' . $path . '", because it is a symbolic link or outside of module folder'
            );
        }
    }

    public function getCurrentContents(string $moduleName): string
    {
        $this->assertSafeCurrentPath($moduleName);
        $path = $this->getCurrentPath($moduleName);
        if (false === is_file($path)) {
            return '';
        }

        return (string)file_get_contents($path);
    }

    private function getRealPath(string $path): string|false
    {
        $missingPath = '';
        while (false === file_exists($path) && false === is_link($path)) {
            $parentPath = dirname($path);
            if ($parentPath === $path) {
                return false;
            }

            $missingPath = '/' . basename($path) . $missingPath;
            $path = $parentPath;
        }

        $realPath = realpath($path);
        if (false === $realPath) {
            return false;
        }

        return $realPath . $missingPath;
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
