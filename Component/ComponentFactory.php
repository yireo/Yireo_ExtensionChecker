<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Component;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\PackageInfo;
use Yireo\ExtensionChecker\Scan\Composer;

class ComponentFactory
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    
    /**
     * @var PackageInfo
     */
    private $packageInfo;
    
    /**
     * @var Composer
     */
    private $composer;
    
    /**
     * @param ObjectManager $objectManager
     * @param PackageInfo $packageInfo
     * @param Composer $composer
     */
    public function __construct(
        ObjectManager $objectManager,
        PackageInfo $packageInfo,
        Composer $composer
    ) {
        $this->objectManager = $objectManager;
        $this->packageInfo = $packageInfo;
        $this->composer = $composer;
    }
    
    public function createByModuleName(string $moduleName): Component
    {
        $packageName = $this->packageInfo->getPackageName($moduleName);
        $packageVersion = $this->composer->getVersionByPackage($packageName);
        
        return $this->objectManager->create(Component::class, [
            'componentName' => $moduleName,
            'componentType' => ComponentRegistrar::MODULE,
            'packageName' => $packageName,
            'packageVersion' => $packageVersion
        ]);
    }
    
    public function createByLibraryName(string $libraryName): Component
    {
        $packageVersion = $this->composer->getVersionByPackage($libraryName);
        
        return $this->objectManager->create(Component::class, [
            'componentName' => $libraryName,
            'componentType' => ComponentRegistrar::LIBRARY,
            'packageName' => $libraryName,
            'packageVersion' => $packageVersion
        ]);
    }
}
