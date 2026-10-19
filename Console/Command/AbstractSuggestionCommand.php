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
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Suggestion\SuggestionInterface;

abstract class AbstractSuggestionCommand extends Command
{
    public function __construct(
        protected SuggestionInterface $suggestion,
        protected DiffGenerator $diffGenerator,
        $name = null
    ) {
        parent::__construct($name);
    }

    protected function addModuleArgument(): void
    {
        $this->addArgument(
            'module',
            InputArgument::REQUIRED,
            'Module name'
        );
    }

    protected function getModuleName(InputInterface $input): string
    {
        $moduleName = (string)$input->getArgument('module');
        if (empty($moduleName)) {
            throw new InvalidArgumentException('Module name is required');
        }

        return $moduleName;
    }

    protected function getSuggestionOptions(InputInterface $input): array
    {
        return [];
    }

    protected function getSuggestedContents(string $moduleName, InputInterface $input): string
    {
        return $this->suggestion->getSuggestedContents($moduleName, $this->getSuggestionOptions($input));
    }

    protected function getDiff(string $moduleName, string $suggestedContents): string
    {
        $path = $this->suggestion->getCurrentPath($moduleName);

        return $this->diffGenerator->diff(
            $this->suggestion->getCurrentContents($moduleName),
            $suggestedContents,
            $path,
            $path . ' (suggested)'
        );
    }
}
