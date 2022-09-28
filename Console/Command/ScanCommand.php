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
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Input\InputOption;
use Yireo\ExtensionChecker\Config\RuntimeConfig;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Scan\Scan;

class ScanCommand extends Command
{
    private Scan $scan;
    private SerializerInterface $serializer;
    private RuntimeConfig $runtimeConfig;
    private MessageBucket $messageBucket;

    /**
     * DeleteRuleCommand constructor.
     *
     * @param Scan $scan
     * @param SerializerInterface $serializer
     * @param RuntimeConfig $runtimeConfig
     */
    public function __construct(
        Scan $scan,
        SerializerInterface $serializer,
        RuntimeConfig $runtimeConfig,
        MessageBucket $messageBucket,
        $name = null
    ) {
        parent::__construct($name);
        $this->scan = $scan;
        $this->serializer = $serializer;
        $this->runtimeConfig = $runtimeConfig;
        $this->messageBucket = $messageBucket;
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
        
        $this->runtimeConfig->setHideDeprecated((bool)$input->getOption('hide-deprecated'));
        $this->runtimeConfig->setHideNeedless((bool)$input->getOption('hide-needless'));

        $this->scan->scan($moduleName, $modulePath);
        $messages = $this->messageBucket->getMessages();

        if ((string)$input->getOption('format') === 'json') {
            $outputData = [];
            foreach ($messages as $message) {
                $outputData[] = $message->toArray();
            }

            $output->writeln($this->serializer->serialize($outputData));
            return empty($messageGroups) ? 0 : 1;
        }

        if (empty($messages)) {
            return 1;
        }

        $table = new Table($output);
        $table->setHeaders([
            'Message',
            'Group',
            'Suggestion'
        ]);

        foreach ($messages as $message) {
            $table->addRow([
                $message->getMessage(),
                $message->getGroupLabel(),
                $message->getSuggestion(),
            ]);
        }
        
        $table->render();

        return 0;
    }
}
