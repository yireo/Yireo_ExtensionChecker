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
use Magento\Framework\Serialize\SerializerInterface;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;

class ListDependenciesCommand extends Command
{
    private SerializerInterface $serializer;
    private ComponentDetectorList $componentDetectorList;

    /**
     * DeleteRuleCommand constructor.
     *
     * @param SerializerInterface $serializer
     * @param null $name
     */
    public function __construct(
        SerializerInterface $serializer,
        ComponentDetectorList $componentDetectorList,
        $name = null
    ) {
        parent::__construct($name);
        $this->serializer = $serializer;
        $this->componentDetectorList = $componentDetectorList;
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:list-dependencies');
        $this->setDescription('List dependencies');

        $this->addOption(
            'path',
            null,
            InputOption::VALUE_OPTIONAL,
            'Module path'
        );

        $this->addOption(
            'module',
            null,
            InputOption::VALUE_OPTIONAL,
            'Module name'
        );

        $this->addOption(
            'format',
            null,
            InputOption::VALUE_OPTIONAL,
            'Format (`json` or the default)'
        );
    }

    /**
     * @param Input $input
     * @param Output $output
     *
     * @return int
     * @throws ReflectionException
     */
    protected function execute(Input $input, Output $output): int
    {
        $moduleName = (string)$input->getOption('module');
        $modulePath = (string)$input->getOption('path');

        if (empty($moduleName) && empty($modulePath)) {
            throw new InvalidArgumentException('Either module name or module path is required');
        }

        $components = $this->componentDetectorList->getComponentsByModuleName($moduleName);

        $format = (string)$input->getOption('format');
        if ($format === 'json') {
            $lines = [];
            foreach ($components as $component) {
                $lines[] = $component->toArray();
            }
            $output->writeln($this->serializer->serialize($lines));
            return 0;
        }

        $table = new Table($output);
        $table->setHeaders(['Component name', 'Component type', 'Package name', 'Current version']);

        foreach ($components as $component) {
            $table->addRow([
                $component->getComponentName(),
                $component->getComponentType(),
                $component->getPackageName(),
                $component->getPackageVersion(),
            ]);
        }

        $table->render();

        return 0;
    }
}
