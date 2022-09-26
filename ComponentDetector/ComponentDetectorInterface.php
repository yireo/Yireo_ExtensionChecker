<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Yireo\ExtensionChecker\Component\Component;

interface ComponentDetectorInterface
{
    /**
     * @param string $moduleName
     * @return Component
     */
    public function getComponentsByModuleName(string $moduleName): array;
}
