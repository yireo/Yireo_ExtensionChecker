<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\PhpClass\ClassInspector;
use Yireo\ExtensionChecker\PhpClass\ModuleCollector;

class ScanDeprecatedClasses
{
    private MessageBucket $messageBucket;
    private ClassInspector $classInspector;
    private ModuleCollector $moduleCollector;
    
    public function __construct(
        MessageBucket $messageBucket,
        ClassInspector $classInspector,
        ModuleCollector $moduleCollector
    )  {
        $this->messageBucket = $messageBucket;
        $this->classInspector = $classInspector;
        $this->moduleCollector = $moduleCollector;
    }
    
    public function scan(string $moduleName)
    {
        $classNames = $this->moduleCollector->getClassNamesFromModule($moduleName);
        foreach ($classNames as $className) {
            $this->classInspector->setClassName($className);
            if ($this->classInspector->isDeprecated()) {
                $msg = sprintf('Use of deprecated dependency "%s"', $className);
                $this->messageBucket->addWarning($msg);
            }
        }
    }
}
