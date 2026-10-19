<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Console\Command;

use DOMDocument;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\Console\CommandList;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ExtensionChecker\Console\Command\AutofixComposerJsonCommand;
use Yireo\ExtensionChecker\Console\Command\AutofixModuleXmlCommand;
use Yireo\ExtensionChecker\Console\Command\DiffComposerJsonCommand;
use Yireo\ExtensionChecker\Console\Command\DiffModuleXmlCommand;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class SuggestionCommandsTest extends TestCase
{
    use TemporaryFolder;

    private const FIXTURE_MODULE = 'Yireo_ExtensionCheckerFixture';

    private const FIXTURE_MODULE_XML = <<<EOF
<?xml version="1.0"?>
<!--
/**
 * Fixture header comment
 */
-->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Yireo_ExtensionCheckerFixture" setup_version="1.0.0">
        <sequence>
            <module name="Foo_Obsolete"/>
        </sequence>
    </module>
</config>

EOF;

    private const FIXTURE_LAYOUT_XML = <<<EOF
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="content">
            <block name="fixture" template="Magento_Catalog::product/list.phtml"/>
        </referenceContainer>
    </body>
</page>

EOF;

    private const FIXTURE_COMPOSER_JSON = <<<EOF
{
    "name": "yireo/magento2-extensionchecker-fixture",
    "type": "magento2-module",
    "require": {
        "foo/obsolete": "^1.0"
    }
}

EOF;

    private string $fixtureFolder = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureFolder = $this->createTemporaryFolder([
            'registration.php' => "<?php declare(strict_types=1);\n",
            'etc/module.xml' => self::FIXTURE_MODULE_XML,
            'view/frontend/layout/default.xml' => self::FIXTURE_LAYOUT_XML,
            'composer.json' => self::FIXTURE_COMPOSER_JSON,
        ], 'yireo-extensionchecker-fixture-module'); // Fixed path, because module paths are cached by name

        ComponentRegistrar::register(ComponentRegistrar::MODULE, self::FIXTURE_MODULE, $this->fixtureFolder);
    }

    protected function tearDown(): void
    {
        $pathsProperty = new ReflectionProperty(ComponentRegistrar::class, 'paths');
        $paths = $pathsProperty->getValue();
        unset($paths[ComponentRegistrar::MODULE][self::FIXTURE_MODULE]);
        $pathsProperty->setValue(null, $paths);

        $this->removeTemporaryFolders();
        parent::tearDown();
    }

    public function testCommandsAreRegistered()
    {
        $commands = ObjectManager::getInstance()->get(CommandList::class)->getCommands();

        $expectedCommands = [
            'yireo_extensionchecker_diff_module_xml' => DiffModuleXmlCommand::class,
            'yireo_extensionchecker_diff_composer_json' => DiffComposerJsonCommand::class,
            'yireo_extensionchecker_autofix_module_xml' => AutofixModuleXmlCommand::class,
            'yireo_extensionchecker_autofix_composer_json' => AutofixComposerJsonCommand::class,
        ];

        foreach ($expectedCommands as $commandKey => $commandClass) {
            $this->assertArrayHasKey($commandKey, $commands);
            $this->assertInstanceOf($commandClass, $commands[$commandKey]);
        }
    }

    public function testDiffAndAutofixModuleXml()
    {
        $diffCommandTester = new CommandTester($this->getCommand(DiffModuleXmlCommand::class));
        $this->assertSame(Command::FAILURE, $diffCommandTester->execute(['module' => self::FIXTURE_MODULE]));
        $display = $diffCommandTester->getDisplay();
        $this->assertStringContainsString('--- ' . $this->fixtureFolder . '/etc/module.xml', $display);
        $this->assertStringContainsString('-            <module name="Foo_Obsolete"/>', $display);
        $this->assertStringContainsString('+            <module name="Magento_Catalog"/>', $display);
        $this->assertSame(self::FIXTURE_MODULE_XML, file_get_contents($this->fixtureFolder . '/etc/module.xml'));

        $autofixCommandTester = new CommandTester($this->getCommand(AutofixModuleXmlCommand::class));
        $this->assertSame(Command::SUCCESS, $autofixCommandTester->execute(['module' => self::FIXTURE_MODULE]));

        $moduleXml = (string)file_get_contents($this->fixtureFolder . '/etc/module.xml');
        $this->assertStringContainsString('Fixture header comment', $moduleXml);
        $this->assertStringContainsString('setup_version="1.0.0"', $moduleXml);
        $this->assertStringContainsString('<module name="Magento_Catalog"/>', $moduleXml);
        $this->assertStringNotContainsString('Foo_Obsolete', $moduleXml);
        $this->assertModuleXmlIsValid($moduleXml);

        $diffCommandTester = new CommandTester($this->getCommand(DiffModuleXmlCommand::class));
        $this->assertSame(Command::SUCCESS, $diffCommandTester->execute(['module' => self::FIXTURE_MODULE]));
    }

    public function testDiffAndAutofixComposerJson()
    {
        $diffCommandTester = new CommandTester($this->getCommand(DiffComposerJsonCommand::class));
        $this->assertSame(Command::FAILURE, $diffCommandTester->execute(['module' => self::FIXTURE_MODULE]));
        $display = $diffCommandTester->getDisplay();
        $this->assertStringContainsString('--- ' . $this->fixtureFolder . '/composer.json', $display);
        $this->assertStringContainsString('-        "foo/obsolete": "^1.0"', $display);
        $this->assertStringContainsString('+        "magento/module-catalog": ', $display);
        $this->assertSame(self::FIXTURE_COMPOSER_JSON, file_get_contents($this->fixtureFolder . '/composer.json'));

        $autofixCommandTester = new CommandTester($this->getCommand(AutofixComposerJsonCommand::class));
        $this->assertSame(
            Command::SUCCESS,
            $autofixCommandTester->execute(['module' => self::FIXTURE_MODULE, '--with-php' => true])
        );

        $composerData = json_decode((string)file_get_contents($this->fixtureFolder . '/composer.json'), true);
        $this->assertSame('yireo/magento2-extensionchecker-fixture', $composerData['name']);
        $this->assertSame('magento2-module', $composerData['type']);
        $this->assertArrayHasKey('magento/module-catalog', $composerData['require']);
        $this->assertArrayHasKey('php', $composerData['require']);
        $this->assertArrayNotHasKey('foo/obsolete', $composerData['require']);

        $diffCommandTester = new CommandTester($this->getCommand(DiffComposerJsonCommand::class));
        $this->assertSame(
            Command::SUCCESS,
            $diffCommandTester->execute(['module' => self::FIXTURE_MODULE, '--with-php' => true])
        );
    }

    public function testSuggestionsForThisModule()
    {
        $objectManager = ObjectManager::getInstance();

        $moduleXml = $objectManager->get(ModuleXmlSuggestion::class)->getSuggestedContents('Yireo_ExtensionChecker');
        $this->assertStringContainsString('<module name="Yireo_ExtensionChecker">', $moduleXml);
        $this->assertStringContainsString('<module name="Magento_Catalog"/>', $moduleXml);
        $this->assertModuleXmlIsValid($moduleXml);

        $composerJson = $objectManager->get(ComposerJsonSuggestion::class)->getSuggestedContents('Yireo_ExtensionChecker');
        $composerData = json_decode($composerJson, true);
        $this->assertSame('yireo/magento2-extensionchecker', $composerData['name']);
        $this->assertArrayHasKey('sebastian/diff', $composerData['require']);
    }

    private function getCommand(string $commandClass): Command
    {
        return ObjectManager::getInstance()->create($commandClass);
    }

    private function assertModuleXmlIsValid(string $moduleXml): void
    {
        $urnResolver = ObjectManager::getInstance()->get(UrnResolver::class);
        $schemaFile = $urnResolver->getRealPath('urn:magento:framework:Module/etc/module.xsd');

        $document = new DOMDocument();
        $document->loadXML($moduleXml);
        $this->assertTrue($document->schemaValidate($schemaFile), 'Suggested module.xml is not valid');
    }
}
