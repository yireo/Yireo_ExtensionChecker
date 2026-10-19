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
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;

class SuggestComposerJsonCommand extends Command
{
    use ComposerJsonOptionsTrait;

    public function __construct(
        private ComposerJsonSuggestion $composerJsonSuggestion,
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

        $this->addComposerJsonOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = (string)$input->getArgument('module');

        if (empty($moduleName)) {
            throw new InvalidArgumentException('Module name is required');
        }

        $composerJson = $this->composerJsonSuggestion->getSuggestedContents(
            $moduleName,
            $this->getSuggestionOptions($input)
        );
        $output->writeln(rtrim($composerJson, "\n"), OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }
}
