<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

class ComposerProvider
{
    private ComposerFileProvider $composerFileProvider;

    /**
     * @param ComposerFileProvider $composerFileProvider
     */
    public function __construct(
        ComposerFileProvider $composerFileProvider
    ) {
        $this->composerFileProvider = $composerFileProvider;
    }

    /**
     * @param string $composerName
     * @return string
     */
    public function getVersionByComposerName(string $composerName): string
    {
        $composerPackages = $this->getComposerPackages();
        foreach ($composerPackages as $composerPackage) {
            if ($composerPackage['name'] === $composerName) {
                return $composerPackage['version'];
            }
        }

        return '';
    }

    /**
     * @param string $version
     * @return string
     */
    public function getSuggestedVersion(string $version): string
    {
        if ($version === '*') {
            return '';
        }

        $versionParts = explode('.', $version);
        if ((int)$versionParts[0] === 0) {
            return '~' . $version;
        }

        if (count($versionParts) === 1) {
            return $versionParts[0];
        }

        return '^' . $versionParts[0] . '.' . $versionParts[1];
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function shouldSuggestVersion(string $packageName): bool
    {
        if ($packageName === 'php' || preg_match('/^ext-/', $packageName)) {
            return false;
        }

        return true;
    }

    /**
     * @return array[]
     */
    public function getComposerPackages(): array
    {
        return $this->composerFileProvider->getAllComposerNames();
    }
}
