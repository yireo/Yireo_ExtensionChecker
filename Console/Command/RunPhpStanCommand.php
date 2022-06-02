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
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class RunPhpStanCommand extends Command
{
    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var DirectoryList
     */
    private $directoryList;
    
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * @param ComponentRegistrar $componentRegistrar
     * @param DirectoryList $directoryList
     * @param Shell $shell
     * @param string|null $name
     */
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        DirectoryList $directoryList,
        Shell $shell,
        string $name = null
    ) {
        parent::__construct($name);
        $this->componentRegistrar = $componentRegistrar;
        $this->directoryList = $directoryList;
        $this->shell = $shell;
    }

    protected function configure()
    {
        $this->setName('yireo_extensionchecker:phpstan');
        $this->setDescription('Run PHPStan for a specific Magento module');
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name');
        $this->addArgument('level', InputArgument::OPTIONAL, 'PHPStan level (default 2)', 2);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|void
     */
    protected function execute(Input $input, Output $output)
    {
        $moduleName = $input->getArgument('module');
        $level = $input->getArgument('level');

        $this->generatePhpStanConfigurationIfMissing();
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $this->shell->execute('vendor/bin/phpstan analyse --configuration=./phpstan.neon --level='.$level.' --no-progress ' . $modulePath);
        return 1;
    }

    private function generatePhpStanConfigurationIfMissing()
    {
        $configFile = $this->directoryList->getRoot().'/phpstan.neon';
        if (file_exists($configFile)) {
            return;
        }

        file_put_contents($configFile, <<<HEREDOC
parameters:
    excludes_analyse:
        - */Test/*/*

HEREDOC
        );

    }
}