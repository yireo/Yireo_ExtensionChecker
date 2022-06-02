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

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class RunCodingStandardCommand extends Command
{
    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;
    
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * @param ComponentRegistrar $componentRegistrar
     * @param Shell $shell
     * @param string|null $name
     */
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        Shell $shell,
        string $name = null
    ) {
        parent::__construct($name);
        $this->componentRegistrar = $componentRegistrar;
        $this->shell = $shell;
    }
    
    protected function configure()
    {
        $this->setName('yireo_extensionchecker:phpcs');
        $this->setDescription('Run PHPCS for a specific Magento module');
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name');
        $this->addArgument('severity', InputArgument::OPTIONAL, 'PHPCS severity (default 7)', 7);
    }
    
    /**
     * @param Input $input
     * @param Output $output
     * @return int|void
     */
    protected function execute(Input $input, Output $output)
    {
        $moduleName = $input->getArgument('module');
        $severity = $input->getArgument('severity');
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $phpcs = 'vendor/bin/phpcs';
        
        if (!$this->checkIfMagento2StandardIsInstalled()) {
            $this->shell->execute($phpcs . ' --config-set installed_paths vendor/magento/magento-coding-standard/');
        }
        
        $this->shell->execute($phpcs . ' --standard=Magento2 --colors --severity=' . $severity . ' ' . $modulePath);
        return 0;
    }
    
    /**
     * @return bool
     * @throws LocalizedException
     */
    private function checkIfMagento2StandardIsInstalled(): bool
    {
        $output = $this->shell->execute('vendor/bin/phpcs -i');
        return (bool)stristr($output, 'Magento2');
    }
}