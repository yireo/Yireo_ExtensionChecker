<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Util;

use Magento\Framework\Filesystem\Io\File;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;

class OtherModuleInfo
{
    private array $otherModuleInfo = [];
    private File $fileReader;

    /**
     * Module constructor.
     * @param File $fileReader
     */
    public function __construct(
        File $fileReader
    ) {
        $this->fileReader = $fileReader;
    }

    /**
     * @param string $modulePath
     * @return array
     */
    public function getByPath(string $modulePath): array
    {
        if (isset($this->otherModuleInfo[$modulePath])) {
            return $this->otherModuleInfo[$modulePath];
        }

        $moduleInfo = $this->loadFromModulePath($modulePath);
        $this->otherModuleInfo[] = $moduleInfo;
        return $moduleInfo;
    }

    /**
     * @param string $moduleName
     * @return array
     */
    public function getByName(string $moduleName): array
    {
        $otherModuleInfo = array_filter($this->otherModuleInfo, fn ($moduleInfo) => $moduleInfo['name'] === $moduleName);
        if (!empty($otherModuleInfo)) {
            return array_shift($otherModuleInfo);
        }

        throw new ModuleNotFoundException('No module "' . $moduleName . '" info found');
    }

    /**
     * @param string $modulePath
     * @return array
     */
    private function loadFromModulePath(string $modulePath): array
    {
        $moduleXmlFile = $modulePath . '/etc/module.xml';
        if (!$this->fileReader->fileExists($moduleXmlFile)) {
            throw new ModuleNotFoundException('No "etc/module.xml" found for path "' . $modulePath . '"');
        }

        $configNode = simplexml_load_file($moduleXmlFile);
        $moduleName = (string)$configNode->module['name'];
        if (empty($moduleName)) {
            throw new ModuleNotFoundException('No useful name found after parsing "' . $moduleXmlFile . '"');
        }

        $moduleSetupVersion = (string)$configNode->module['setup_version'];
        $moduleSequence = [];
        foreach ($configNode->module->sequence->module as $sequenceModule) {
            $moduleSequence[] = (string)$sequenceModule['name'];
        }

        return [
            'path' => $modulePath,
            'name' => $moduleName,
            'setup_version' => $moduleSetupVersion,
            'sequence' => $moduleSequence,
        ];
    }
}
