<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Util;

use Magento\Framework\Component\ComponentRegistrar;
use Yireo\ExtensionChecker\Component\Component;

class CheckerConfiguration
{
    private array $configurations = [];

    public function __construct(
        private ComponentRegistrar $componentRegistrar,
    ) {
    }

    public function getIgnoredComponents(string $moduleName): array
    {
        $this->configurations[$moduleName] ??= $this->getConfiguration($moduleName);
        $configuration = $this->configurations[$moduleName];

        if (isset($configuration['ignore']) && is_array($configuration['ignore'])) {
            return array_values(array_filter($configuration['ignore'], 'is_string'));
        }

        return [];
    }

    public function isIgnored(string $moduleName, string $componentName): bool
    {
        return in_array(
            $componentName, $this->getIgnoredComponents($moduleName), true
        );
    }

    public function isComponentIgnored(
        string $moduleName,
        Component $component
    ): bool {
        $ignoredComponents = $this->getIgnoredComponents($moduleName);
        return in_array($component->getComponentName(), $ignoredComponents, true)
            || in_array($component->getPackageName(), $ignoredComponents, true);
    }

    private function getConfiguration(string $moduleName): array
    {
        $modulePath = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE, $moduleName
        );
        $configurationFile = $modulePath . '/.yireo-extension-checker.json';
        if (false === file_exists($configurationFile)) {
            return [];
        }

        $configuration = json_decode((string)file_get_contents($configurationFile), true);
        if (!is_array($configuration)) {
            return [];
        }

        return $configuration;
    }
}
