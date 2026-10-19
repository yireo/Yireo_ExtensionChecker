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

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Suggestion\SuggestionInterface;

/**
 * Replace the current file with the suggested file
 */
abstract class AbstractAutofixCommand extends AbstractSuggestionCommand
{
    public function __construct(
        SuggestionInterface $suggestion,
        DiffGenerator $diffGenerator,
        protected FileDriver $fileDriver,
        $name = null
    ) {
        parent::__construct($suggestion, $diffGenerator, $name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $this->getModuleName($input);
        $path = $this->suggestion->getCurrentPath($moduleName);
        $suggestedContents = $this->getSuggestedContents($moduleName, $input);
        $diff = $this->getDiff($moduleName, $suggestedContents);

        if ($diff === '') {
            $output->writeln('<info>No changes needed for "' . $path . '"</info>');
            return Command::SUCCESS;
        }

        $folder = dirname($path);
        if (false === $this->fileDriver->isDirectory($folder)) {
            $this->fileDriver->createDirectory($folder);
        }

        $this->fileDriver->filePutContents($path, $suggestedContents);

        $output->write($diff, false, OutputInterface::OUTPUT_RAW);
        $output->writeln('<info>Updated "' . $path . '"</info>');

        return Command::SUCCESS;
    }
}
