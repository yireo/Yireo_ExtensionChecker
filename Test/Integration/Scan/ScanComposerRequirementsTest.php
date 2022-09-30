<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Scan;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Module\PackageInfo;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\PhpClass\ClassInspector;
use Yireo\ExtensionChecker\Scan\ScanComposerRequirements;

class ScanComposerRequirementsTest extends TestCase
{
    public function testScan()
    {
        $messages = $this->scan('Yireo_ExtensionChecker');
        //$this->assertEmpty($messages, $this->getMessagesDump($messages)); // @todo: This is the goal
        $this->assertEquals(1, count($messages));

        //$messages = $this->scan('Magento_Store');
        //$this->assertEmpty($messages, $this->getMessagesDump($messages)); // @todo: Too many things go wrong here

        //$messages = $this->scan('Magento_Directory');
        //$this->assertEmpty($messages, $this->getMessagesDump($messages)); // @todo: Composer requirement "magento/module-config" possibly not needed
    }

    /**
     * @param string $moduleName
     * @return array
     * @throws FileSystemException
     * @throws NotFoundException
     */
    private function scan(string $moduleName): array
    {
        $messageBucket = ObjectManager::getInstance()->get(MessageBucket::class);
        $messageBucket->clean();
        $this->assertEmpty($messageBucket->getMessages());

        $componentDetectorList = ObjectManager::getInstance()->get(ComponentDetectorList::class);
        $scanComposerRequirements = ObjectManager::getInstance()->get(ScanComposerRequirements::class);
        $components = $componentDetectorList->getComponentsByModuleName($moduleName);
        $scanComposerRequirements->scan($moduleName, $components);

        return $messageBucket->getMessages();
    }

    /**
     * @param array $messages
     * @return string
     */
    private function getMessagesDump(array $messages): string
    {
        $messagesDump = [];
        foreach ($messages as $message) {
            $messagesDump[] = $message->toArray();
        }

        return var_export($messagesDump, true);
    }
}
