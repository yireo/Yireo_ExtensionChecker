<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Diff;

use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Diff\DiffGenerator;

class DiffGeneratorTest extends TestCase
{
    public function testDiffReturnsEmptyStringWhenIdentical()
    {
        $contents = "foo\nbar\n";
        $this->assertSame('', (new DiffGenerator())->diff($contents, $contents));
    }

    public function testDiffWithChangedLines()
    {
        $diff = (new DiffGenerator())->diff(
            "foo\nbar\nbaz\n",
            "foo\nqux\nbaz\nnew\n",
            'old.txt',
            'new.txt'
        );

        $expected = <<<EOF
--- old.txt
+++ new.txt
@@ -1,3 +1,4 @@
 foo
-bar
+qux
 baz
+new

EOF;
        $this->assertSame($expected, $diff);
    }

    public function testDiffWithRemovedLines()
    {
        $diff = (new DiffGenerator())->diff("foo\nbar\n", "foo\n", 'a', 'b');
        $this->assertSame("--- a\n+++ b\n@@ -1,2 +1 @@\n foo\n-bar\n", $diff);
    }

    public function testDiffWithOnlyTrailingNewlineChanged()
    {
        $diff = (new DiffGenerator())->diff("foo", "foo\n", 'a', 'b');
        $this->assertNotEmpty($diff);
        $this->assertStringContainsString('No newline at end of file', $diff);
    }

    public function testDiffFromEmptyOriginal()
    {
        $diff = (new DiffGenerator())->diff('', "foo\n", 'a', 'b');
        $this->assertStringContainsString("+foo\n", $diff);
    }
}
