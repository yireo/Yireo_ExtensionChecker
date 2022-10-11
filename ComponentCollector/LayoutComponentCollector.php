<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentCollector;

use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;

class LayoutComponentCollector extends AbstractComponentCollector
{
    private FileReadFactory $fileReadFactory;

    public function __construct(
        FileReadFactory $fileReadFactory,
        ComponentFactory $componentFactory
    ) {
        $this->fileReadFactory = $fileReadFactory;
        parent::__construct($componentFactory);
    }

    /**
     * @param string $file
     * @return Component[]
     */
    public function getComponentsFromFile(string $file): array
    {
        $fileRead = $this->fileReadFactory->create($file, 'file');
        $contents = $fileRead->readAll();
        $components = $this->findComponentsByModuleName($contents);
        $patterns = ['hyva_modal' => 'Hyva_Theme'];
        $components = array_merge($components, $this->findComponentsByPattern($contents, $patterns));
        return $components;
    }
}
