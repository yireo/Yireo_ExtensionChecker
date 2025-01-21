<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Config;

class RuntimeConfig
{
    private bool $hideDeprecated = false;
    private bool $hideNeedless = false;
    private bool $verbose = false;
    private array $whitelistedComposerPackages = ['magento/module-store'];
    private array $whitelistedMagentoModules = ['Magento_Store'];

    /**
     * @param bool $hideDeprecated
     * @return RuntimeConfig
     */
    public function setHideDeprecated(bool $hideDeprecated): RuntimeConfig
    {
        $this->hideDeprecated = $hideDeprecated;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHideDeprecated(): bool
    {
        return $this->hideDeprecated;
    }

    /**
     * @param bool $hideNeedless
     * @return RuntimeConfig
     */
    public function setHideNeedless(bool $hideNeedless): RuntimeConfig
    {
        $this->hideNeedless = $hideNeedless;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHideNeedless(): bool
    {
        return $this->hideNeedless;
    }

    /**
     * @param bool $verbose
     * @return RuntimeConfig
     */
    public function setVerbose(bool $verbose): RuntimeConfig
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function isModuleWhitelisted(string $moduleName): bool
    {
        return in_array($moduleName, $this->whitelistedMagentoModules);
    }

    public function isComposerPackageWhitelisted(string $composerPackage): bool
    {
        return in_array($composerPackage, $this->whitelistedComposerPackages);
    }
}
