<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Composer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Composer\MagentoPackageResolver;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class MagentoPackageResolverTest extends TestCase
{
    use TemporaryFolder;

    public function testGetMagentoReplacement()
    {
        $resolver = $this->getResolver([
            'mage-os/module-csp' => [
                'name' => 'mage-os/module-csp',
                'replace' => ['magento/module-csp' => '100.4.7'],
            ],
        ]);

        $this->assertSame(
            ['name' => 'magento/module-csp', 'version' => '100.4.7'],
            $resolver->getMagentoReplacement('mage-os/module-csp')
        );
    }

    public function testGetMagentoReplacementWithDifferentlyNamedPackage()
    {
        $resolver = $this->getResolver([
            'mage-os/module-foo' => [
                'replace' => ['other/package' => '1.0.0', 'magento/module-bar' => '101.0.0'],
            ],
        ]);

        $this->assertSame(
            ['name' => 'magento/module-bar', 'version' => '101.0.0'],
            $resolver->getMagentoReplacement('mage-os/module-foo')
        );
    }

    public function testGetMagentoReplacementWithoutMagentoEquivalent()
    {
        $resolver = $this->getResolver([
            'mage-os/module-automatic-translation' => ['name' => 'mage-os/module-automatic-translation'],
            'mage-os/module-admin-activity-log' => ['replace' => ['other/package' => '1.0.0']],
        ]);

        $this->assertNull($resolver->getMagentoReplacement('mage-os/module-automatic-translation'));
        $this->assertNull($resolver->getMagentoReplacement('mage-os/module-admin-activity-log'));
        $this->assertNull($resolver->getMagentoReplacement('mage-os/module-missing'));
    }

    public function testGetMagentoReplacementWithNonMageOsPackage()
    {
        $resolver = $this->getResolver([
            'magento/module-csp' => ['replace' => ['magento/module-csp' => '100.4.7']],
        ]);

        $this->assertNull($resolver->getMagentoReplacement('magento/module-csp'));
        $this->assertNull($resolver->getMagentoReplacement(''));
    }

    public function testGetMagentoReplacementWithInvalidJson()
    {
        $folder = $this->createTemporaryFolder(['vendor/mage-os/module-csp/composer.json' => '{"name": ']);
        $this->assertNull($this->createResolver($folder)->getMagentoReplacement('mage-os/module-csp'));
    }

    public function testGetMagentoModuleName()
    {
        $resolver = $this->getResolver([
            'mage-os/module-foo' => ['replace' => ['magento/module-foo' => '100.0.0']],
            'mage-os/module-bar' => ['name' => 'mage-os/module-bar'],
        ]);

        $this->assertSame(
            'Magento_Foo',
            $resolver->getMagentoModuleName(new Component('MageOs_Foo', 'module', 'mage-os/module-foo'))
        );
        $this->assertNull($resolver->getMagentoModuleName(new Component('MageOs_Bar', 'module', 'mage-os/module-bar')));
        $this->assertNull(
            $resolver->getMagentoModuleName(new Component('Magento_Foo', 'module', 'mage-os/module-foo'))
        );
    }

    /**
     * @param array<string, array> $packages
     */
    private function getResolver(array $packages): MagentoPackageResolver
    {
        $files = [];
        foreach ($packages as $packageName => $composerData) {
            $files['vendor/' . $packageName . '/composer.json'] = (string)json_encode($composerData);
        }

        return $this->createResolver($this->createTemporaryFolder($files));
    }

    private function createResolver(string $rootFolder): MagentoPackageResolver
    {
        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn($rootFolder);

        return new MagentoPackageResolver($directoryList, new FileDriver(), new Json());
    }
}
