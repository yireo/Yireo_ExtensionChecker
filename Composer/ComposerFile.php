<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Shell;
use RuntimeException;

class ComposerFile
{
    private ReadFactory $readFactory;
    private SerializerInterface $serializer;
    private string $composerFile;
    
    /**
     * Composer constructor.
     * @param DirectoryList $directoryList
     * @param ReadFactory $readFactory
     * @param SerializerInterface $serializer
     * @param Shell $shell
     * @param string $composerFile
     */
    public function __construct(
        DirectoryList $directoryList,
        ReadFactory $readFactory,
        SerializerInterface $serializer,
        Shell $shell,
        string $composerFile
    ) {
        $this->directoryList = $directoryList;
        $this->readFactory = $readFactory;
        $this->serializer = $serializer;
        $this->shell = $shell;
        $this->composerFile = $composerFile;
    }
    
    /**
     * @return array
     * @throws NotFoundException
     */
    public function getData(): array
    {
        $read = $this->readFactory->create($this->composerFile, 'file');
        $composerContents = $read->readAll();
        $extensionData = $this->serializer->unserialize($composerContents);
        if (empty($extensionData)) {
            throw new RuntimeException('Empty contents after decoding file "' . $this->composerFile . '"');
        }
        
        return $extensionData;
    }
    
    /**
     * @param string $keyName
     * @return mixed
     * @throws NotFoundException
     */
    public function get(string $keyName)
    {
        $extensionData = $this->getData();
        if (!isset($extensionData[$keyName])) {
            throw new RuntimeException('File "' . $this->composerFile . '" does not have a "' . $keyName . '"');
        }
        
        return $extensionData[$keyName];
    }
    
    /**
     * @return string
     * @throws NotFoundException
     */
    public function getName(): string
    {
        return $this->get('name');
    }
    
    /**
     * @return array
     * @throws NotFoundException
     * @throws RuntimeException
     */
    public function getRequirements(): array
    {
        // @todo: Merge this with require-dev?
        return $this->get('require');
    }
}
