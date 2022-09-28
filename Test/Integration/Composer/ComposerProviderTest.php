<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerProvider;

class ComposerProviderTest extends TestCase
{
    public function testGetVersionByComposerName()
    {
        $composerProvider = ObjectManager::getInstance()->get(ComposerProvider::class);
        $version = $composerProvider->getVersionByComposerName('magento/framework');
        $this->assertNotEmpty($version);
    }

    public function testGetComposerPackages()
    {
        $composerProvider = ObjectManager::getInstance()->get(ComposerProvider::class);
        $composerPackages = $composerProvider->getComposerPackages();
        foreach ($composerPackages as $composerPackage) {
            $this->assertNotEmpty($composerPackage['name']);
            $this->assertNotEmpty($composerPackage['version']);
        }
    }
}
