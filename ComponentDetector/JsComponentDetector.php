<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Yireo\ExtensionChecker\Component\Component;

/**
 * Detect components from a module its JS files
 */
class JsComponentDetector implements ComponentDetectorInterface
{
    /**
     * @param string $moduleName
     * @return Component[]
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        return [];
    }
}
