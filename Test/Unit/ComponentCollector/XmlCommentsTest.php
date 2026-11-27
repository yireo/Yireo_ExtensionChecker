<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\ComponentCollector;

use Magento\Framework\Filesystem\File\Read;
use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\ComponentCollector\AbstractComponentCollector;
use Yireo\ExtensionChecker\ComponentCollector\LayoutComponentCollector;
use Yireo\ExtensionChecker\ComponentCollector\XmlComponentCollector;
use Yireo\ExtensionChecker\PhpClass\ComponentCollector;

class XmlCommentsTest extends TestCase
{
    /**
     * @dataProvider xmlContentsProvider
     */
    public function testCollectsOnlyActiveReferences(
        string $collectorClass,
        string $contents,
        array $moduleNames,
        array $classNames
    ): void {
        $fileRead = $this->createMock(Read::class);
        $fileRead->expects($this->once())->method('readAll')->willReturn($contents);
        $fileReadFactory = $this->createMock(FileReadFactory::class);
        $fileReadFactory->expects($this->once())->method('create')->with('config.xml', 'file')->willReturn($fileRead);

        $hardRequirement = $collectorClass === XmlComponentCollector::class;
        $componentFactory = $this->createMock(ComponentFactory::class);
        $componentFactory->expects($this->exactly(count($moduleNames)))
            ->method('createByModuleName')
            ->willReturnCallback(function (string $moduleName, bool $hard) use (&$moduleNames, $hardRequirement) {
                $this->assertSame(array_shift($moduleNames), $moduleName);
                $this->assertSame($hardRequirement, $hard);
                return new Component($moduleName, 'module', '', '', $hard);
            });

        $classComponents = array_map(
            static fn (string $className) => new Component($className, 'library', '', '', true),
            $classNames
        );
        $componentCollector = $this->createMock(ComponentCollector::class);
        $componentCollector->expects($hardRequirement ? ($classNames ? $this->once() : $this->any()) : $this->never())
            ->method('getComponentsByClasses')
            ->willReturnCallback(function (array $actualClassNames) use ($classNames, $classComponents) {
                $this->assertSame($classNames, $actualClassNames);
                return $classComponents;
            });

        $expectedNames = array_merge($moduleNames, $classNames);
        $collector = $hardRequirement
            ? new XmlComponentCollector($fileReadFactory, $componentFactory, $componentCollector)
            : new LayoutComponentCollector($componentFactory, $componentCollector, $fileReadFactory);
        $components = $collector->getComponentsFromFile('config.xml');
        $actualNames = array_map(static fn (Component $component) => $component->getComponentName(), $components);
        $this->assertEqualsCanonicalizing($expectedNames, $actualNames);
    }

    public function testPreservesContentsWhenCommentRemovalExceedsBacktrackLimit(): void
    {
        $collector = new class (
            $this->createMock(ComponentFactory::class),
            $this->createMock(ComponentCollector::class)
        ) extends AbstractComponentCollector {
            public function stripXmlComments(string $contents): string
            {
                return parent::stripXmlComments($contents);
            }
        };
        $contents = '<config><!--' . str_repeat('x', 1000) . '--><item>Vendor_Module::foo</item></config>';
        $originalLimit = ini_get('pcre.backtrack_limit');

        try {
            ini_set('pcre.backtrack_limit', '1');
            $result = $collector->stripXmlComments($contents);
            $error = preg_last_error();
        } finally {
            ini_set('pcre.backtrack_limit', $originalLimit);
        }

        $this->assertSame(PREG_BACKTRACK_LIMIT_ERROR, $error);
        $this->assertSame($contents, $result);
    }

    public static function xmlContentsProvider(): array
    {
        $cases = [
            'commented references' => [
                '<config><!-- see Vendor_Other::foo and Vendor\Other\Model\Bar --></config>', [], [],
            ],
            'commented class' => ['<config><!-- Vendor\Other\Model\Bar --></config>', [], []],
            'commented patterns' => ['<config><!-- hyva.modal hyva_modal --></config>', [], []],
            'multiline and multiple comments' => [
                "<config><!--\nVendor_Other::foo\nVendor\\Other\\Model\\Bar\n--><!-- hyva.modal hyva_modal --></config>",
                [], [],
            ],
            'active references around comments' => [
                '<config><item>First_Module::foo</item><!-- Vendor_Other::foo Vendor\Other\Model\Bar -->'
                . '<item>Last_Module::bar</item><item>Active\Module\Model\Item</item></config>',
                ['First_Module', 'Last_Module'], ['Active\Module\Model\Item'],
            ],
            'active patterns' => ['<config><item>hyva.modal hyva_modal</item></config>', ['Hyva_Theme'], []],
            'empty content' => ['', [], []],
        ];

        $data = [];
        foreach ([XmlComponentCollector::class, LayoutComponentCollector::class] as $collectorClass) {
            foreach ($cases as $name => [$contents, $moduleNames, $classNames]) {
                $data[$collectorClass . ' ' . $name] = [
                    $collectorClass,
                    $contents,
                    $moduleNames,
                    $collectorClass === XmlComponentCollector::class ? $classNames : [],
                ];
            }
        }
        return $data;
    }
}
