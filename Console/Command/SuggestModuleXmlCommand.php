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
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;

class SuggestModuleXmlCommand extends Command
{
    public function __construct(
        private ComponentDetectorList $componentDetectorList,
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

        $components = $this->componentDetectorList->getComponentsByModuleName($moduleName);
        $moduleDependencies = [];
        foreach ($components as $component) {
            if (false === $component->hasComponentName()) {
                continue;
            }

            $moduleDependencies[] = $component->getComponentName();
        }

        sort($moduleDependencies);
        $output->writeln($this->getModuleXml($moduleName, $moduleDependencies));

        return Command::SUCCESS;
    }

    private function getModuleXml(string $moduleName, array $moduleDependencies = []): string
    {
        $moduleSequence = '';
        foreach ($moduleDependencies as $moduleDependency) {
            $moduleSequence .=  '            <module name="'.$moduleDependency.'"/>'."\n";
        }

        return <<<EOF
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="{$moduleName}">
        <sequence>
{$moduleSequence}
        </sequence>
    </module>
</config>
EOF;
    }
}
