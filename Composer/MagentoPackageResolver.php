<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

use InvalidArgumentException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Serialize\SerializerInterface;
use Yireo\ExtensionChecker\Component\Component;

class MagentoPackageResolver
{
    private const MAGE_OS_PACKAGE_PREFIX = 'mage-os/';
    private const MAGENTO_PACKAGE_PREFIX = 'magento/';
    private const MAGE_OS_MODULE_PREFIX = 'MageOs_';
    private const MAGENTO_MODULE_PREFIX = 'Magento_';

    private array $replacements = [];

    public function __construct(
        private DirectoryList $directoryList,
        private FileDriver $fileDriver,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * Return the Magento core package (name and version) that a Mage-OS package replaces
     *
     * @return array{name: string, version: string}|null
     */
    public function getMagentoReplacement(string $packageName): ?array
    {
        if (false === str_starts_with($packageName, self::MAGE_OS_PACKAGE_PREFIX)) {
            return null;
        }

        if (false === array_key_exists($packageName, $this->replacements)) {
            $this->replacements[$packageName] = $this->findMagentoReplacement($packageName);
        }

        return $this->replacements[$packageName];
    }

    public function getMagentoModuleName(Component $component): ?string
    {
        $moduleName = $component->getComponentName();
        if (false === str_starts_with($moduleName, self::MAGE_OS_MODULE_PREFIX)) {
            return null;
        }

        if (null === $this->getMagentoReplacement($component->getPackageName())) {
            return null;
        }

        return self::MAGENTO_MODULE_PREFIX . substr($moduleName, strlen(self::MAGE_OS_MODULE_PREFIX));
    }

    /**
     * @return array{name: string, version: string}|null
     */
    private function findMagentoReplacement(string $packageName): ?array
    {
        $replace = $this->getReplaceSection($packageName);
        $magentoPackageName = self::MAGENTO_PACKAGE_PREFIX
            . substr($packageName, strlen(self::MAGE_OS_PACKAGE_PREFIX));

        if (isset($replace[$magentoPackageName]) && is_string($replace[$magentoPackageName])) {
            return ['name' => $magentoPackageName, 'version' => $replace[$magentoPackageName]];
        }

        foreach ($replace as $replacedPackageName => $version) {
            if (is_string($version) && str_starts_with((string)$replacedPackageName, self::MAGENTO_PACKAGE_PREFIX)) {
                return ['name' => (string)$replacedPackageName, 'version' => $version];
            }
        }

        return null;
    }

    private function getReplaceSection(string $packageName): array
    {
        $composerFile = $this->directoryList->getRoot() . '/vendor/' . $packageName . '/composer.json';

        try {
            if (false === $this->fileDriver->isExists($composerFile)) {
                return [];
            }

            $composerData = $this->serializer->unserialize($this->fileDriver->fileGetContents($composerFile));
        } catch (FileSystemException|InvalidArgumentException $exception) {
            return [];
        }

        if (false === is_array($composerData) || false === isset($composerData['replace'])) {
            return [];
        }

        return is_array($composerData['replace']) ? $composerData['replace'] : [];
    }
}
