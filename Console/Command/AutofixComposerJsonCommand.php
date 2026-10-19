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
use Yireo\ExtensionChecker\Diff\DiffGenerator;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;

class AutofixComposerJsonCommand extends AbstractAutofixCommand
{
    use ComposerJsonOptionsTrait;

    public function __construct(
        ComposerJsonSuggestion $composerJsonSuggestion,
        DiffGenerator $diffGenerator,
        FileDriver $fileDriver,
        $name = null
    ) {
        parent::__construct($composerJsonSuggestion, $diffGenerator, $fileDriver, $name);
    }

    protected function configure()
    {
        $this->setName('yireo_extensionchecker:autofix:composer-json');
        $this->setDescription('Replace the requirements in the current composer.json file with the suggested ones');
        $this->addModuleArgument();
        $this->addComposerJsonOptions();
    }
}
