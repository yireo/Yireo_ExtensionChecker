<?php declare(strict_types=1);

use Magento\Framework\Console\CommandLocator;
use Yireo\ExtensionChecker\Console\CommandList;

/**
 * This file is used to make some CLI commands available without Magento installation (deployment)
 */
if (PHP_SAPI === 'cli') {
    CommandLocator::register(CommandList::class);
}
