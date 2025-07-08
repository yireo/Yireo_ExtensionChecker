<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Util;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem\Directory\ReadFactory as DirectoryReadFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\ModuleList;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;

class ModuleInfo
{
    private ModuleList $moduleList;
    private ComponentRegistrar $componentRegistrar;
    private File $fileReader;
    private DirectoryList $directoryList;
    private DirectoryReadFactory $directoryReadFactory;
    private OtherModuleInfo $otherModuleInfo;

    /**
     * Module constructor.
     * @param ModuleList $moduleList
     * @param ComponentRegistrar $componentRegistrar
     * @param File $fileReader
     * @param DirectoryList $directoryList
     * @param DirectoryReadFactory $directoryReadFactory
     * @param OtherModuleInfo $otherModuleInfo
     */
    public function __construct(
        ModuleList $moduleList,
        ComponentRegistrar $componentRegistrar,
        File $fileReader,
        DirectoryList $directoryList,
        DirectoryReadFactory $directoryReadFactory,
        OtherModuleInfo $otherModuleInfo
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->fileReader = $fileReader;
        $this->directoryList = $directoryList;
        $this->directoryReadFactory = $directoryReadFactory;
        $this->otherModuleInfo = $otherModuleInfo;
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function isKnown(string $moduleName): bool
    {
        $paths = $this->componentRegistrar->getPaths(ComponentRegistrar::MODULE);
        if (false === array_key_exists($moduleName, $paths)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $moduleName
     * @return string
     * @throws ModuleNotFoundException
     */
    public function getModuleFolder(string $moduleName): string
    {
        $moduleInfo = $this->getModuleInfo($moduleName);
        $moduleFolder = $moduleInfo['path'];

        if (!$this->fileReader->fileExists($moduleFolder . '/registration.php')) {
            $msg = (string)__('Module folder "' . $moduleFolder . '" for module "' . $moduleName . '" is empty');
            throw new ModuleNotFoundException($msg);
        }

        return $moduleFolder;
    }

    /**
     * @param string $modulePath
     * @return string
     * @throws FileSystemException
     * @throws ValidatorException
     */
    public function getModuleNameFromPath(string $modulePath): string
    {
        $modulePath = $this->normalizePath($modulePath);
        $paths = $this->componentRegistrar->getPaths(ComponentRegistrar::MODULE);
        if (in_array($modulePath, $paths)) {
            return array_search($modulePath, $paths);
        }

        $moduleInfo = $this->otherModuleInfo->getByPath($modulePath);
        return $moduleInfo['name'];
    }

    /**
     * @param string $moduleName
     * @return array
     */
    public function getModuleInfo(string $moduleName): array
    {
        $moduleInfo = $this->moduleList->getOne($moduleName);
        if (!empty($moduleInfo)) {
            $moduleInfo['path'] = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
            return $moduleInfo;
        }

        return $this->otherModuleInfo->getByName($moduleName);
    }

    /**
     * @param string $path
     * @return string
     * @throws FileSystemException
     * @throws ValidatorException
     */
    private function normalizePath(string $path): string
    {
        $directoryRead = $this->directoryReadFactory->create($this->directoryList->getRoot());
        if ($directoryRead->isExist($path)) {
            return $path;
        }

        $absolutePath = $directoryRead->getAbsolutePath($path);
        if ($directoryRead->isExist($absolutePath)) {
            return $absolutePath;
        }

        return $path;
    }
}
