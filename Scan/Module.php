<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;

/**
 * Class Module
 *
 * @package Yireo\ExtensionChecker\Scan
 */
class Module
{
    /**
     * @var ModuleList
     */
    private $moduleList;
    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * Module constructor.
     * @param ModuleList $moduleList
     * @param ComponentRegistrar $componentRegistrar
     */
    public function __construct(
        ModuleList $moduleList,
        ComponentRegistrar $componentRegistrar
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
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
     * @return string
     */
    public function getModuleFolder($moduleName): string
    {
        return $this->componentRegistrar->getPath('module', $moduleName);
    }
}
