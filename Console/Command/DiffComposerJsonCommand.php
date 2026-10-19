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

use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;

class DiffComposerJsonCommand extends AbstractDiffCommand
{
    use ComposerJsonOptionsTrait;

    public function __construct(
        ComposerJsonSuggestion $composerJsonSuggestion,
        DiffGenerator $diffGenerator,
        $name = null
    ) {
        parent::__construct($composerJsonSuggestion, $diffGenerator, $name);
    }

    protected function configure()
    {
        $this->setName('yireo_extensionchecker:diff:composer-json');
        $this->setDescription('Show the differences between the current composer.json file and the suggested one');
        $this->addModuleArgument();
        $this->addComposerJsonOptions();
    }
}
