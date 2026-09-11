<?php
$_SERVER['REQUEST_URI'] = '/api/auth/login';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

require_once __DIR__ . '/../includes/core/Router.php';

// Let's test render logic
$reflection = new ReflectionClass('Router');
$prop = $reflection->getProperty('routes');
$prop->setAccessible(true);
$routes = $prop->getValue();

echo "Route match for /api/auth/login: " . ($routes['/api/auth/login'] ?? 'NONE') . "\n";
