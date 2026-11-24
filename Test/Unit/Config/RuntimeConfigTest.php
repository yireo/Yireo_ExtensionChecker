<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ExtensionChecker\Config\RuntimeConfig;

class RuntimeConfigTest extends TestCase
{
    public function testDebugMessageEscapesModuleControlledStrings()
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE, true);
        $runtimeConfig = new RuntimeConfig();
        $runtimeConfig->setOutput($output);
        $runtimeConfig->setVerbose(true);

        $runtimeConfig->debugMessage("Component by module name: Foo</info><error>x\e[2J\u{9b}");

        $display = $output->fetch();
        $this->assertSame("Component by module name: Foo</info><error>x[2J\n", $display);
    }

    public function testDebugMessageWithoutOutput()
    {
        $runtimeConfig = new RuntimeConfig();
        $runtimeConfig->setVerbose(true);
        $runtimeConfig->setOutput(null);

        $runtimeConfig->debugMessage('Hello');
        $this->assertNull($runtimeConfig->getOutput());
    }

    public function testDebugMessageIsSkippedWhenNotVerbose()
    {
        $output = new BufferedOutput();
        $runtimeConfig = new RuntimeConfig();
        $runtimeConfig->setOutput($output);

        $runtimeConfig->debugMessage('Hello');
        $this->assertSame('', $output->fetch());
    }
}
