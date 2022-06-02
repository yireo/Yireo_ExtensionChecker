<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Shell;
use RuntimeException;
use Yireo\ExtensionChecker\Exception\ComposerException;

class Composer
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var ReadFactory
     */
    private $readFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * Composer constructor.
     * @param DirectoryList $directoryList
     * @param ReadFactory $readFactory
     * @param SerializerInterface $serializer
     * @param Shell $shell
     */
    public function __construct(
        DirectoryList $directoryList,
        ReadFactory $readFactory,
        SerializerInterface $serializer,
        Shell $shell
    ) {
        $this->directoryList = $directoryList;
        $this->readFactory = $readFactory;
        $this->serializer = $serializer;
        $this->shell = $shell;
    }

    /**
     * @param string $composerFile
     * @return array
     * @throws NotFoundException
     */
    public function getDataFromFile(string $composerFile): array
    {
        if (empty($composerFile)) {
            throw new NotFoundException(__('Composer file "' . $composerFile . '" does not exists'));
        }

        $read = $this->readFactory->create($composerFile, 'file');
        $composerContents = $read->readAll();
        $extensionData = $this->serializer->unserialize($composerContents);
        if (empty($extensionData)) {
            throw new RuntimeException('Empty contents after decoding file "' . $composerFile . '"');
        }

        return $extensionData;
    }

    /**
     * @param string $composerFile
     * @return array
     * @throws NotFoundException
     * @throws RuntimeException
     */
    public function getRequirementsFromFile(string $composerFile): array
    {
        $extensionData = $this->getDataFromFile($composerFile);
        if (!isset($extensionData['require'])) {
            throw new RuntimeException('File "' . $composerFile . '" does not have a "require" section');
        }

        $extensionDeps = $extensionData['require'];
        return $extensionDeps;
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
    public function getInstalledPackages(): array
    {
        static $installedPackages = [];

        if (!empty($installedPackages)) {
            return $installedPackages;
        }
        
        chdir($this->directoryList->getRoot());
        $output = $this->shell->execute('composer show --no-scripts --no-plugins --format=json');
        $packages = $this->serializer->unserialize($output);
        $installedPackages = $packages['installed'];
        
        if (empty($installedPackages)) {
            throw new ComposerException('No installed packages found');
        }
        
        return $installedPackages;
    }
}
