<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Component;

use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;

class ComponentTest extends TestCase
{
    public function testToArray()
    {
        $component = new Component('Foo_Bar', 'module', 'foo/bar', '0.0.1');
        $data = $component->toArray();
        $this->assertEquals('Foo_Bar', $data['component-name']);
        $this->assertEquals('module', $data['component-type']);
        $this->assertEquals('foo/bar', $data['package-name']);
        $this->assertEquals('0.0.1', $data['package-version']);
        $this->assertFalse($data['dev-requirement']);
    }

    public function testWithDevRequirement()
    {
        $component = new Component('Foo_Bar', 'module', 'foo/bar', '0.0.1', true);
        $devComponent = $component->withDevRequirement();

        $this->assertNotSame($component, $devComponent);
        $this->assertFalse($component->isDevRequirement());
        $this->assertTrue($devComponent->isDevRequirement());
        $this->assertTrue($devComponent->isHardRequirement());
        $this->assertEquals('foo/bar', $devComponent->getPackageName());
    }
}
