<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Behaviour;

use Composer\InstalledVersions;
use RuntimeException;

final class InstalledCorePackage
{
    public static function getName(string $package): string
    {
        foreach (['magento/' . $package, 'mage-os/' . $package] as $name) {
            if (InstalledVersions::isInstalled($name) && InstalledVersions::getInstallPath($name) !== null) {
                return $name;
            }
        }

        throw new RuntimeException('Core package is not installed: ' . $package);
    }

    public static function getComposerFilePath(string $package): string
    {
        return InstalledVersions::getInstallPath(self::getName($package)) . '/composer.json';
    }
}
