<?php declare(strict_types=1);
/**
 * Yireo ExtensionChecker for Magento
 *
 * @package     Yireo_ExtensionChecker
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2018 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\ExtensionChecker\Console\Command;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\WriteFactory;
use Magento\Framework\ObjectManagerInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Input\InputOption;

class GenerateUnitTestCommand extends Command
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;
    /**
     * @var WriteFactory
     */
    private $writeFactory;

    /**
     * GenerateUnitTestCommand constructor.
     * @param ObjectManagerInterface $objectManager
     * @param ComponentRegistrar $componentRegistrar
     * @param WriteFactory $writeFactory
     * @param string|null $name
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ComponentRegistrar $componentRegistrar,
        WriteFactory $writeFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->objectManager = $objectManager;
        $this->componentRegistrar = $componentRegistrar;
        $this->writeFactory = $writeFactory;
    }

    protected function configure()
    {
        $this->setName('yireo_extensionchecker:generate-unit-test');
        $this->setDescription('Generate a PHPUnit unit test');

        $this->addOption(
            'module',
            null,
            InputOption::VALUE_REQUIRED,
            'Module name');

        $this->addOption(
            'class',
            null,
            InputOption::VALUE_REQUIRED,
            'Full qualified class name');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|void
     */
    protected function execute(Input $input, Output $output)
    {
        $moduleName = $input->getOption('module');
        $modulePath = $this->componentRegistrar->getPath('module', $moduleName);

        $className = $input->getOption('class');
        if (!$targetObject = $this->objectManager->get($className)) {
            $output->writeln('<error>Class "' . $className . '" does not exist</error>');
        }

        $phpUnitTestFile = $this->getPhpUnitTestFile($modulePath, $targetObject);
        if (file_exists($phpUnitTestFile)) {
            $output->writeln('Unit test already exists');
            return;
        }

        $phpUnitTestCode = $this->getPhpUnitTestCode($targetObject);
        $fileWrite = $this->writeFactory->create($phpUnitTestFile, DriverPool::FILE, 'w');
        $fileWrite->write($phpUnitTestCode);

        $output->writeln('Unit test generated: ' . $phpUnitTestFile);
    }

    /**
     * @param string $modulePath
     * @param object $targetObject
     * @return string
     * @throws ReflectionException
     */
    private function getPhpUnitTestFile(string $modulePath, object $targetObject): string
    {
        $reflectionObject = new ReflectionClass($targetObject);
        $namespaceName = $reflectionObject->getNamespaceName();
        $parts = explode('\\', $namespaceName);
        array_shift($parts);
        array_shift($parts);

        return $modulePath . '/Test/Unit/' . implode('//',
                $parts) . '/' . $reflectionObject->getShortName() . 'Test.php';
    }

    /**
     * @param object $targetObject
     * @return string
     */
    private function getPhpUnitTestCode(object $targetObject): string
    {
        $phpCode = '';
        $reflectionObject = new ReflectionClass($targetObject);
        $targetClass = get_class($targetObject);
        $targetMethods = get_class_methods($targetObject);

        $namespace = $this->getTestNameSpace($targetObject);
        $className = $reflectionObject->getShortName() . 'Test';

        $class = new ClassType($className);
        $class->setExtends(TestCase::class);

        foreach ($targetMethods as $targetMethod) {
            if ($targetMethod === '__construct') {
                continue;
            }

            $targetObjectName = $reflectionObject->getShortName();
            $targetObjectVar = '$'.lcfirst($targetObjectName);

            $bodyLines = [];
            $bodyLines[] = $targetObjectVar . ' = new ' . $targetObjectName . '();';
            $bodyLines[] = $targetObjectVar . '->'.$targetMethod.'();';


            $class->addMethod('test' . ucfirst($targetMethod))
                ->addComment('Test for ' . $targetClass . '::' . $targetMethod)
                ->setBody(implode("\n", $bodyLines));
        }

        $namespace->addUse(TestCase::class);
        $namespace->addUse($targetClass);
        $namespace->add($class);

        $phpCode .= "<?php declare(strict_types=1);\n\n";
        $phpCode .= $namespace;

        return $phpCode;
    }

    /**
     * @param object $targetObject
     * @return PhpNamespace
     * @throws ReflectionException
     */
    private function getTestNameSpace(object $targetObject): PhpNamespace
    {
        $reflectionObject = new ReflectionClass($targetObject);
        $namespaceName = $reflectionObject->getNamespaceName();
        $parts = explode('\\', $namespaceName);
        $vendor = array_shift($parts);
        $module = array_shift($parts);
        $namespaceName = implode('\\', array_merge([$vendor, $module, 'Test'], $parts));
        return new PhpNamespace($namespaceName);
    }
}
