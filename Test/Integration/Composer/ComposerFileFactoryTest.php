<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerFileFactory;
use Yireo\ExtensionChecker\Test\Integration\Behaviour\InstalledCorePackage;

class ComposerFileFactoryTest extends TestCase
{
    public function testCreation()
    {
        $om = ObjectManager::getInstance();
        $composerFilePath = InstalledCorePackage::getComposerFilePath('module-customer');
        $composerFileFactory = $om->create(ComposerFileFactory::class);
        $composerFile = $composerFileFactory->create($composerFilePath);
        $this->assertNotEmpty($composerFile->getData());
        $this->assertEquals(InstalledCorePackage::getName('module-customer'), $composerFile->getName());
        $this->assertEquals('magento2-module', $composerFile->get('type'));
        $this->assertNotEmpty($composerFile->getRequirements());
    }
}
