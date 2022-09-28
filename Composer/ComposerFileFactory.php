<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

use Magento\Framework\ObjectManagerInterface;

class ComposerFileFactory
{
    private ObjectManagerInterface $objectManager;

    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(string $composerFile): ComposerFile
    {
        return $this->objectManager->create(ComposerFile::class, ['composerFile' => $composerFile]);
    }
}
