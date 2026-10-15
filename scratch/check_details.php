<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    print_r(Database::fetchAll("DESCRIBE supplier_credits"));
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
