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
            if (str_starts_with($component->getPackageName(), 'ext-')) {
                $version = '*';
            }

            $composerDependencies[$component->getPackageName()] = $version;
        }

        ksort($composerDependencies);
        $composerData = [
            'require' =>  $composerDependencies,
        ];

        $composerJson = json_encode($composerData, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        $output->writeln($composerJson);

        return Command::SUCCESS;
    }
}
