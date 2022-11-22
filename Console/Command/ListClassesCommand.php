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
use Yireo\ExtensionChecker\PhpClass\ModuleCollector;

class ListClassesCommand extends Command
{
    private SerializerInterface $serializer;
    private ModuleCollector $moduleCollector;

    /**
     * DeleteRuleCommand constructor.
     *
     * @param SerializerInterface $serializer
     * @param ModuleCollector $moduleCollector
     * @param null $name
     */
    public function __construct(
        SerializerInterface $serializer,
        ModuleCollector  $moduleCollector,
        $name = null
    ) {
        parent::__construct($name);
        $this->serializer = $serializer;
        $this->moduleCollector = $moduleCollector;
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:list-classes');
        $this->setDescription('List classes');

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

        $classNames = $this->moduleCollector->getClassNamesFromModule($moduleName);

        $format = (string)$input->getOption('format');
        if ($format === 'json') {
            $lines = [];
            foreach ($classNames as $className) {
                $lines[] = $className;
            }
            $output->writeln($this->serializer->serialize($lines));
            return 0;
        }

        $table = new Table($output);
        $table->setHeaders(['Class name', 'Class type']);

        foreach ($classNames as $className) {
            if (strstr($className, '\\Test\\')) {
                continue;
            }

            $table->addRow([
                $className,
                $this->getClassType($className)
            ]);
        }

        $table->render();

        return 0;
    }

    /**
     * @param string $className
     * @return string
     */
    private function getClassType(string $className): string
    {
        if (interface_exists($className)) {
            return 'interface';
        }

        return 'class';
    }
}
