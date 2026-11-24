<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Util;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Io\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;
use Yireo\ExtensionChecker\Util\OtherModuleInfo;

class OtherModuleInfoTest extends TestCase
{
    use TemporaryFolder;

    public function testGetByPath()
    {
        $moduleFolder = $this->createTemporaryFolder([
            'etc/module.xml' => '<config><module name="Foo_Bar" setup_version="1.0.0">'
                . '<sequence><module name="Magento_Store"/></sequence></module></config>',
        ]);

        $this->assertSame([
            'path' => $moduleFolder,
            'name' => 'Foo_Bar',
            'setup_version' => '1.0.0',
            'sequence' => ['Magento_Store'],
        ], $this->getOtherModuleInfo()->getByPath($moduleFolder));
    }

    /**
     * @dataProvider getMalformedModuleXml
     */
    #[DataProvider('getMalformedModuleXml')]
    public function testGetByPathWithMalformedModuleXml(string $moduleXml, bool $previousErrorSetting)
    {
        $moduleFolder = $this->createTemporaryFolder(['etc/module.xml' => $moduleXml]);
        $originalErrorSetting = libxml_use_internal_errors($previousErrorSetting);

        try {
            $this->getOtherModuleInfo()->getByPath($moduleFolder);
            $this->fail('Expected ModuleNotFoundException was not thrown');
        } catch (ModuleNotFoundException $exception) {
            $this->assertStringContainsString($moduleFolder . '/etc/module.xml', $exception->getMessage());
        } finally {
            $currentErrorSetting = libxml_use_internal_errors($originalErrorSetting);
        }

        $this->assertSame($previousErrorSetting, $currentErrorSetting);
        $this->assertFalse(libxml_get_last_error());
    }

    public static function getMalformedModuleXml(): array
    {
        return [
            ['<config><module name="Foo_Bar"', false],
            ['<config><module name="Foo_Bar"', true],
            ['', false],
            ['not xml at all', false],
        ];
    }

    private function getOtherModuleInfo(): OtherModuleInfo
    {
        return new OtherModuleInfo(new File(), $this->createMock(ComponentRegistrar::class));
    }
}
