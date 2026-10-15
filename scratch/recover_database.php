<?php
/**
 * Curtiss POS - Database Recovery Script for Local XAMPP
 */
require_once __DIR__ . '/../config/config.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbName = DB_NAME; // curtiss_pos

try {
    echo "1. Connecting to MySQL server...\n";
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "2. Re-creating database [`{$dbName}`]...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
    $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");

    echo "3. Importing database_schema.sql... ";
    $schemaPath = __DIR__ . '/../database_schema.sql';
    if (!file_exists($schemaPath)) {
        die("FATAL: database_schema.sql not found at {$schemaPath}\n");
    }

    $sql = file_get_contents($schemaPath);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec($sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "SUCCESS\n";

    echo "4. Running production migration runner...\n";
    require_once __DIR__ . '/../tests/migrate_production.php';

    echo "\n=========================================================\n";
    echo " DATABASE RECOVERY COMPLETE! [{$dbName}] IS FULLY OPERATIONAL\n";
    echo "=========================================================\n";
} catch (Throwable $e) {
    echo "ERROR RECOVERING DATABASE: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
