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
use Composer\Semver\VersionParser;
use InvalidArgumentException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Shell;
use RuntimeException;
use UnexpectedValueException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Util\CacheFile;

class CheckMagentoVersionCommand extends Command
{
    private ComposerFileProvider $composerFileProvider;
    private DirectoryList $directoryList;
    private Shell $shell;
    private CacheFile $cacheFile;

    public function __construct(
        ComposerFileProvider $composerFileProvider,
        DirectoryList $directoryList,
        Shell $shell,
        CacheFile $cacheFile,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->composerFileProvider = $composerFileProvider;
        $this->directoryList = $directoryList;
        $this->shell = $shell;
        $this->cacheFile = $cacheFile;
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
     * @throws FileSystemException
     * @throws LocalizedException
     */
    protected function execute(Input $input, Output $output): int
    {
        $verbose = (bool)$input->getOption('verbose');
        $moduleName = (string)$input->getOption('module');
        $magentoVersion = (string)$input->getOption('magento-version');
        $this->validateMagentoVersion($magentoVersion);

        $composerFile = $this->composerFileProvider->getComposerFileByModuleName($moduleName);
        $moduleRequirements = $composerFile->getRequirements();
        $magentoVersionComposerData = $this->getMagentoVersionComposerData($magentoVersion);

        $hasWarnings = false;
        $rows = [];
        foreach ($magentoVersionComposerData['requires'] as $magentoRequirement => $magentoRequiredVersion) {
            if (!isset($moduleRequirements[$magentoRequirement])) {
                continue;
            }

            // @todo: We are unable to crossmatch constraints with constraints as of yet
            if (false === $this->isExactVersion((string)$magentoRequiredVersion)) {
                continue;
            }

            $moduleVersion = $moduleRequirements[$magentoRequirement];
            $message = $this->getSatisfactionMessage((string)$magentoRequiredVersion, (string)$moduleVersion);
            if ($message !== 'Satisfied') {
                $hasWarnings = true;
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

    private function isExactVersion(string $version): bool
    {
        if (preg_match('/[\s,|<>=^~*]/', $version)) {
            return false;
        }

        try {
            (new VersionParser())->normalize($version);
        } catch (UnexpectedValueException $exception) {
            return false;
        }

        return true;
    }

    private function getSatisfactionMessage(string $version, string $constraint): string
    {
        try {
            return Semver::satisfies($version, $constraint) ? 'Satisfied' : 'Not satisfied';
        } catch (UnexpectedValueException $exception) {
            return 'Invalid constraint';
        }
    }

    private function validateMagentoVersion(string $magentoVersion): void
    {
        if (!preg_match('/^2\.\d+\.\d+(-p\d+)?$/D', $magentoVersion)) {
            throw new InvalidArgumentException('Not a valid Magento version');
        }
    }

    /**
     * @param string $magentoVersion
     * @return array
     * @throws LocalizedException
     */
    private function getMagentoVersionComposerData(string $magentoVersion): array
    {
        $cacheFile = $this->directoryList->getRoot() . '/var/composer_home/magento-product-community-edition-'
            . $magentoVersion . '.json';
        $magentoVersionComposerData = json_decode((string)$this->cacheFile->read($cacheFile), true);
        if ($this->isValidComposerData($magentoVersionComposerData)) {
            return $magentoVersionComposerData;
        }

        $magentoVersionComposerString = $this->shell->execute(
            'composer show magento/product-community-edition %s --format=json -a --no-plugins --no-scripts',
            [$magentoVersion]
        );

        $magentoVersionComposerData = json_decode($magentoVersionComposerString, true);
        if (false === $this->isValidComposerData($magentoVersionComposerData)) {
            throw new RuntimeException('Unable to retrieve composer data of Magento version "' . $magentoVersion . '"');
        }

        $this->cacheFile->write($cacheFile, $magentoVersionComposerString);

        return $magentoVersionComposerData;
    }

    /**
     * @param mixed $magentoVersionComposerData
     * @return bool
     */
    private function isValidComposerData($magentoVersionComposerData): bool
    {
        return is_array($magentoVersionComposerData)
            && isset($magentoVersionComposerData['requires'])
            && is_array($magentoVersionComposerData['requires']);
    }
}
