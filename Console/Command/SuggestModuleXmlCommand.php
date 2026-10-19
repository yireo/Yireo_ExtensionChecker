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
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;

class SuggestModuleXmlCommand extends Command
{
    public function __construct(
        private ModuleXmlSuggestion $moduleXmlSuggestion,
        $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:suggest:module-xml');
        $this->setDescription('Suggest etc/module.xml file');

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

        $moduleXml = $this->moduleXmlSuggestion->getSuggestedContents($moduleName);
        $output->writeln(rtrim($moduleXml, "\n"), OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }
}
