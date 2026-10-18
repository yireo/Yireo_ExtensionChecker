<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration;

use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsEnabled;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegistered;

class ModuleTest extends TestCase
{
    use AssertModuleIsEnabled;
    use AssertModuleIsRegistered;
    
    public function testModule()
    {
        $this->assertModuleIsEnabled('Yireo_ExtensionChecker');
        $this->assertModuleIsRegistered('Yireo_ExtensionChecker');
    }
}
