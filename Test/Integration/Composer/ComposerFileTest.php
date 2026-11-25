<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerFile;
use Yireo\ExtensionChecker\Test\Integration\Behaviour\InstalledCorePackage;

class ComposerFileTest extends TestCase
{
    public function testCreation()
    {
        $om = ObjectManager::getInstance();
        $composerFilePath = InstalledCorePackage::getComposerFilePath('framework');
        $composerFile = $om->create(ComposerFile::class, ['composerFile' => $composerFilePath]);
        $this->assertNotEmpty($composerFile->getData());
        $this->assertEquals(InstalledCorePackage::getName('framework'), $composerFile->getName());
        $this->assertEquals('magento2-library', $composerFile->get('type'));
        $this->assertNotEmpty($composerFile->getRequirements());
    }
}
