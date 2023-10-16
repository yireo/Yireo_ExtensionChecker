<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentCollector;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;

class AbstractComponentCollector
{
    protected ComponentFactory $componentFactory;

    /**
     * @param ComponentFactory $componentFactory
     */
    public function __construct(
        ComponentFactory $componentFactory
    ) {
        $this->componentFactory = $componentFactory;
    }

    /**
     * @param string $content
     * @return Component[]
     * @throws FileSystemException
     * @throws NotFoundException
     */
    protected function findComponentsByModuleName(string $content, bool $hardRequirement = false): array
    {
        if (!preg_match_all('/([A-Za-z0-9]+)_([A-Za-z0-9]+)::/', $content, $matches)) {
            return [];
        }
        
        $components = [];
        foreach ($matches[0] as $matchIndex => $match) {
            $moduleName = $matches[1][$matchIndex] . '_' . $matches[2][$matchIndex];
            $components[] = $this->componentFactory->createByModuleName($moduleName, $hardRequirement);
        }

        return $components;
    }

    /**
     * @param string $contents
     * @param array $patterns
     * @return Component[]
     * @throws FileSystemException
     * @throws NotFoundException
     */
    protected function findComponentsByPattern(string $contents, array $patterns, bool $hardRequirement = false): array
    {
        $components = [];
        foreach ($patterns as $search => $moduleName) {
            if (strstr($contents, $search)) {
                $components[] = $this->componentFactory->createByModuleName($moduleName, $hardRequirement);
            }
        }

        return $components;
    }
}
