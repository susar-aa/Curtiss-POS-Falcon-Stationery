<?php
/**
 * Curtiss POS - Production Schema Migration Runner (Stage 20)
 * Safely executes ordered database migrations on a fresh or target production database.
 * Idempotent, non-destructive, with table & column existence checks.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "=========================================================\n";
echo "   CURTISS POS - PRODUCTION SCHEMA MIGRATION RUNNER      \n";
echo "=========================================================\n\n";

$migrations = [
    'Stage 5'  => 'migrate_stage5.php',
    'Stage 6'  => 'migrate_stage6.php',
    'Stage 7'  => 'migrate_stage7.php',
    'Stage 8'  => 'migrate_stage8.php',
    'Stage 9'  => 'migrate_stage9.php',
    'Stage 10' => 'migrate_stage10.php',
    'Stage 11' => 'migrate_stage11.php',
    'Stage 12' => 'migrate_stage12.php',
    'Stage 13' => 'migrate_stage13.php',
    'Stage 14' => 'migrate_stage14.php',
    'Stage 15' => 'migrate_stage15.php',
    'Stage 16' => 'migrate_stage16.php',
    'Stage 17' => 'migrate_stage17.php',
    'Stage 18' => 'migrate_stage18.php',
    'Stage 19' => 'migrate_stage19.php',
];

$phpPath = PHP_BINARY ? PHP_BINARY : 'C:\\xampp\\php\\php.exe';
$testsDir = __DIR__;

$passedCount = 0;
$failedCount = 0;

foreach ($migrations as $stageName => $fileName) {
    $filePath = $testsDir . DIRECTORY_SEPARATOR . $fileName;
    echo "Running migration {$stageName} [{$fileName}]... ";

    if (!file_exists($filePath)) {
        echo "SKIPPED (File not found)\n";
        continue;
    }

    $cmd = "\"{$phpPath}\" \"{$filePath}\"";
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    if ($returnCode === 0) {
        echo "SUCCESS\n";
        $passedCount++;
    } else {
        echo "FAILED (Exit Code {$returnCode})\n";
        echo "  Output: " . implode("\n  ", $output) . "\n";
        $failedCount++;
    }
}

echo "\n=========================================================\n";
echo " MIGRATION SUMMARY: {$passedCount} Succeeded, {$failedCount} Failed\n";
echo "=========================================================\n";

if ($failedCount > 0) {
    echo "PRODUCTION MIGRATION STATUS: FAILED\n";
    exit(1);
} else {
    echo "PRODUCTION MIGRATION STATUS: SUCCESSFUL\n";
    exit(0);
}
