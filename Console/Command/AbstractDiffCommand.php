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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show the differences between the current file and the suggested file
 * Like the `diff` tool, this exits with 0 when there are no differences and with 1 when there are
 */
abstract class AbstractDiffCommand extends AbstractSuggestionCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $this->getModuleName($input);
        $diff = $this->getDiff($moduleName, $this->getSuggestedContents($moduleName, $input));
        if ($diff === '') {
            $output->writeln('<info>No differences found</info>');
            return Command::SUCCESS;
        }

        $output->write($diff, false, OutputInterface::OUTPUT_RAW);

        return Command::FAILURE;
    }
}
