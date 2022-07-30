<?php

declare(strict_types=1);

namespace Yireo\ExtensionChecker\Console;

use Magento\Framework\ObjectManagerInterface;
use Yireo\ExtensionChecker\Console\Command\ScanCommand;

/**
 * Provide list of CLI commands to be available for not-installed application
 */
class CommandList implements \Magento\Framework\Console\CommandListInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Gets list of command classes
     *
     * @return string[]
     */
    private function getCommandsClasses(): array
    {
        return [ScanCommand::class];
    }

    /**
     * @inheritdoc
     */
    public function getCommands(): array
    {
        $commands = [];
        foreach ($this->getCommandsClasses() as $class) {
            if (class_exists($class)) {
                $commands[] = $this->objectManager->get($class);
            } else {
                throw new \RuntimeException('Class ' . $class . ' does not exist');
            }
        }

        return $commands;
    }
}
