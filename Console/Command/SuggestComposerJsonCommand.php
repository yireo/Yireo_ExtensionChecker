<?php declare(strict_types=1);

/**
 * Yireo ExtensionChecker for Magento
 *
 * @package     Yireo_ExtensionChecker
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2018 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\ExtensionChecker\Console\Command;

use InvalidArgumentException;
use Magento\Framework\Component\ComponentRegistrar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Composer\ComposerProvider;

class SuggestComposerJsonCommand extends Command
{
    public function __construct(
        private ComponentDetectorList $componentDetectorList,
        private ComposerProvider $composerProvider,
        private ComponentRegistrar $componentRegistrar,
        $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:suggest:composer-json');
        $this->setDescription('Suggest composer.json requirements');

        $this->addArgument(
            'module',
            InputArgument::REQUIRED,
            'Module name'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = (string)$input->getArgument('module');

        if (empty($moduleName)) {
            throw new InvalidArgumentException('Module name is required');
        }

        $components = $this->componentDetectorList->getComponentsByModuleName($moduleName);
        $composerDependencies = [];
        foreach ($components as $component) {
            if (false === $component->hasPackageName()) {
                continue;
            }

            $version = $this->composerProvider->getSuggestedVersion($component->getPackageVersion());
            $composerDependencies[$component->getPackageName()] = $version;
        }

        ksort($composerDependencies);
        $composerData = $this->getCurrentComposerData($moduleName);
        $composerData = array_merge($composerData, [
            'require' =>  $composerDependencies,
        ]);

        $composerJson = json_encode($composerData, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        $output->writeln($composerJson);

        return Command::SUCCESS;
    }

    private function getCurrentComposerData(string $moduleName): array
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $composerPath = $modulePath . '/composer.json';
        return json_decode(file_get_contents($composerPath), true);
    }
}
