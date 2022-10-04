<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\XmlLayout;

use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;

class ComponentCollector
{
    private FileReadFactory $fileReadFactory;
    private ComponentFactory $componentFactory;

    public function __construct(
        FileReadFactory $fileReadFactory,
        ComponentFactory $componentFactory
    ) {
        $this->fileReadFactory = $fileReadFactory;
        $this->componentFactory = $componentFactory;
    }

    /**
     * @param string $file
     * @return Component[]
     */
    public function getComponentsFromFile(string $file): array
    {
        $fileRead = $this->fileReadFactory->create($file, 'file');
        $xmlContents = $fileRead->readAll();

        if (!preg_match_all('/([A-Za-z0-9]+)_([A-Za-z0-9]+)::/', $xmlContents, $matches)) {
            return [];
        }

        $components = [];
        foreach ($matches[0] as $matchIndex => $match) {
            $moduleName = $matches[1][$matchIndex] . '_' .$matches[2][$matchIndex];
            $component = $this->componentFactory->createByModuleName($moduleName);
            $components[] = $component;
        }

        return $components;
    }
}
