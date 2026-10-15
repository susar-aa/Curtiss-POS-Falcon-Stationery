<?php
/**
 * Falcon POS - Database Connection Diagnostic Tool
 * Run this on your server to verify VPS database connection and tables.
 */
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config/config.php';

echo "=== FALCON POS DATABASE DIAGNOSTIC ===\n\n";
echo "Host Header: " . ($_SERVER['HTTP_HOST'] ?? 'CLI') . "\n";
echo "Detected VPS Mode: " . ($isProductionVPS ? 'YES (Production)' : 'NO (Localhost)') . "\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n\n";

try {
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "✓ [PASS] Database connection succeeded!\n";

    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ [PASS] Found " . count($tables) . " tables in database '" . DB_NAME . "'\n";

    if (!in_array('users', $tables)) {
        echo "✗ [FAIL] Table 'users' does not exist! You must import database/falcon_pos_backup.sql\n";
    } else {
        echo "✓ [PASS] Table 'users' exists.\n";
        
        // Check super admin user
        $stmt = $pdo->prepare("SELECT id, username, email, role_id, status FROM users WHERE username = 'susar.aa'");
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user) {
            echo "✓ [PASS] User 'susar.aa' exists (ID: {$user['id']}, Role: {$user['role_id']}, Status: {$user['status']})\n";
        } else {
            echo "✗ [FAIL] User 'susar.aa' does not exist in 'users' table!\n";
        }
    }
} catch (Throwable $e) {
    echo "✗ [FAIL] Database Error: " . $e->getMessage() . "\n";
}
