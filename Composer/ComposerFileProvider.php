<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Shell;
use Yireo\ExtensionChecker\Exception\ComponentNotFoundException;
use Yireo\ExtensionChecker\Exception\ComposerException;
use Yireo\ExtensionChecker\Exception\ComposerFileNotFoundException;

class ComposerFileProvider
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
     * @param string $moduleName
     * @return ComposerFile
     * @throws FileSystemException
     * @throws ComposerFileNotFoundException
     */
    public function getComposerFileByModuleName(string $moduleName): ComposerFile
    {
        $module = $this->moduleList->getOne($moduleName);
        if (!$module) {
            throw new ComponentNotFoundException('Unknown module "'.$moduleName.'"');
        }

        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $possibleComposerPaths = [
            $modulePath . '/composer.json',
            $modulePath . '/../composer.json',
            $modulePath . '/../../composer.json',
        ];

        foreach ($possibleComposerPaths as $possibleComposerPath) {
            if ($this->fileDriver->isExists($possibleComposerPath)) {
                return $this->composerFileFactory->create($possibleComposerPath);
            }
        }
        
        throw new ComposerFileNotFoundException('Could not locate composer.json for module "'.$moduleName.'"');
    }
    
    /**
     * @param string $composerName
     * @return string
     */
    public function getVersionByComposerName(string $composerName): string
    {
        $installedPackages = $this->getInstalledPackages();
        
        foreach ($installedPackages as $installedPackage) {
            if ($installedPackage['name'] === $composerName) {
                return $installedPackage['version'];
            }
        }
        
        return '';
    }
    
    /**
     * @return string[]
     */
    public function getAllComposerNames(): array
    {
        static $installedPackages = [];
        
        if (!empty($installedPackages)) {
            return $installedPackages;
        }
        
        chdir($this->directoryList->getRoot());
        $output = $this->shell->execute('composer show --no-scripts --no-plugins --format=json');
        $output = str_replace("\n", ' ', $output);
        $output = preg_replace('/^([^{]+)/m', '', $output);
        
        $packages = $this->serializer->unserialize($output);
        if (!isset($packages['installed'])) {
            throw new ComposerException('No installed packages found');
        }
        
        $installedPackages = $packages['installed'];
        
        if (empty($installedPackages)) {
            throw new ComposerException('No installed packages found');
        }
        
        return $installedPackages;
    }
}
