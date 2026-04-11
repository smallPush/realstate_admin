<?php

use App\Kernel;

// Robustly find the vendor/autoload_runtime.php
$autoloadPath = realpath(__DIR__ . '/../vendor/autoload_runtime.php');

if (!$autoloadPath && file_exists(__DIR__ . '/vendor/autoload_runtime.php')) {
    $autoloadPath = realpath(__DIR__ . '/vendor/autoload_runtime.php');
}

if (!$autoloadPath) {
    // Last resort: check from root if included from elsewhere
    $autoloadPath = realpath(getcwd() . '/vendor/autoload_runtime.php');
}

if (!$autoloadPath) {
    throw new \RuntimeException('Unable to find vendor/autoload_runtime.php. Did you run "composer install"?');
}

require_once $autoloadPath;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
