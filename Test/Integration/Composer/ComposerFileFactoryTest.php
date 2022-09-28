<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Composer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\DirectoryList;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerFileFactory;

class ComposerFileFactoryTest extends TestCase
{
    public function testCreation()
    {
        $om = ObjectManager::getInstance();
        $directoryList = $om->get(DirectoryList::class);
        $composerFilePath = $directoryList->getRoot() . '/vendor/magento/module-customer/composer.json';
        $composerFileFactory = $om->create(ComposerFileFactory::class);
        $composerFile = $composerFileFactory->create($composerFilePath);
        $this->assertNotEmpty($composerFile->getData());
        $this->assertEquals('magento/module-customer', $composerFile->getName());
        $this->assertEquals('magento2-module', $composerFile->get('type'));
        $this->assertNotEmpty($composerFile->getRequirements());
    }
}
