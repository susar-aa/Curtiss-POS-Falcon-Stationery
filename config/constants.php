<?php
/**
 * Curtiss POS - System Constants
 */

define('APP_NAME', 'Curtiss POS');
define('APP_VERSION', '2.0.0-Stage2');
define('APP_ROOT', dirname(__DIR__));

// Database Configuration (Dynamic Environment Auto-Switching)
$isProductionVPS = isset($_SERVER['HTTP_HOST']) && (
    str_contains($_SERVER['HTTP_HOST'], 'trycurtiss.com') ||
    str_contains($_SERVER['HTTP_HOST'], 'kandykids')
);

if ($isProductionVPS) {
    // VPS Server Database Credentials
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'kandy_kids');
    define('DB_USER', 'curtiss_kandykids');
    define('DB_PASS', 'Susara@200611003614');
} else {
    // Local XAMPP Environment Credentials
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'curtiss_pos');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// Session & Security Settings
define('SESSION_NAME', 'CURTISS_POS_SESS');
define('DEFAULT_INACTIVITY_TIMEOUT', 3600); // 1 hour (3600 seconds)

// Role Codes
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_CASHIER', 'cashier');
define('ROLE_ACCOUNTANT', 'accountant');
define('ROLE_SPECIAL_USER', 'special_user');

// Logging
define('LOG_PATH', APP_ROOT . '/storage/logs/app.log');
