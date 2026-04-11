<?php

use App\Kernel;

$possiblePaths = [
    __DIR__ . '/../vendor/autoload_runtime.php',
    __DIR__ . '/vendor/autoload_runtime.php',
    getcwd() . '/vendor/autoload_runtime.php',
    '/app/vendor/autoload_runtime.php',
];

$autoloadPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $autoloadPath = realpath($path);
        break;
    }
}

if (!$autoloadPath) {
    throw new \RuntimeException(sprintf(
        'Unable to find vendor/autoload_runtime.php. Tried: %s. Current DIR: %s. GetCWD: %s',
        implode(', ', $possiblePaths),
        __DIR__,
        getcwd()
    ));
}

require_once $autoloadPath;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
