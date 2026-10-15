<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $cols = Database::fetchAll("DESCRIBE bank_accounts");
    print_r($cols);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
