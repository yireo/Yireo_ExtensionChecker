<?php
/**
 * ExtensionChecker module for Magento
 *
 * @package     Yireo_ExtensionChecker
 * @author      Jola (https://www.yireo.com/)
 * @copyright   Copyright 2018 Jola (https://www.yireo.com/)
 * @license     Open Source License
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Yireo_ExtensionChecker',
    __DIR__
);
