<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

use Magento\Framework\App\ObjectManager;

class ComposerFileFactory
{
    private ObjectManager $objectManager;
    
    public function __construct(
        ObjectManager $objectManager
    ) {
        $this->objectManager = $objectManager;
    }
    
    public function create(string $composerFile): ComposerFile
    {
        return $this->objectManager->create(ComposerFile::class, ['composerFile' => $composerFile]);
    }
}
