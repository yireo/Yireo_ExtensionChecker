<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Component;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManagerInterface;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Composer\ComposerProvider;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Message\MessageBucket;

class ComponentFactory
{
    private ObjectManagerInterface $objectManager;
    private ComposerFileProvider $composerFileProvider;
    private ComposerProvider $composerProvider;
    private MessageBucket $messageBucket;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ComposerFileProvider $composerFileProvider
     * @param ComposerProvider $composerProvider
     * @param MessageBucket $messageBucket
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ComposerFileProvider $composerFileProvider,
        ComposerProvider $composerProvider,
        MessageBucket $messageBucket
    ) {
        $this->objectManager = $objectManager;
        $this->composerFileProvider = $composerFileProvider;
        $this->composerProvider = $composerProvider;
        $this->messageBucket = $messageBucket;
    }

    /**
     * @param string $moduleName
     * @return Component
     */
    public function createByModuleName(string $moduleName, bool $hardRequirement = false): Component
    {
        try {
            $composerFile = $this->composerFileProvider->getComposerFileByModuleName($moduleName);
            $packageName = $composerFile->getName();
        } catch (FileSystemException|NotFoundException|ModuleNotFoundException $e) {
            $packageName = '';
            $this->messageBucket->debug($e->getMessage());
        }

        $packageVersion = $this->composerProvider->getVersionByComposerName($packageName);

        return $this->objectManager->create(Component::class, [
            'componentName' => $moduleName,
            'componentType' => ComponentRegistrar::MODULE,
            'packageName' => $packageName,
            'packageVersion' => $packageVersion,
            'hardRequirement' => $hardRequirement
        ]);
    }

    /**
     * @param string $libraryName
     * @param string|null $packageVersion
     * @return Component
     */
    public function createByLibraryName(string $libraryName, ?string $packageVersion = null, bool $hardRequirement = false): Component
    {
        if (empty($packageVersion)) {
            $packageVersion = $this->composerProvider->getVersionByComposerName($libraryName);
        }

        return $this->objectManager->create(Component::class, [
            'componentName' => $libraryName,
            'componentType' => ComponentRegistrar::LIBRARY,
            'packageName' => $libraryName,
            'packageVersion' => $packageVersion,
            'hardRequirement' => $hardRequirement
        ]);
    }
}
