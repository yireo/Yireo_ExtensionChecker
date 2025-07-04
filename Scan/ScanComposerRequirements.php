<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Composer\Semver\Semver;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Composer\ComposerProvider;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Util\CheckerConfiguration;

class ScanComposerRequirements
{
    private ComposerFileProvider $composerFileProvider;
    private MessageBucket $messageBucket;
    private ComposerProvider $composerProvider;
    private RuntimeConfig $runtimeConfig;
    private CheckerConfiguration $checkerConfiguration;

    public function __construct(
        ComposerFileProvider $composerFileProvider,
        MessageBucket $messageBucket,
        ComposerProvider $composerProvider,
        RuntimeConfig $runtimeConfig,
        CheckerConfiguration $checkerConfiguration
    ) {
        $this->composerFileProvider = $composerFileProvider;
        $this->messageBucket = $messageBucket;
        $this->composerProvider = $composerProvider;
        $this->runtimeConfig = $runtimeConfig;
        $this->checkerConfiguration = $checkerConfiguration;
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
            $this->scanComponentWithComposerRequirements($component, $requirements, $moduleName);
        }

        foreach ($requirements as $requirement => $requirementVersion) {
            $this->scanComposerRequirementWithComponents($moduleName, $requirement, $requirementVersion, $components);
        }
    }

    /**
     * @param Component $component
     * @param array $requirements
     * @param string $moduleName
     * @return void
     */
    private function scanComponentWithComposerRequirements(
        Component $component,
        array $requirements,
        string $moduleName
    ) {
        if ($component->isSoftRequirement()) {
            return;
        }

        if (array_key_exists($component->getPackageName(), $requirements)) {
            return;
        }

        if ($component->getPackageName() == 'symfony/console') {
            return;
        }

        $packageName = !empty($component->getPackageName()) ? $component->getPackageName(
        ) : $component->getComponentName();

        $version = $component->getPackageVersion();
        $message = 'No composer dependency found for "'.$packageName.'"';
        $suggestion = sprintf('Current version is %s. ', $version);
        if ($this->composerProvider->shouldSuggestVersion($packageName)) {
            $suggestion .= sprintf('Perhaps use %s?', $this->composerProvider->getSuggestedVersion($version));
        }

        $this->messageBucket->add(
            $message,
            MessageGroupLabels::GROUP_MISSING_COMPOSER_DEP,
            $suggestion,
            $moduleName
        );
    }

    /**
     * @param string $moduleName
     * @param string $requirement
     * @param string $requirementVersion
     * @param Component[] $components
     * @return void
     */
    private function scanComposerRequirementWithComponents(
        string $moduleName,
        string $requirement,
        string $requirementVersion,
        array $components
    ) {
        $this->checkIfRequirementIsNeeded($moduleName, $requirement, $components);
        $this->checkIfComposerRequirementUsesWildCard($moduleName, $requirement, $requirementVersion);
        $this->checkPhpVersion($moduleName, $requirement, $requirementVersion);
    }

    /**
     * @param string $moduleName
     * @param string $requirement
     * @param array $components
     * @return void
     */
    private function checkIfRequirementIsNeeded(
        string $moduleName,
        string $requirement,
        array $components
    ) {
        if ($this->runtimeConfig->isHideNeedless()) {
            return;
        }

        if ($this->checkerConfiguration->isIgnored($moduleName, $requirement)) {
            return;
        }

        if ($this->isComposerDependencyNeeded($requirement, $components)) {
            return;
        }

        if ($this->runtimeConfig->isComposerPackageWhitelisted($requirement)) {
            return;
        }

        $message = 'Composer requirement "'.$requirement.'" possibly not needed';
        $this->messageBucket->add($message, MessageGroupLabels::GROUP_UNNECESSARY_COMPOSER_DEP);
    }

    /**
     * @param string $moduleName
     * @param string $requirement
     * @param string $requirementVersion
     * @return void
     */
    private function checkIfComposerRequirementUsesWildCard(
        string $moduleName,
        string $requirement,
        string $requirementVersion
    ) {
        if (preg_match('/^ext-/', $requirement)) {
            return;
        }

        if ($requirementVersion !== '*') {
            return;
        }

        $version = $this->composerProvider->getVersionByComposerName($requirement);
        $message = 'Composer requirement "'.$requirement.'" set to wildcard version';
        $suggestion = 'Current version is set to *. ';
        if ($this->composerProvider->shouldSuggestVersion($requirement)) {
            $suggestion .= sprintf('Perhaps use %s?', $this->composerProvider->getSuggestedVersion($version));
        }

        $this->messageBucket->add(
            $message,
            MessageGroupLabels::GROUP_WILDCARD_VERSION,
            $suggestion,
            $moduleName
        );
    }

    /**
     * @param string $moduleName
     * @param string $requirement
     * @param string $requirementVersion
     * @return void
     */
    private function checkPhpVersion(
        string $moduleName,
        string $requirement,
        string $requirementVersion
    ) {
        if ($requirement !== 'php') {
            return;
        }

        $currentVersion = phpversion();
        if (Semver::satisfies($currentVersion, $requirementVersion)) {
            return;
        }

        $message = 'Required PHP version "'.$requirementVersion.'" does not match your current PHP version '.$currentVersion;
        $this->messageBucket->add(
            $message,
            MessageGroupLabels::GROUP_UNMET_REQUIREMENT,
            '',
            $moduleName
        );
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
            'magento/magento-composer-installer',
            'phpstan/phpstan',
            'bitexpert/phpstan-magento',
            'yireo/magento2-integration-test-helper',
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
