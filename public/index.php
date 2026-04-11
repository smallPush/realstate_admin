<?php

use App\Kernel;

if (file_exists(dirname(__DIR__).'/vendor/autoload_runtime.php')) {
    require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
} else {
    require_once __DIR__.'/../vendor/autoload_runtime.php';
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
