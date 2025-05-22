<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Util;

use Magento\Framework\Component\ComponentRegistrar;

class CheckerConfiguration
{
    public function __construct(
        private ComponentRegistrar $componentRegistrar,
    ) {
    }

    public function getIgnoredComponents(string $moduleName): array
    {
        $configuration = $this->getConfiguration($moduleName);
        if (array_key_exists('ignore', $configuration)) {
            return $configuration['ignore'];
        }

        return [];
    }

    public function isIgnored(string $moduleName, string $componentName): bool
    {
        return in_array($componentName, $this->getIgnoredComponents($moduleName));
    }

    private function getConfiguration(string $moduleName): array
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $configurationFile = $modulePath.'/.yireo-extension-checker.json';
        if (false === file_exists($configurationFile)) {
            return [];
        }

        return json_decode(file_get_contents($configurationFile), true);
    }
}
