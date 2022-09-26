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
    private $moduleName;
    private ModuleList $moduleList;
    private ComponentRegistrar $componentRegistrar;
    private PackageInfo $packageInfo;
    private File $fileReader;
    private Json $jsonSerializer;
    private FileDriver $fileDriver;
    
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
        PackageInfo $packageInfo,
        File $fileReader,
        Json $jsonSerializer,
        FileDriver $fileDriver
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->packageInfo = $packageInfo;
        $this->fileReader = $fileReader;
        $this->jsonSerializer = $jsonSerializer;
        $this->fileDriver = $fileDriver;
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
        $this->moduleName = $moduleName;
        return $this->moduleList->getOne($moduleName);
    }

    /**
     * @param string $moduleName
     * @return array
     * @deprecated Move this to ComposerFile class instead
     */
    public function getPackageInfo(string $moduleName): array
    {
        $this->moduleName = $moduleName;

        $info = [];
        $info['name'] = $this->packageInfo->getPackageName($moduleName);
        $info['version'] = $this->packageInfo->getVersion($moduleName);
        $info['requirements'] = $this->getRequirements();
        $info['dependencies'] = $this->getDependencies();

        return $info;
    }

    /**
     * @return array
     * @deprecated Move this to ComposerFile class instead
     */
    private function getDependencies(): array
    {
        $dependencies = [];
        $composerData = $this->getComposerJsonData();

        $sources = [
            'require',
            'require-dev',
        ];

        foreach ($sources as $source) {
            if (!empty($composerData[$source])) {
                $dependencies[] = array_filter(array_keys($composerData[$source]));
            }
        }

        if (!$dependencies) {
            return [];
        }

        return array_unique(array_merge(...$dependencies));
    }

    /**
     * @return string[]
     * @deprecated Move this to ComposerFile class instead
     */
    private function getRequirements(): array
    {
        $requirements = [];
        $moduleRequirements = $this->packageInfo->getRequire($this->moduleName);

        foreach ($moduleRequirements as $moduleRequirement) {
            if (!$moduleRequirement) {
                continue;
            }

            $requirements[] = trim($moduleRequirement);
        }

        $requirements = array_merge($requirements, $this->getAdditionalRequirements());

        return $requirements;
    }

    /**
     * @return array
     * @deprecated Move this to ComposerFile class instead
     */
    private function getAdditionalRequirements(): array
    {
        $requirements = [];
        $composerData = $this->getComposerJsonData();

        if (!empty($composerData['require']['magento/framework'])) {
            $requirements[] = 'magento/framework';
        }

        return $requirements;
    }

    /**
     * @return array
     * @deprecated Move this to ComposerFile class instead
     */
    private function getComposerJsonData(): array
    {
        try {
            $composerFile = $this->getComposerFile($this->moduleName);
        } catch (ComponentNotFoundException $componentNotFoundException) {
            return [];
        }

        $contents = $this->fileReader->read($composerFile);
        return $this->jsonSerializer->unserialize($contents);
    }
    
    /**
     * @param string $moduleName
     * @return string
     * @throws FileSystemException
     * @deprecated Move this to ComposerFile class instead
     */
    public function getComposerFile(string $moduleName): string
    {
        $composerFile = $this->getModuleFolder($moduleName) . '/composer.json';
        if ($this->fileDriver->isExists($composerFile)) {
            return $composerFile;
        }

        $composerFile = $this->getModuleFolder($moduleName) . '/../composer.json';
        if ($this->fileDriver->isExists($composerFile)) {
            return $composerFile;
        }

        throw new ComponentNotFoundException('No composer.json for module "' . $moduleName . '"');
    }
}
