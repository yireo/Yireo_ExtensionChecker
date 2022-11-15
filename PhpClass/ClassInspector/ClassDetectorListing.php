<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass\ClassInspector;

class ClassDetectorListing
{
    private CommandClassDetector $commandClassDetector;

    public function __construct(
        CommandClassDetector $commandClassDetector
    ) {
        $this->commandClassDetector = $commandClassDetector;
    }

    /**
     * @return ClassDetectorInterface[]
     */
    public function get(): array
    {
        return [
            $this->commandClassDetector
        ];
    }
}
