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

use Composer\Semver\Semver;
use InvalidArgumentException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Serialize\SerializerInterface;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Message\Message;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Scan\Scan;

class CheckMagentoVersionCommand extends Command
{
    private ComposerFileProvider $composerFileProvider;
    private DirectoryList $directoryList;

    public function __construct(
        ComposerFileProvider $composerFileProvider,
        DirectoryList $directoryList,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->composerFileProvider = $composerFileProvider;
        $this->directoryList = $directoryList;
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:check-magento-version');
        $this->setDescription('Match a specific Magento module with a specific Magento version');

        $this->addOption(
            'module',
            null,
            InputOption::VALUE_REQUIRED,
            'Module name'
        );

        $this->addOption(
            'magento-version',
            null,
            InputOption::VALUE_REQUIRED,
            'Magento version'
        );

        $this->addOption(
            'format',
            null,
            InputOption::VALUE_OPTIONAL,
            'Format (`json` or the default)'
        );
    }

    /**
     * @param Input $input
     * @param Output $output
     *
     * @return int
     * @throws ReflectionException
     */
    protected function execute(Input $input, Output $output): int
    {
        $verbose = (bool)$input->getOption('verbose');
        $moduleName = (string)$input->getOption('module');
        $magentoVersion = (string)$input->getOption('magento-version');
        if (!preg_match('/2\.[0-9]+\.[0-9]+/', $magentoVersion)) {
            throw new \RuntimeException('Not a valid Magento version');
        }

        $composerFile = $this->composerFileProvider->getComposerFileByModuleName($moduleName);
        $moduleRequirements = $composerFile->getRequirements();

        $magentoVersionDir = 'magento-product-community-edition-' . $magentoVersion . '.json';
        $magentoVersionFile = $this->directoryList->getRoot() . '/var/composer_home/' . $magentoVersionDir;
        if (file_exists($magentoVersionFile)) {
            $magentoVersionComposerData = json_decode(file_get_contents($magentoVersionFile), true);
        } else {
            // phpcs:ignore
            exec('composer show magento/product-community-edition ' . $magentoVersion . ' --format json -a', $output);
            $magentoVersionComposerString = implode("\n", $output);
            file_put_contents($magentoVersionFile, $magentoVersionComposerString);
            $magentoVersionComposerData = json_decode($magentoVersionComposerString, true);
        }

        $hasWarnings = false;
        $rows = [];
        foreach ($magentoVersionComposerData['requires'] as $magentoRequirement => $magentoRequiredVersion) {
            if (!isset($moduleRequirements[$magentoRequirement])) {
                continue;
            }

            // @todo: We are unable to crossmatch constraints with constraints as of yet
            if (preg_match('/([^0-9.])+/', $magentoRequiredVersion)) {
                continue;
            }

            $moduleVersion = $moduleRequirements[$magentoRequirement];
            if (!Semver::satisfies($magentoRequiredVersion, $moduleVersion)) {
                $message = 'Not satisfied';
            } else {
                $message = 'Satisfied';
            }

            $rows[] = [
                $magentoRequirement,
                $magentoRequiredVersion,
                $moduleVersion,
                $message
            ];
        }

        if (false === $hasWarnings && false === $verbose) {
            return 0;
        }

        $table = new Table($output);
        $table->setHeaders([
            'Composer requirement',
            'Magento version ' . $magentoVersion,
            'Module constraint',
            'Message'
        ]);

        $table->addRows($rows);
        $table->render();

        return 1;
    }
}
