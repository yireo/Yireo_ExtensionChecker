<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Console\Command;

use InvalidArgumentException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Console\CommandList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Console\Command\CheckMagentoVersionCommand;

class CheckMagentoVersionCommandTest extends TestCase
{
    public function testCommandIsRegistered()
    {
        $commands = ObjectManager::getInstance()->get(CommandList::class)->getCommands();
        $this->assertArrayHasKey('yireo_extensionchecker_check_magento_version', $commands);
        $this->assertInstanceOf(
            CheckMagentoVersionCommand::class,
            $commands['yireo_extensionchecker_check_magento_version']
        );
    }

    public function testExecuteRejectsInjectedMagentoVersion()
    {
        $command = ObjectManager::getInstance()->create(CheckMagentoVersionCommand::class);
        $application = new Application();
        $application->add($command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid Magento version');
        (new CommandTester($command))->execute([
            '--module' => 'Yireo_ExtensionChecker',
            '--magento-version' => '2.4.7; touch /tmp/yireo-extensionchecker-pwned',
        ]);
    }
}
