<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass\ClassInspector;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Console\Cli as CliApplication;
use Magento\Framework\Console\CliFactory as CliApplicationFactory;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandClassDetector implements ClassDetectorInterface
{
    private CliApplicationFactory $cliApplicationFactory;
    private ?CliApplication $cliApplication = null;

    public function __construct(
        CliApplicationFactory $cliApplicationFactory
    ) {
        $this->cliApplicationFactory = $cliApplicationFactory;
    }

    public function getClassNames(string $phpFileContent): array
    {
        if (!preg_match_all("/->find\('([a-zA-Z0-9\_\-\:]+)'\)/", $phpFileContent, $matches)) {
            return [];
        }

        $classnames = [];

        foreach ($matches[1] as $commandName) {
            try {
                $command = $this->getCliApplication()->find($commandName);
            } catch (CommandNotFoundException $exception) {
                continue;
            }

            $classnames[] = get_class($command);
        }

        return $classnames;
    }

    private function getCliApplication(): CliApplication
    {
        if ($this->cliApplication instanceof CliApplication) {
            return $this->cliApplication;
        }

        // Constructing the CLI application bootstraps a new ObjectManager and sets it as the global instance
        $objectManager = ObjectManager::getInstance();
        try {
            $this->cliApplication = $this->cliApplicationFactory->create();
        } finally {
            ObjectManager::setInstance($objectManager);
        }

        return $this->cliApplication;
    }
}
