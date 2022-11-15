<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass\ClassInspector;

interface ClassDetectorInterface
{
    /**
     * @param string $phpFileContent
     * @return string[]
     */
    public function getClassNames(string $phpFileContent): array;
}
