<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Serialize\SerializerInterface;
use RuntimeException;

class ComposerFile
{
    private ReadFactory $readFactory;
    private SerializerInterface $serializer;
    private string $composerFile;

    /**
     * Composer constructor.
     * @param ReadFactory $readFactory
     * @param SerializerInterface $serializer
     * @param string $composerFile
     */
    public function __construct(
        ReadFactory $readFactory,
        SerializerInterface $serializer,
        string $composerFile
    ) {
        $this->readFactory = $readFactory;
        $this->serializer = $serializer;
        $this->composerFile = $composerFile;
    }

    /**
     * @return array
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
     * @throws RuntimeException
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
