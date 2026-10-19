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
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;

class DiffModuleXmlCommand extends AbstractDiffCommand
{
    public function __construct(
        ModuleXmlSuggestion $moduleXmlSuggestion,
        DiffGenerator $diffGenerator,
        $name = null
    ) {
        parent::__construct($moduleXmlSuggestion, $diffGenerator, $name);
    }

    protected function configure()
    {
        $this->setName('yireo_extensionchecker:diff:module-xml');
        $this->setDescription('Show the differences between the current etc/module.xml file and the suggested one');
        $this->addModuleArgument();
    }
}
