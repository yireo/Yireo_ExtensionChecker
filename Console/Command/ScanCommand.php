<?php
/**
 * Yireo ExtensionChecker for Magento
 *
 * @package     Yireo_ExtensionChecker
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2018 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

declare(strict_types=1);

namespace Yireo\ExtensionChecker\Console\Command;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Input\InputOption;
use Yireo\ExtensionChecker\Scan\Scan;

/**
 * Class ScanCommand
 */
class ScanCommand extends Command
{
    /**
     * @var Scan
     */
    private $scan;

    /**
     * DeleteRuleCommand constructor.
     *
     * @param Scan $scan
     * @param string $name
     */
    public function __construct(
        Scan $scan,
        $name = null
    ) {

        $rt = parent::__construct($name);
        $this->scan = $scan;
        return $rt;
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:scan');
        $this->setDescription('Scan a specific Magento module');

        $this->addArgument(
            'module',
            InputOption::VALUE_REQUIRED,
            'Module name'
        );

        $this->addOption(
            'hide-deprecated',
            null,
            InputOption::VALUE_OPTIONAL,
            'Hide deprecated dependency notices'
        );
    }

    /**
     * @param Input $input
     * @param Output $output
     *
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $moduleName = (string)$input->getArgument('module');
        } catch (Exception $e) {
            throw new InvalidArgumentException('Unable to initialize arguments');
        }

        if (empty($moduleName)) {
            throw new InvalidArgumentException('Argument "Foo_Bar" is missing');
        }

        $hideDeprecated = (bool)$input->getOption('hide-deprecated');

        $this->scan->setOutput($output);
        $this->scan->setModuleName($moduleName);
        $this->scan->setHideDeprecated($hideDeprecated);
        $this->scan->scan();
    }
}
