<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

use Yireo\ExtensionChecker\Util\ModuleInfo;

class ModuleCollector
{
    private ModuleInfo $moduleInfo;
    private FileCollector $fileCollector;
    private ClassNameCollector $classNameCollector;

    public function __construct(
        ModuleInfo $moduleInfo,
        FileCollector $fileCollector,
        ClassNameCollector $classNameCollector
    ) {
        $this->moduleInfo = $moduleInfo;
        $this->fileCollector = $fileCollector;
        $this->classNameCollector = $classNameCollector;
    }

    /**
     * @param string $moduleName
     * @return string[]
     */
    public function getClassNamesFromModule(string $moduleName): array
    {
        $moduleFolder = $this->moduleInfo->getModuleFolder($moduleName);
        $files = $this->fileCollector->getFilesFromFolder($moduleFolder);
        return $this->classNameCollector->getClassNamesFromFiles($files);
    }
}
