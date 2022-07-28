<?php
/**
 * This file is used to make some CLI commands available without Magento installation (deployment)
 */
if (PHP_SAPI === 'cli') {
    \Magento\Framework\Console\CommandLocator::register(\Yireo\ExtensionChecker\Console\CommandList::class);
}
