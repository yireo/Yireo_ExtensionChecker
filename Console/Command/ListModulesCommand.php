<?php
declare(strict_types=1);

namespace Yireo\ExtensionChecker\Console\Command;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Yireo\ExtensionChecker\Composer\ComposerFileFactory;
use Yireo\ExtensionChecker\Composer\ComposerProvider;

class ListModulesCommand extends Command
{
    private ComponentRegistrar $componentRegistrar;
    private ModuleList $moduleList;
    private ComposerFileFactory $composerFileFactory;
    private ComposerProvider $composerProvider;

    public function __construct(
        ComponentRegistrar $componentRegistrar,
        ModuleList $moduleList,
        ComposerFileFactory $composerFileFactory,
        ComposerProvider $composerProvider,
        ?string $name = null)
    {
        parent::__construct($name);
        $this->componentRegistrar = $componentRegistrar;
        $this->moduleList = $moduleList;
        $this->composerFileFactory = $composerFileFactory;
        $this->composerProvider = $composerProvider;
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:list:modules');
        $this->setDescription('List all Magento modules');
        $this->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Format (json, default)');
    }

    /**
     * @param Input $input
     * @param Output $output
     *
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        $moduleRows = $this->getModuleRows();
        $format = $input->getOption('format');

        if ($format === 'json') {
            $output->writeln(json_encode($moduleRows));
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders([
            'Module',
            'Status',
            'Setup Version',
            'Composer Version'
        ]);

        foreach ($moduleRows as $moduleRow) {
            $table->addRow($moduleRow);
        }

        $table->render();

        return Command::SUCCESS;
    }

    private function getModuleRows(): array
    {
        $componentPaths = $this->componentRegistrar->getPaths(ComponentRegistrar::MODULE);
        $moduleNames = array_keys($componentPaths);
        $moduleRows = [];

        foreach ($moduleNames as $moduleName) {
            $moduleInfo = $this->moduleList->getOne($moduleName);
            $status = $moduleInfo ? 'enabled' : 'disabled';
            $setupVersion = isset($moduleInfo['setup_version']) ? $moduleInfo['setup_version'] : '-';

            $moduleRows[] = [
                $moduleName,
                $status,
                $setupVersion,
                $this->getComposerVersion($moduleName)
            ];
        }

        return $moduleRows;
    }

    private function getComposerVersion(string $moduleName): string
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $composerJsonFile = $path. '/composer.json';
        if (false === file_exists($composerJsonFile)) {
            $composerJsonFile = dirname($path).'/composer.json';
        }

        if (false === file_exists($composerJsonFile)) {
            return '';
        }

        $composerFile = $this->composerFileFactory->create($composerJsonFile);

        try {
            $composerVersion = $composerFile->get('version');
            if (!empty($composerVersion)) {
                return $composerVersion;
            }
        } catch(RuntimeException $e) {
        }

        $composerName = $composerFile->get('name');
        return $this->composerProvider->getVersionByComposerName($composerName);
    }
}
