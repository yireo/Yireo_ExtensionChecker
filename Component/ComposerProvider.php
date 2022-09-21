<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Component;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\ModuleListInterface;
use Yireo\ExtensionChecker\Exception\ComponentNotFoundException;

class ComposerProvider
{
    private ModuleListInterface $moduleList;
    private ComponentRegistrar $componentRegistrar;
    private File $fileDriver;

    public function __construct(
        ModuleListInterface $moduleList,
        ComponentRegistrar $componentRegistrar,
        File $fileDriver
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->fileDriver = $fileDriver;
    }

    public function getComposerFile(string $moduleName)
    {
        $module = $this->moduleList->getOne($moduleName);
        if (!$module) {
            throw new ComponentNotFoundException('Unknown module "'.$moduleName.'"');
        }

        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $possibleComposerPaths = [
            $modulePath . '/composer.json',
            $modulePath . '/../composer.json',
            $modulePath . '/../../composer.json',
        ];

        foreach ($possibleComposerPaths as $possibleComposerPath) {
            if ($this->fileDriver->isExists($possibleComposerPath)) {
                return $possibleComposerPath;
            }
        }

        return $possibleComposerPath;
    }
}