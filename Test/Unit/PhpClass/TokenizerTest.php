<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\PhpClass;

use Magento\Framework\Filesystem\File\ReadFactory;
use PHPUnit\Framework\Constraint\TraversableContainsIdentical;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\PhpClass\Tokenizer;

class TokenizerTest extends TestCase
{
    public function testGetImportedClassnamesFromSource()
    {
        $readFactory = $this->getMockBuilder(ReadFactory::class)->disableOriginalConstructor()->getMock();
        $tokenizer = new Tokenizer($readFactory);

        $source = <<<EOF
<?php
use Foo\Bar;
use Foo2\Bar as Foo2Bar;
use Foo3\Bar as Foo3Bar, Foo4\Bar as Foo4Bar;

\$bar = new Bar;
\$bar2 = new Foo2Bar;
\$bar3 = new Foo3Bar;
\$bar4 = new Foo4Bar;
EOF;

        $importedClassnames = $tokenizer->getImportedClassnamesFromSource($source);
        $this->assertContains('Foo\Bar', $importedClassnames);
        $this->assertContains('Foo2\Bar', $importedClassnames);
        $this->assertContains('Foo3\Bar', $importedClassnames);
        $this->assertContains('Foo4\Bar', $importedClassnames);
    }
}
