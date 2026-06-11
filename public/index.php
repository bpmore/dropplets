<?php

/**
 * Public entry point. The only PHP file in the web root that runs the app.
 * All application code, config, and data live one level up, outside public/.
 */

// Under the PHP built-in server (the documented quick-run path), let real
// files in public/ — uploads, static assets — be served directly instead of
// falling into the router and 404ing.
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($requested !== __DIR__ . '/index.php' && is_file($requested)) {
        return false;
    }
}

require dirname(__DIR__) . '/src/routes.php';
