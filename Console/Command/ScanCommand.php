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

use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Input\InputOption;
use Yireo\ExtensionChecker\Scan\Scan;

class ScanCommand extends Command
{
    private Scan $scan;
    private SerializerInterface $serializer;
    
    /**
     * DeleteRuleCommand constructor.
     *
     * @param Scan $scan
     * @param SerializerInterface $serializer
     * @param null $name
     */
    public function __construct(
        Scan $scan,
        SerializerInterface $serializer,
        $name = null
    ) {
        parent::__construct($name);
        $this->scan = $scan;
        $this->serializer = $serializer;
    }
    
    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:scan');
        $this->setDescription('Scan a specific Magento module');
        
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
            'hide-deprecated',
            null,
            InputOption::VALUE_OPTIONAL,
            'Hide deprecated dependency notices'
        );
        
        $this->addOption(
            'hide-needless',
            null,
            InputOption::VALUE_OPTIONAL,
            'Hide needless dependency notices'
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
        
        $hideDeprecated = (bool)$input->getOption('hide-deprecated');
        $hideNeedless = (bool)$input->getOption('hide-needless');
        $verbose = (bool)$input->getOption('verbose');
        $format = (string)$input->getOption('format');
        
        $this->scan->setModuleName($moduleName);
        $this->scan->setHideDeprecated($hideDeprecated);
        $this->scan->setHideNeedless($hideNeedless);
        
        $this->scan->scan();
        $messages = $this->scan->getMessages();
        $outputData = [];
        
        $hasWarnings = false;
        foreach ($messages as $message) {
            if (!$verbose && $message->isDebug()) {
                continue;
            }
    
            $outputData[] = $message->getText();
            if ($message->isWarning()) {
                $hasWarnings = true;
            }
        }
        
        $this->output($output, $outputData, $format);
        
        return (int)$hasWarnings;
    }
    
    private function output(Output $output, array $outputData, string $format = 'default')
    {
        if ($format === 'json') {
            $output->writeln($this->serializer->serialize($outputData));
            return;
        }
        
        foreach ($outputData as $line) {
            $output->writeln($line);
        }
    }
}
