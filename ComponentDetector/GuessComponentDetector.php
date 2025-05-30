<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\ComponentDetector;

use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Component\ComponentFactory;
use Yireo\ExtensionChecker\Exception\ModuleNotFoundException;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Util\ModuleInfo;

/**
 * Detect components from a module its JS files
 */
class GuessComponentDetector implements ComponentDetectorInterface
{
    private ComponentFactory $componentFactory;
    private ModuleInfo $moduleInfo;
    private MessageBucket $messageBucket;

    public function __construct(
        ComponentFactory $componentFactory,
        ModuleInfo $moduleInfo,
        MessageBucket $messageBucket
    ) {
        $this->componentFactory = $componentFactory;
        $this->moduleInfo = $moduleInfo;
        $this->messageBucket = $messageBucket;
    }

    /**
     * @param string $moduleName
     * @return Component
     */
    public function getComponentsByModuleName(string $moduleName): array
    {
        $components = [];
        $components[] = $this->componentFactory->createByLibraryName('magento/framework', null, true);

        try {
            $moduleFolder = $this->moduleInfo->getModuleFolder($moduleName);
        } catch (ModuleNotFoundException $moduleNotFoundException) {
            $message = 'ModuleNotFoundException for module "' . $moduleName . '": ' . $moduleNotFoundException->getMessage();
            $this->messageBucket->add($message, MessageGroupLabels::GROUP_EXCEPTION);
            return $components;
        }

        // @todo: Refactor is_dir and is_file to use Magento Filesystem instead
        // @todo: Move this into a DI-based configuration
        if (is_dir($moduleFolder . '/Setup') || is_dir($moduleFolder . '/Block')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Store', true);
        }

        if (is_file($moduleFolder . '/etc/db_schema.xml')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Store', true);
        }

        if (is_file($moduleFolder . '/etc/schema.graphqls')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_GraphQl', false);
        }

        if (is_dir($moduleFolder . '/etc/graphql')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_GraphQl', false);
        }

        if (is_dir($moduleFolder . '/etc/frontend')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Store', false);
        }

        if (is_dir($moduleFolder . '/etc/adminhtml')) {
            $components[] = $this->componentFactory->createByModuleName('Magento_Backend', false);
        }

        return $components;
    }
}
