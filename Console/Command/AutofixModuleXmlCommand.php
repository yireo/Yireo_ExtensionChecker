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
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;

class AutofixModuleXmlCommand extends AbstractAutofixCommand
{
    public function __construct(
        ModuleXmlSuggestion $moduleXmlSuggestion,
        DiffGenerator $diffGenerator,
        FileDriver $fileDriver,
        $name = null
    ) {
        parent::__construct($moduleXmlSuggestion, $diffGenerator, $fileDriver, $name);
    }

    protected function configure()
    {
        $this->setName('yireo_extensionchecker:autofix:module-xml');
        $this->setDescription('Replace the current etc/module.xml file with the suggested one');
        $this->addModuleArgument();
    }
}
