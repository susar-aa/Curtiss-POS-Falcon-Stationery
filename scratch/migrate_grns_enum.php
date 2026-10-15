<?php
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->exec("ALTER TABLE grns MODIFY COLUMN payment_status ENUM('unpaid', 'partially_paid', 'paid') NOT NULL DEFAULT 'unpaid'");
    echo "Updated payment_status enum in grns table!\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
