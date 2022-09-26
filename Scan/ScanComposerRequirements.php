<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Composer\Semver\Semver;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Composer\ComposerProvider;
use Yireo\ExtensionChecker\Message\MessageBucket;

class ScanComposerRequirements
{
    private ComposerFileProvider $composerFileProvider;
    private MessageBucket $messageBucket;
    private ComposerProvider $composerProvider;
    
    public function __construct(
        ComposerFileProvider $composerFileProvider,
        MessageBucket $messageBucket,
        ComposerProvider $composerProvider
    ) {
        $this->composerFileProvider = $composerFileProvider;
        $this->messageBucket = $messageBucket;
        $this->composerProvider = $composerProvider;
    }
    
    /**
     * @param string $moduleName
     * @param Component[] $components
     * @return void
     * @throws FileSystemException
     * @throws NotFoundException
     */
    public function scan(string $moduleName, array $components)
    {
        $composerFile = $this->composerFileProvider->getComposerFileByModuleName($moduleName);
        $requirements = $composerFile->getRequirements();
        
        foreach ($components as $component) {
            $this->scanComponentWithComposerRequirements($component, $requirements);
        }
        
        foreach ($requirements as $requirement => $requirementVersion) {
            $this->scanComposerRequirementWithComponents($requirement, $requirementVersion, $components);
        }
    }
    
    /**
     * @param Component $component
     * @param array $requirements
     * @return void
     */
    private function scanComponentWithComposerRequirements(Component $component, array $requirements)
    {
        if (in_array($component->getPackageName(), $requirements)) {
            return;
        }
        
        $packageName = $component->getPackageName();
        $version = $component->getPackageVersion();
        $msg = sprintf('Dependency "%s" not found in composer.json. ', $packageName);
        $msg .= sprintf('Current version is %s. ', $version);
        $msg .= sprintf('Perhaps use %s?', $this->composerProvider->getSuggestedVersion($version));
        $this->messageBucket->addWarning($msg);
    }
    
    /**
     * @param string $requirement
     * @param string $requirementVersion
     * @param Component[] $components
     * @return void
     */
    private function scanComposerRequirementWithComponents(
        string $requirement,
        string $requirementVersion,
        array $components
    ) {
        $this->checkIfRequirementIsNeeded($requirement, $components);
        $this->checkIfComposerRequirementUsesWildCard($requirement, $requirementVersion);
        $this->checkPhpVersion($requirement, $requirementVersion);
    }
    
    /**
     * @param string $requirement
     * @param array $components
     * @return void
     */
    private function checkIfRequirementIsNeeded(string $requirement, array $components)
    {
        if ($this->isComposerDependencyNeeded($requirement, $components)) {
            return;
        }
        
        $msg = sprintf('Dependency "%s" from composer.json possibly not needed.', $requirement);
        $this->messageBucket->addWarning($msg);
    }
    
    private function checkIfComposerRequirementUsesWildCard(string $requirement, string $requirementVersion)
    {
        if (preg_match('/^ext-/', $requirement)) {
            return;
        }
        
        if ($requirementVersion !== '*') {
            return;
        }
        
        $version = $this->composer->getVersionByPackage($requirement);
        $msg = 'Composer dependency "' . $requirement . '" is set to version *. ';
        $msg .= sprintf('Current version is %s. ', $version);
        $msg .= sprintf('Perhaps use %s?', $this->getSuggestedVersion($version));
        $this->messageBucket->addWarning($msg);
    }
    
    /**
     * @param string $requirement
     * @param string $requirementVersion
     * @return void
     */
    private function checkPhpVersion(string $requirement, string $requirementVersion)
    {
        if ($requirement !== 'php') {
            return;
        }
        
        $currentVersion = phpversion();
        if (Semver::satisfies($currentVersion, $requirementVersion)) {
            return;
        }
        
        $msg = 'Required PHP version "' . $requirementVersion . '" does not match your current PHP version ' . $currentVersion;
        $this->messageBucket->addWarning($msg);
    }
    
    /**
     * @param string $dependency
     * @param Component[] $components
     * @return bool
     */
    private function isComposerDependencyNeeded(string $dependency, array $components): bool
    {
        foreach ($components as $component) {
            if ($component->getPackageName() === $dependency) {
                return true;
            }
        }
        
        $validDependencies = [
            'php',
            'magento/magento-composer-installer'
        ];
        
        if (\in_array($dependency, $validDependencies)) {
            return true;
        }
        
        if ($dependency === 'magento/framework') {
            return true;
        }
        
        if (str_starts_with($dependency, 'ext-')) {
            return true;
        }
        
        return false;
    }
}
