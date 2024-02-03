<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration;

use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsEnabled;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegistered;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegisteredForReal;

class ModuleTest extends TestCase
{
    use AssertModuleIsEnabled;
    use AssertModuleIsRegistered;
    use AssertModuleIsRegisteredForReal;
    
    public function testModule()
    {
        $this->assertModuleIsEnabled('Yireo_ExtensionChecker');
        $this->assertModuleIsRegistered('Yireo_ExtensionChecker');
        $this->assertModuleIsRegisteredForReal('Yireo_ExtensionChecker');
    }
}
