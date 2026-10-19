<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Suggestion;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Suggestion\ModuleXmlSuggestion;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class ModuleXmlSuggestionTest extends TestCase
{
    use TemporaryFolder;

    public function testGetCurrentPath()
    {
        $moduleFolder = $this->createTemporaryFolder();
        $suggestion = $this->getSuggestion($moduleFolder, []);
        $this->assertSame($moduleFolder . '/etc/module.xml', $suggestion->getCurrentPath('Foo_Bar'));
    }

    public function testGetCurrentPathWithUnknownModule()
    {
        $suggestion = $this->getSuggestion(null, []);
        $this->expectException(ModuleNotFoundException::class);
        $suggestion->getCurrentPath('Foo_Bar');
    }

    public function testGetCurrentContents()
    {
        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => 'foobar']);
        $this->assertSame('foobar', $this->getSuggestion($moduleFolder, [])->getCurrentContents('Foo_Bar'));

        $moduleFolder = $this->createTemporaryFolder();
        $this->assertSame('', $this->getSuggestion($moduleFolder, [])->getCurrentContents('Foo_Bar'));
    }

    public function testGetSuggestedContentsWithoutExistingFile()
    {
        $moduleFolder = $this->createTemporaryFolder();
        $suggestion = $this->getSuggestion($moduleFolder, [
            new Component('Magento_Store', 'module', 'magento/module-store', '101.0.0'),
            new Component('Magento_Catalog', 'module', 'magento/module-catalog', '104.0.0'),
            new Component('Magento_Catalog', 'module', 'magento/module-catalog', '104.0.0', true),
            new Component('psr/log', 'library', 'psr/log', '3.0.0'),
        ]);

        $expected = <<<EOF
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Foo_Bar">
        <sequence>
            <module name="Magento_Catalog"/>
            <module name="Magento_Store"/>
        </sequence>
    </module>
</config>

EOF;
        $this->assertSame($expected, $suggestion->getSuggestedContents('Foo_Bar'));
    }

    public function testGetSuggestedContentsWithoutDependencies()
    {
        $moduleFolder = $this->createTemporaryFolder();
        $suggestion = $this->getSuggestion($moduleFolder, []);

        $expected = <<<EOF
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Foo_Bar"/>
</config>

EOF;
        $this->assertSame($expected, $suggestion->getSuggestedContents('Foo_Bar'));
    }

    public function testGetSuggestedContentsPreservesHeaderAndAttributes()
    {
        $currentModuleXml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!--
/**
 * Copyright Foo
 */
-->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Foo_Bar" setup_version="1.2.3">
        <sequence>
            <module name="Foo_Obsolete"/>
        </sequence>
    </module>
</config>
EOF;

        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => $currentModuleXml]);
        $suggestion = $this->getSuggestion($moduleFolder, [
            new Component('Magento_Store', 'module', 'magento/module-store', '101.0.0'),
        ]);

        $expected = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!--
/**
 * Copyright Foo
 */
-->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Foo_Bar" setup_version="1.2.3">
        <sequence>
            <module name="Magento_Store"/>
        </sequence>
    </module>
</config>
EOF;

        // No trailing newline, because the current file does not have one either
        $this->assertSame($expected, $suggestion->getSuggestedContents('Foo_Bar'));
    }

    public function testGetSuggestedContentsKeepsTrailingNewline()
    {
        $currentModuleXml = '<?xml version="1.0"?>' . "\n" . '<config><module name="Foo_Bar"/></config>' . "\n";
        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => $currentModuleXml]);
        $suggestion = $this->getSuggestion($moduleFolder, []);

        $suggestedContents = $suggestion->getSuggestedContents('Foo_Bar');
        $this->assertStringEndsWith("</config>\n", $suggestedContents);
        $this->assertStringEndsNotWith("\n\n", $suggestedContents);
    }

    public function testGetSuggestedContentsWithInvalidCurrentXml()
    {
        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => '<config><module name="Foo_Bar"']);
        $suggestion = $this->getSuggestion($moduleFolder, []);

        $suggestedContents = $suggestion->getSuggestedContents('Foo_Bar');
        $this->assertStringStartsWith('<?xml version="1.0"?>' . "\n" . '<config ', $suggestedContents);
        $this->assertStringContainsString('<module name="Foo_Bar"/>', $suggestedContents);
    }

    /**
     * @param Component[] $components
     */
    private function getSuggestion(?string $moduleFolder, array $components): ModuleXmlSuggestion
    {
        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')
            ->with(ComponentRegistrar::MODULE, 'Foo_Bar')
            ->willReturn($moduleFolder);

        $componentDetectorList = $this->createMock(ComponentDetectorList::class);
        $componentDetectorList->method('getComponentsByModuleName')
            ->with('Foo_Bar')
            ->willReturn($components);

        return new ModuleXmlSuggestion($componentRegistrar, $componentDetectorList);
    }
}
