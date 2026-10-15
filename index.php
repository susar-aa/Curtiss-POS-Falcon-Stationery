<?php
/**
 * Curtiss POS - Universal Application Router & Clean URL Engine
 * Handles routing for both Apache (.htaccess) and Nginx web servers.
 */

require_once __DIR__ . '/config/config.php';

// Parse request path
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$posDir = preg_replace('/\/[^\/]+\.php$/', '', $scriptName);
$posDir = rtrim($posDir, '/');

// Strip base directory from request URI if hosted in subdirectory
if (!empty($posDir) && str_starts_with($requestUri, $posDir)) {
    $requestUri = substr($requestUri, strlen($posDir));
}

$path = trim($requestUri, '/');

// 1. Root path redirect
if (empty($path) || $path === 'index.php') {
    if (Auth::isLoggedIn()) {
        header('Location: dashboard');
    } else {
        header('Location: login');
    }
    exit();
}

// 2. Handle /reports/{type} routes
if (preg_match('/^reports\/([a-zA-Z0-9_-]+)$/', $path, $matches)) {
    $_GET['type'] = $matches[1];
    require __DIR__ . '/reports.php';
    exit();
}

// 3. Handle direct clean URL to .php file (e.g., /login -> login.php)
$targetFile = __DIR__ . '/' . $path . '.php';
if (file_exists($targetFile)) {
    require $targetFile;
    exit();
}

// 4. Handle static files (css, js, images, fonts)
$staticFile = __DIR__ . '/' . $path;
if (file_exists($staticFile) && !is_dir($staticFile)) {
    return false;
}

// 5. 404 Fallback
http_response_code(404);
include __DIR__ . '/views/errors/404.php';
exit();
