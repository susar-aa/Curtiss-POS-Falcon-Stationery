<?php
/**
 * Global Configuration & Initialization Script
 */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../core/Logger.php';

// Application Default Timezone (Asia/Colombo)
date_default_timezone_set('Asia/Colombo');

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_name(SESSION_NAME);
    session_start();
}

// Global Custom Error Handler (Rule 3)
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    Logger::error("PHP Error: " . $message, null, [
        'severity' => $severity,
        'file'     => $file,
        'line'     => $line
    ]);
    return true;
});

// Global Exception Handler (Rule 3)
set_exception_handler(function (Throwable $exception) {
    $GLOBALS['last_exception'] = $exception;
    Logger::exception($exception);
    
    if (php_sapi_name() === 'cli') {
        echo "UNHANDLED EXCEPTION: " . $exception->getMessage() . "\n";
        exit(1);
    }
    
    http_response_code(500);
    include APP_ROOT . '/views/errors/500.php';
    exit();
});

// Autoload Core Classes
spl_autoload_register(function ($class) {
    $file = APP_ROOT . '/core/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
