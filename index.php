<?php

/**
 * Symfony Root Entry Point Bridge
 */

// If we are in the root directory, public/index.php is the real one.
if (file_exists(__DIR__.'/public/index.php')) {
    require_once __DIR__.'/public/index.php';
} else {
    // This part should technically never be reachable if this file is in the root,
    // but it provides a fallback just in case.
    die('Unable to locate the Symfony entry point.');
}
