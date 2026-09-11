<?php
/**
 * Front Controller & Router Entry Point — index.php
 * Library Attendance Monitoring System (LAMS)
 */

// If running via PHP's built-in web server, serve static files directly
if (php_sapi_name() === 'cli-server') {
    $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/includes/core/Router.php';

Router::dispatch();
