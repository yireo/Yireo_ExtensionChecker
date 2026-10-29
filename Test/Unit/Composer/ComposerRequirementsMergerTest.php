<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Composer;

use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Composer\ComposerRequirementsMerger;

class ComposerRequirementsMergerTest extends TestCase
{
    public function testMergeRequire()
    {
        $original = [
            'foo/bar' => '^1.0',
            'foo/wildcard' => '*',
            'phpunit/phpunit' => '^9.0',
        ];

        $new = [
            'foo/bar' => '^2.0',
            'foo/wildcard' => '^3.0',
            'foo/new' => '^4.0',
        ];

        $result = (new ComposerRequirementsMerger())->mergeRequire($original, $new);
        $this->assertSame([
            'foo/bar' => '^1.0',
            'foo/new' => '^4.0',
            'foo/wildcard' => '^3.0',
        ], $result);
    }

    public function testMergeRequireKeepsPhpRequirement()
    {
        $original = [
            'php' => '>=8.1',
            'foo/undetected' => '^1.0',
        ];

        $new = [
            'foo/bar' => '^2.0',
        ];

        $result = (new ComposerRequirementsMerger())->mergeRequire($original, $new);
        $this->assertSame([
            'foo/bar' => '^2.0',
            'php' => '>=8.1',
        ], $result);
    }

    public function testMergeRequireKeepsOriginalPhpConstraint()
    {
        $original = ['php' => '>=8.1'];
        $new = ['php' => '>=8.2 <8.6'];

        $result = (new ComposerRequirementsMerger())->mergeRequire($original, $new);
        $this->assertSame(['php' => '>=8.1'], $result);
    }

    public function testMergeRequireDev()
    {
        $original = [
            'phpstan/phpstan' => '^1.0',
            'foo/bar' => '^1.0',
            'yireo/magento2-integration-test-helper' => '*',
        ];

        $new = [
            'phpunit/phpunit' => '^9.0',
            'yireo/magento2-integration-test-helper' => '^0.0.13',
            'foo/baz' => '^2.0',
        ];

        $require = [
            'foo/bar' => '^1.0',
            'foo/baz' => '^2.0',
        ];

        $result = (new ComposerRequirementsMerger())->mergeRequireDev($original, $new, $require);
        $this->assertSame([
            'phpstan/phpstan' => '^1.0',
            'phpunit/phpunit' => '^9.0',
            'yireo/magento2-integration-test-helper' => '^0.0.13',
        ], $result);
    }

    public function testMergeRequireDevSkipsPackagesInRequireCaseInsensitively()
    {
        $original = ['Foo/Bar' => '^1.0', 'phpstan/phpstan' => '^1.0'];
        $new = ['foo/baz' => '^2.0', 'phpunit/phpunit' => '^9.0'];
        $require = ['foo/bar' => '^1.0', 'Foo/Baz' => '^2.0'];

        $result = (new ComposerRequirementsMerger())->mergeRequireDev($original, $new, $require);
        $this->assertSame([
            'phpstan/phpstan' => '^1.0',
            'phpunit/phpunit' => '^9.0',
        ], $result);
    }
}
