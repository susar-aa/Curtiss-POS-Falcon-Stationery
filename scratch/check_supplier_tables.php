<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $tables = Database::fetchAll("SHOW TABLES LIKE '%supplier%'");
    print_r($tables);
    $journals = Database::fetchAll("SHOW TABLES LIKE '%journal%'");
    print_r($journals);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
