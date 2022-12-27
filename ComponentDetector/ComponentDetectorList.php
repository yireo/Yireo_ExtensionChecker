<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Yireo\ExtensionChecker\Component\Component;

class ComponentDetectorList
{
    /**
     * @var ComponentDetectorInterface[]
     */
    private array $componentDetectors;

    /**
     * @param ComponentDetectorInterface[] $componentDetectors
     */
    public function __construct(
        array $componentDetectors = []
    ) {
        $this->componentDetectors = $componentDetectors;
    }

    /**
     * @return ComponentDetectorInterface[]
     */
    public function getComponentDetectors(): array
    {
        return $this->componentDetectors;
    }

    /**
     * @param string $moduleName
     * @return Component[]
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        $components = [];
        foreach ($this->componentDetectors as $componentDetector) {
            $components = array_merge($components, $componentDetector->getComponentsByModuleName($moduleName));
        }

        $components = array_filter($components, fn ($component) => $component->getComponentName() !== $moduleName);
        $components = array_unique($components, SORT_REGULAR);
        $components = $this->filterSoftAndHardDuplicates($components);

        return $components;
    }

    /**
     * @param Component[] $components
     * @return void
     */
    private function filterSoftAndHardDuplicates(array $components): array
    {
        foreach ($components as $index => $component) {
            if ($component->isHardRequirement()) {
                continue;
            }

            foreach ($components as $c) {
                if ($c->getComponentName() === $component->getComponentName() && $c->isHardRequirement()) {
                    unset($components[$index]);
                }
            }
        }

        return $components;
    }
}
