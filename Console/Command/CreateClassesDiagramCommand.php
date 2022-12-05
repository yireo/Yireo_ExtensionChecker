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
use Yireo\ExtensionChecker\Exception\NoClassNameException;
use Yireo\ExtensionChecker\PhpClass\ClassInspector;
use Yireo\ExtensionChecker\PhpClass\ClassNameCollector;
use Yireo\ExtensionChecker\PhpClass\ModuleCollector;

class CreateClassesDiagramCommand extends Command
{
    private SerializerInterface $serializer;
    private ModuleCollector $moduleCollector;
    private ClassNameCollector $classNameCollector;
    private ClassInspector $classInspector;

    /**
     * DeleteRuleCommand constructor.
     *
     * @param SerializerInterface $serializer
     * @param ModuleCollector $moduleCollector
     * @param ClassNameCollector $classNameCollector
     * @param null $name
     */
    public function __construct(
        SerializerInterface $serializer,
        ModuleCollector $moduleCollector,
        ClassNameCollector $classNameCollector,
        ClassInspector $classInspector,
        $name = null
    ) {
        parent::__construct($name);
        $this->serializer = $serializer;
        $this->moduleCollector = $moduleCollector;
        $this->classNameCollector = $classNameCollector;
        $this->classInspector = $classInspector;
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:create-classes-diagram');
        $this->setDescription('Create classes diagram');

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

        $output->writeln('@startuml');
        foreach ($classNames as $className) {
            $printClassName = $this->printClassName($className);
            try {
                $classInspector = $this->classInspector->setClassName($className);
            } catch (NoClassNameException|ReflectionException $exception) {
                continue;
            }

            try {
                foreach ($classInspector->getDependenciesFromConstructor() as $classNameDependency) {
                    $output->writeln(
                        $printClassName
                        . ' *-- '
                        . $this->printClassName($classNameDependency)
                        . ' : constructor DI'
                    );
                }
            } catch (ReflectionException $e) {
            }

            foreach (get_class_methods($className) as $method) {
                if ($method === '__construct') {
                    continue;
                }

                $output->writeln($printClassName . ' : ' . $method . '()');
            }
        }

        foreach ($classNames as $className) {
            $classPrefix = $this->getClassPrefix($className);
            if (!$classPrefix) {
                continue;
            }

            $output->writeln($classPrefix . $this->printClassName($className));
        }

        $output->writeln('@enduml');

        return 0;
    }

    /**
     * @param string $className
     * @return string
     */
    private function getClassPrefix(string $className): string
    {
        try {
            if (interface_exists($className)) {
                return 'interface ';
            }
        } catch (\Throwable $throwable) {
        }

        return '';
    }

    /**
     * @param string $className
     * @return string
     */
    private function printClassName(string $className): string
    {
        return str_replace('\\', '.', $className);
    }
}
