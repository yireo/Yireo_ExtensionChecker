<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Util;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Yireo\ExtensionChecker\Exception\ComponentNotFoundException;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;

class ModuleInfo
{
    private ModuleList $moduleList;
    private ComponentRegistrar $componentRegistrar;
    private File $fileReader;
    
    /**
     * Module constructor.
     * @param ModuleList $moduleList
     * @param ComponentRegistrar $componentRegistrar
     * @param PackageInfo $packageInfo
     * @param File $fileReader
     * @param Json $jsonSerializer
     */
    public function __construct(
        ModuleList $moduleList,
        ComponentRegistrar $componentRegistrar,
        File $fileReader
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->fileReader = $fileReader;
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function isKnown(string $moduleName): bool
    {
        if (!in_array($moduleName, $this->moduleList->getNames())) {
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
        $moduleFolder = $this->componentRegistrar->getPath('module', $moduleName);
        if (!$this->fileReader->fileExists($moduleFolder . '/registration.php')) {
            $msg = (string)__('Module folder "' . $moduleFolder . '" for module "' . $moduleName . '" is empty');
            throw new ModuleNotFoundException($msg);
        }

        return $moduleFolder;
    }

    /**
     * @param string $moduleName
     * @return array
     */
    public function getModuleInfo(string $moduleName): array
    {
        return $this->moduleList->getOne($moduleName);
    }
}
