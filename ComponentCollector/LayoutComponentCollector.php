<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentCollector;

use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\PhpClass\ComponentCollector;

class LayoutComponentCollector extends AbstractComponentCollector
{
    private FileReadFactory $fileReadFactory;

    /**
     * @var string[]
     */
    private array $patterns;

    public function __construct(
        ComponentFactory $componentFactory,
        ComponentCollector $componentCollector,
        FileReadFactory $fileReadFactory,
        array $patterns = ['hyva_modal' => 'Hyva_Theme']
    ) {
        parent::__construct($componentFactory, $componentCollector);
        $this->fileReadFactory = $fileReadFactory;
        $this->patterns = $patterns;
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
        $components = array_merge($components, $this->findComponentsByPattern($contents, $this->patterns));
        return $components;
    }
}
