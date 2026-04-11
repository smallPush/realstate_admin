<?php

/**
 * Symfony Root Entry Point Bridge
 * 
 * This file is here to help PaaS environments like Dokploy/Nixpacks 
 * find the entry point if they aren't correctly configured to use 
 * the 'public/' directory as the web root.
 */

require_once __DIR__.'/public/index.php';
