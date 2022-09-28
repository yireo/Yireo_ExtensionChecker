<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Component;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManagerInterface;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Composer\ComposerProvider;

class ComponentFactory
{
    private ObjectManagerInterface $objectManager;
    private ComposerFileProvider $composerFileProvider;
    private ComposerProvider $composerProvider;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ComposerFileProvider $composerFileProvider
     * @param ComposerProvider $composerProvider
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ComposerFileProvider $composerFileProvider,
        ComposerProvider $composerProvider
    ) {
        $this->objectManager = $objectManager;
        $this->composerFileProvider = $composerFileProvider;
        $this->composerProvider = $composerProvider;
    }

    /**
     * @param string $moduleName
     * @return Component
     * @throws FileSystemException
     * @throws NotFoundException
     */
    public function createByModuleName(string $moduleName): Component
    {
        $composerFile = $this->composerFileProvider->getComposerFileByModuleName($moduleName);
        $packageName = $composerFile->getName();
        $packageVersion = $this->composerProvider->getVersionByComposerName($packageName);

        return $this->objectManager->create(Component::class, [
            'componentName' => $moduleName,
            'componentType' => ComponentRegistrar::MODULE,
            'packageName' => $packageName,
            'packageVersion' => $packageVersion
        ]);
    }

    /**
     * @param string $libraryName
     * @param string|null $packageVersion
     * @return Component
     */
    public function createByLibraryName(string $libraryName, ?string $packageVersion = null): Component
    {
        if (empty($packageVersion)) {
            $packageVersion = $this->composerProvider->getVersionByComposerName($libraryName);
        }

        return $this->objectManager->create(Component::class, [
            'componentName' => $libraryName,
            'componentType' => ComponentRegistrar::LIBRARY,
            'packageName' => $libraryName,
            'packageVersion' => $packageVersion
        ]);
    }
}
