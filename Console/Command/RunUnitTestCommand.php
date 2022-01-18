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
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Input\InputOption;
use Yireo\ExtensionChecker\Scan\Scan;

class RunUnitTestCommand extends Command
{
    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @param ComponentRegistrar $componentRegistrar
     * @param string|null $name
     */
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        string $name = null
    ) {
        parent::__construct($name);
        $this->componentRegistrar = $componentRegistrar;
    }

    protected function configure()
    {
        $this->setName('yireo_extensionchecker:unit');
        $this->setDescription('Run PHPUnit unit tests for a specific Magento module');
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|void
     */
    protected function execute(Input $input, Output $output)
    {
        $moduleName = $input->getArgument('module');
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);

        if (is_dir($modulePath . '/Test/Unit')) {
            return passthru($_SERVER['_'] . ' ./vendor/bin/phpunit --colors=always ' . $modulePath . '/Test/Unit/');
        }

        return 0;
    }
}
