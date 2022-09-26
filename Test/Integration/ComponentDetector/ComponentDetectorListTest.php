<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\ComponentDetector;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorInterface;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;

class ComponentDetectorListTest extends TestCase
{
    public function testGetComponentDetectors()
    {
        $componentDetectorList = ObjectManager::getInstance()->get(ComponentDetectorList::class);
        $componentDetectors = $componentDetectorList->getComponentDetectors();
        $this->assertNotEmpty($componentDetectors);
        $this->assertContainsOnlyInstancesOf(ComponentDetectorInterface::class, $componentDetectors);
    }
}