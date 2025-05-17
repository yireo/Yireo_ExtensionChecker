<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentCollector;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\PhpClass\ComponentCollector;

class XmlComponentCollector extends AbstractComponentCollector
{
    private FileReadFactory $fileReadFactory;

    public function __construct(
        FileReadFactory $fileReadFactory,
        ComponentFactory $componentFactory,
        ComponentCollector $componentCollector
    ) {
        $this->fileReadFactory = $fileReadFactory;
        parent::__construct($componentFactory, $componentCollector);
    }

    /**
     * @param string $file
     * @return Component[]
     * @throws FileSystemException
     * @throws NotFoundException
     */
    public function getComponentsFromFile(string $file): array
    {
        $fileRead = $this->fileReadFactory->create($file, 'file');
        $contents = $fileRead->readAll();
        $components = $this->findComponentsByModuleName($contents, true);
        $components = array_merge($components, $this->findComponentsByClassName($contents, true));

        // @todo: Move this into a DI-type based configuration
        $patterns = [
            'hyva.modal' => 'Hyva_Theme',
            '$viewModels->require' => 'Hyva_Theme',
        ];
        $components = array_merge($components, $this->findComponentsByPattern($contents, $patterns, true));
        return $components;
    }
}
