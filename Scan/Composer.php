<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

/**
 * Class Composer
 *
 * @package Yireo\ExtensionChecker\Scan
 */
class Composer
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * Composer constructor.
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->directoryList = $directoryList;
    }

    /**
     * @param string $package
     * @return string
     */
    public function getVersionByPackage(string $package): string
    {
        $installedPackages = $this->getInstalledPackages();

        foreach ($installedPackages as $installedPackage) {
            if ($installedPackage['name'] === $package) {
                return $installedPackage['version'];
            }
        }

        return '';
    }

    /**
     * @return array
     */
    private function getInstalledPackages(): array
    {
        static $installedPackages = [];

        if (!empty($installedPackages)) {
            return $installedPackages;
        }

        chdir($this->directoryList->getRoot());
        exec('composer show --format=json', $output);
        $packages = json_decode(implode('', $output), true);
        $installedPackages = $packages['installed'];
        return $installedPackages;
    }
}
