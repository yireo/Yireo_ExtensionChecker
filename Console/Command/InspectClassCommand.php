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

use Composer\Console\Input\InputArgument;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Yireo\ExtensionChecker\PhpClass\ClassInspector;

class InspectClassCommand extends Command
{
    private SerializerInterface $serializer;
    private ClassInspector $classInspector;

    /**
     * DeleteRuleCommand constructor.
     *
     * @param SerializerInterface $serializer
     * @param ModuleCollector $moduleCollector
     * @param null $name
     */
    public function __construct(
        SerializerInterface $serializer,
        ClassInspector $classInspector,
        $name = null
    ) {
        parent::__construct($name);
        $this->serializer = $serializer;
        $this->classInspector = $classInspector;
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:inspect-class');
        $this->setDescription('Inspect PHP class');

        $this->addArgument(
            'className',
            InputArgument::REQUIRED,
            'PHP class name'
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
        $className = (string)$input->getArgument('className');
        $dependencies = $this->classInspector->setClassName($className)->getDependencies();

        $table = new Table($output);
        $table->setHeaders(['Class name', 'Class type']);

        foreach ($dependencies as $className) {
            if (strstr($className, '\\Test\\')) {
                continue;
            }

            $table->addRow([
                $className,
                $this->getClassType($className)
            ]);
        }

        $table->render();

        return Command::SUCCESS;
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
