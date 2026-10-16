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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Composer\ComposerProvider;
use Yireo\ExtensionChecker\Composer\ComposerRequirementsMerger;

class SuggestComposerJsonCommand extends Command
{
    private const FIXED_VERSION_CONSTRAINTS = [
        'psr/log' => '^1.0 || ^2.0 || ^3.0',
    ];

    public function __construct(
        private ComponentDetectorList $componentDetectorList,
        private ComposerProvider $composerProvider,
        private ComponentRegistrar $componentRegistrar,
        private ComposerRequirementsMerger $composerRequirementsMerger,
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

        $this->addOption(
            'with-php',
            null,
            InputOption::VALUE_NONE,
            'Add a PHP requirement (>=8.2 <8.6)'
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
        $composerDevDependencies = [];
        foreach ($components as $component) {
            if (false === $component->hasPackageName()) {
                continue;
            }

            $version = $this->composerProvider->getSuggestedVersion($component->getPackageVersion());
            if (str_starts_with($component->getPackageName(), 'ext-')) {
                $version = '*';
            }

            if (isset(self::FIXED_VERSION_CONSTRAINTS[$component->getPackageName()])) {
                $version = self::FIXED_VERSION_CONSTRAINTS[$component->getPackageName()];
            }

            if ($component->isDevRequirement()) {
                $composerDevDependencies[$component->getPackageName()] = $version;
                continue;
            }

            $composerDependencies[$component->getPackageName()] = $version;
        }

        if ($input->getOption('with-php')) {
            $composerDependencies['php'] = '>=8.2 <8.6';
        }

        $composerData = $this->getCurrentComposerData($moduleName);
        $require = $this->composerRequirementsMerger->mergeRequire(
            $composerData['require'] ?? [],
            $composerDependencies
        );
        $requireDev = $this->composerRequirementsMerger->mergeRequireDev(
            $composerData['require-dev'] ?? [],
            $composerDevDependencies,
            $require
        );

        $composerData = $this->setRequirements($composerData, $require, $requireDev);

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

    private function setRequirements(array $composerData, array $require, array $requireDev): array
    {
        $composerData['require'] = $require;

        if (empty($requireDev)) {
            unset($composerData['require-dev']);
            return $composerData;
        }

        if (isset($composerData['require-dev'])) {
            $composerData['require-dev'] = $requireDev;
            return $composerData;
        }

        // Insert "require-dev" directly after "require"
        $newComposerData = [];
        foreach ($composerData as $key => $value) {
            $newComposerData[$key] = $value;
            if ($key === 'require') {
                $newComposerData['require-dev'] = $requireDev;
            }
        }

        return $newComposerData;
    }
}
