<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass\ClassInspector;

use Magento\Framework\Console\Cli as CliApplication;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandClassDetector implements ClassDetectorInterface
{
    private CliApplication $cliApplication;

    public function __construct(
        CliApplication $cliApplication
    ) {
        $this->cliApplication = $cliApplication;
    }

    public function getClassNames(string $phpFileContent): array
    {
        if (!preg_match_all("/->find\('([a-zA-Z0-9\_\-\:]+)'\)/", $phpFileContent, $matches)) {
            return [];
        }

        $classnames = [];

        foreach ($matches[1] as $commandName) {
            try {
                $command = $this->cliApplication->find($commandName);
            } catch (CommandNotFoundException $exception) {
                continue;
            }

            $classnames[] = get_class($command);
        }

        return $classnames;
    }
}
