<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\DirectoryList;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerFile;

class ComposerFileTest extends TestCase
{
    public function testCreation()
    {
        $om = ObjectManager::getInstance();
        $directoryList = $om->get(DirectoryList::class);
        $composerFilePath = $directoryList->getRoot() . '/vendor/magento/framework/composer.json';
        $composerFile = $om->create(ComposerFile::class, ['composerFile' => $composerFilePath]);
        $this->assertNotEmpty($composerFile->getData());
        $this->assertEquals('magento/framework', $composerFile->getName());
        $this->assertEquals('magento2-library', $composerFile->get('type'));
        $this->assertNotEmpty($composerFile->getRequirements());
    }
}