<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Shell;
use Yireo\ExtensionChecker\Exception\ComposerException;

class ComposerProvider
{
    private ModuleListInterface $moduleList;
    private ComponentRegistrar $componentRegistrar;
    private FileDriver $fileDriver;
    private ComposerFileFactory $composerFileFactory;
    private DirectoryList $directoryList;
    private SerializerInterface $serializer;
    private Shell $shell;

    /**
     * @param ModuleListInterface $moduleList
     * @param ComponentRegistrar $componentRegistrar
     * @param FileDriver $fileDriver
     * @param ComposerFileFactory $composerFileFactory
     * @param DirectoryList $directoryList
     * @param SerializerInterface $serializer
     * @param Shell $shell
     */
    public function __construct(
        ModuleListInterface $moduleList,
        ComponentRegistrar $componentRegistrar,
        FileDriver $fileDriver,
        ComposerFileFactory $composerFileFactory,
        DirectoryList $directoryList,
        SerializerInterface $serializer,
        Shell $shell
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->fileDriver = $fileDriver;
        $this->composerFileFactory = $composerFileFactory;
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->shell = $shell;
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
        $versionParts = explode('.', $version);
        if ((int)$versionParts[0] === 0) {
            return '~' . $version;
        }

        return '^' . $versionParts[0] . '.' . $versionParts[1];
    }

    /**
     * @return array[]
     */
    public function getComposerPackages(): array
    {
        static $composerPackages = [];

        if (!empty($composerPackages)) {
            return $composerPackages;
        }

        chdir($this->directoryList->getRoot());
        $output = $this->shell->execute('composer show --no-scripts --no-plugins --format=json');
        $output = str_replace("\n", ' ', $output);
        $output = preg_replace('/^([^{]+)/m', '', $output);

        $packages = $this->serializer->unserialize($output);
        if (!isset($packages['installed'])) {
            throw new ComposerException('No installed packages found');
        }

        $composerPackages = $packages['installed'];

        if (empty($composerPackages)) {
            throw new ComposerException('No installed packages found');
        }

        return $composerPackages;
    }
}
