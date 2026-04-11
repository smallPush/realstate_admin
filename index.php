<?php

/**
 * Symfony Root Entry Point Bridge
 */

// If we are in the root directory, public/index.php is the real one.
$entryPoint = realpath(__DIR__ . '/public/index.php');

if ($entryPoint) {
    require_once $entryPoint;
} else {
    // This part should technically never be reachable if this file is in the root,
    // but it provides a fallback just in case.
    die('Unable to locate the Symfony entry point (public/index.php).');
}
