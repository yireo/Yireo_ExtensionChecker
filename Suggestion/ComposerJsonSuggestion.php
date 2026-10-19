<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Suggestion;

use Magento\Framework\Component\ComponentRegistrar;
use stdClass;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Composer\ComposerProvider;
use Yireo\ExtensionChecker\Composer\ComposerRequirementsMerger;
use Yireo\ExtensionChecker\Exception\ComposerException;
use Yireo\ExtensionChecker\Exception\ComposerFileNotFoundException;

class ComposerJsonSuggestion extends AbstractSuggestion
{
    public const OPTION_WITH_PHP = 'with-php';
    public const PHP_VERSION_CONSTRAINT = '>=8.2 <8.6';

    private const FIXED_VERSION_CONSTRAINTS = [
        'psr/log' => '^1.0 || ^2.0 || ^3.0',
        'symfony/console' => '^5.0 | ^6.0 | ^7.0',
    ];

    public function __construct(
        ComponentRegistrar $componentRegistrar,
        private ComponentDetectorList $componentDetectorList,
        private ComposerProvider $composerProvider,
        private ComposerRequirementsMerger $composerRequirementsMerger
    ) {
        parent::__construct($componentRegistrar);
    }

    public function getCurrentPath(string $moduleName): string
    {
        return $this->getModulePath($moduleName) . '/composer.json';
    }

    public function getSuggestedContents(string $moduleName, array $options = []): string
    {
        $currentContents = $this->getCurrentContents($moduleName);
        $composerData = $this->getComposerData($moduleName, $currentContents);

        [$composerDependencies, $composerDevDependencies] = $this->getDependencies($moduleName);
        if (!empty($options[self::OPTION_WITH_PHP])) {
            $composerDependencies['php'] = self::PHP_VERSION_CONSTRAINT;
        }

        $require = $this->composerRequirementsMerger->mergeRequire(
            $composerData['require'] ?? [],
            $composerDependencies
        );
        $requireDev = $this->composerRequirementsMerger->mergeRequireDev(
            $composerData['require-dev'] ?? [],
            $composerDevDependencies,
            $require
        );

        $composerData = $this->setRequirements($composerData, $require, $requireDev);
        $composerJson = (string)json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $this->matchTrailingNewline($composerJson, $currentContents);
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private function getDependencies(string $moduleName): array
    {
        $components = $this->componentDetectorList->getComponentsByModuleName($moduleName);
        $composerDependencies = [];
        $composerDevDependencies = [];
        foreach ($components as $component) {
            if (false === $component->hasPackageName()) {
                continue;
            }

            $version = $this->composerProvider->getSuggestedVersion($component->getPackageVersion());
            if (str_starts_with($component->getPackageName(), 'ext-')) {
                $version = '*';
            }

            if (isset(self::FIXED_VERSION_CONSTRAINTS[$component->getPackageName()])) {
                $version = self::FIXED_VERSION_CONSTRAINTS[$component->getPackageName()];
            }

            if ($component->isDevRequirement()) {
                $composerDevDependencies[$component->getPackageName()] = $version;
                continue;
            }

            $composerDependencies[$component->getPackageName()] = $version;
        }

        return [$composerDependencies, $composerDevDependencies];
    }

    private function getComposerData(string $moduleName, string $currentContents): array
    {
        if ($currentContents === '') {
            $msg = 'File "' . $this->getCurrentPath($moduleName) . '" does not exist or is empty';
            throw new ComposerFileNotFoundException($msg);
        }

        $composerData = json_decode($currentContents, true);
        if (false === is_array($composerData)) {
            $msg = 'File "' . $this->getCurrentPath($moduleName) . '" does not contain valid JSON';
            throw new ComposerException($msg);
        }

        return $composerData;
    }

    private function setRequirements(array $composerData, array $require, array $requireDev): array
    {
        // An empty object instead of an empty array, so that the JSON stays valid for composer
        $composerData['require'] = empty($require) ? new stdClass() : $require;

        if (empty($requireDev)) {
            unset($composerData['require-dev']);
            return $composerData;
        }

        if (isset($composerData['require-dev'])) {
            $composerData['require-dev'] = $requireDev;
            return $composerData;
        }

        // Insert "require-dev" directly after "require"
        $newComposerData = [];
        foreach ($composerData as $key => $value) {
            $newComposerData[$key] = $value;
            if ($key === 'require') {
                $newComposerData['require-dev'] = $requireDev;
            }
        }

        return $newComposerData;
    }
}
