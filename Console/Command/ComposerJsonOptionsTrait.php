<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;

trait ComposerJsonOptionsTrait
{
    protected function addComposerJsonOptions(): void
    {
        $this->addOption(
            ComposerJsonSuggestion::OPTION_WITH_PHP,
            null,
            InputOption::VALUE_NONE,
            'Add a PHP requirement (' . ComposerJsonSuggestion::PHP_VERSION_CONSTRAINT . ')'
        );
    }

    protected function getSuggestionOptions(InputInterface $input): array
    {
        return [
            ComposerJsonSuggestion::OPTION_WITH_PHP => (bool)$input->getOption(ComposerJsonSuggestion::OPTION_WITH_PHP),
        ];
    }
}
