<?php
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $cols = $db->query("DESCRIBE grns")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('paid_amount', $cols)) {
        $db->exec("ALTER TABLE grns ADD COLUMN paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER actual_grn_total");
        echo "Added paid_amount column to grns.\n";
    }

    if (!in_array('payment_status', $cols)) {
        $db->exec("ALTER TABLE grns ADD COLUMN payment_status ENUM('Unpaid', 'Partially Paid', 'Paid') NOT NULL DEFAULT 'Unpaid' AFTER paid_amount");
        echo "Added payment_status column to grns.\n";
    }

    echo "GRN table schema alteration complete!\n";
} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
