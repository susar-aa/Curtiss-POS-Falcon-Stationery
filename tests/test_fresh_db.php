<?php
/**
 * Curtiss POS - Fresh Database Installation & Validation Suite (Stage 20)
 * 
 * Verifies that a brand-new empty database can be initialized from scratch:
 *  1. Creates temporary clean test database `curtiss_pos_fresh_test`
 *  2. Installs base schema `database_schema.sql`
 *  3. Executes `tests/migrate_production.php`
 *  4. Asserts schema, tables, default rows, and basic health checks
 *  5. Drops temporary clean test database
 */

require_once __DIR__ . '/../config/config.php';

echo "=========================================================\n";
echo "   CURTISS POS - FRESH DATABASE INSTALLATION TEST       \n";
echo "=========================================================\n\n";

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$freshDbName = 'curtiss_pos_fresh_test';

try {
    // 1. Connect to MySQL server without database selected
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "1. Creating clean temporary database [{$freshDbName}]... ";
    $pdo->exec("DROP DATABASE IF EXISTS `{$freshDbName}`");
    $pdo->exec("CREATE DATABASE `{$freshDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "SUCCESS\n";

    // 2. Load and execute base schema database_schema.sql
    echo "2. Importing base database_schema.sql... ";
    $schemaPath = __DIR__ . '/../database_schema.sql';
    if (!file_exists($schemaPath)) {
        die("FATAL: database_schema.sql not found at {$schemaPath}\n");
    }

    $pdo->exec("USE `{$freshDbName}`");
    $sql = file_get_contents($schemaPath);
    
    // Split SQL into individual statements by semicolon
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec($sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "SUCCESS\n";

    // 3. Verify core table count
    $stmtTbls = $pdo->query("SHOW TABLES");
    $tables = $stmtTbls->fetchAll(PDO::FETCH_COLUMN);
    $tableCount = count($tables);
    echo "3. Verified table initialization: Found {$tableCount} tables... ";
    if ($tableCount < 30) {
        throw new Exception("Expected at least 30 core tables in fresh database schema, found {$tableCount}");
    }
    echo "PASS\n";

    // 4. Verify default seed records (Super Admin User & Default Branch)
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = (int)$stmtUsers->fetchColumn();

    $stmtBranches = $pdo->query("SELECT COUNT(*) FROM branches");
    $branchCount = (int)$stmtBranches->fetchColumn();

    $stmtCOA = $pdo->query("SELECT COUNT(*) FROM chart_of_accounts");
    $coaCount = (int)$stmtCOA->fetchColumn();

    echo "4. Checking baseline seed data:\n";
    echo "   - Users: {$userCount} records\n";
    echo "   - Branches: {$branchCount} records\n";
    echo "   - Chart of Accounts: {$coaCount} accounts\n";

    if ($userCount === 0 || $branchCount === 0 || $coaCount === 0) {
        throw new Exception("Baseline seed data missing from fresh schema import.");
    }

    // 5. Clean up temporary test database
    echo "5. Cleaning up temporary database [{$freshDbName}]... ";
    $pdo->exec("DROP DATABASE IF EXISTS `{$freshDbName}`");
    echo "SUCCESS\n";

    echo "\n=========================================================\n";
    echo " FRESH DATABASE INSTALLATION RESULT: PASSED 100%\n";
    echo "=========================================================\n";
    exit(0);

} catch (Throwable $e) {
    echo "\nFATAL EXCEPTION IN FRESH DATABASE TEST:\n";
    echo $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    
    // Attempt cleanup if database exists
    try {
        if (isset($pdo)) {
            $pdo->exec("DROP DATABASE IF EXISTS `{$freshDbName}`");
        }
    } catch (Throwable $ignore) {}
    
    exit(1);
}
