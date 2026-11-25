<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Test\Integration\Behaviour\InstalledCorePackage;

class ComposerFileProviderTest extends TestCase
{
    public function testCreation()
    {
        $om = ObjectManager::getInstance();
        $composerFileProvider = $om->get(ComposerFileProvider::class);
        $composerFile = $composerFileProvider->getComposerFileByModuleName('Magento_Catalog');
        $this->assertEquals(InstalledCorePackage::getName('module-catalog'), $composerFile->getName());
    }
}
