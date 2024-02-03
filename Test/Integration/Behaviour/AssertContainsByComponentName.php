<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Integration\Behaviour;

use Yireo\ExtensionChecker\Component\Component;

trait AssertContainsByComponentName
{
    /**
     * @param string $componentName
     * @param Component[] $components
     * @param string $message
     * @return void
     */
    public function assertContainsByComponentName(string $componentName, array $components = [], string $message = '')
    {
        $componentFound = false;
        foreach ($components as $component) {
            if ($component->getComponentName() === $componentName) {
                $componentFound = true;
                break;
            }
        }

        $this->assertTrue($componentFound, $message);
    }
}
