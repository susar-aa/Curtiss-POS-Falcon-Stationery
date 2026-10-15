<?php
require_once __DIR__ . '/../config/config.php';

try {
    $rows = Database::fetchAll("SELECT * FROM error_logs ORDER BY id DESC LIMIT 5");
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo "DB Error: " . $e->getMessage();
}
