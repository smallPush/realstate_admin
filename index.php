<?php

use App\Kernel;

/**
 * Symfony Standalone Entry Point for PaaS (Dokploy/Nixpacks)
 * 
 * This file handles cases where the root directory is used as the web root.
 */

$rootDir = __DIR__;
$autoloadFile = $rootDir . '/vendor/autoload_runtime.php';

if (!file_exists($autoloadFile)) {
    // If we're somehow in public/
    $rootDir = dirname(__DIR__);
    $autoloadFile = $rootDir . '/vendor/autoload_runtime.php';
}

if (!file_exists($autoloadFile)) {
    throw new \RuntimeException(sprintf(
        'Unable to find vendor/autoload_runtime.php at "%s". Did you run "composer install"?',
        $autoloadFile
    ));
}

require_once $autoloadFile;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
