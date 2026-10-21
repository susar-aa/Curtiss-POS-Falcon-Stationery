<?php
/**
 * Migration: Report Engine Optimization & Performance Indexes
 * Adds composite and covering indexes for fast report aggregation, date filtering, and branch analytics.
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Report Engine migrations...\n";

    // Indexes for sales
    $salesIndexes = [];
    $stmt = $db->query("SHOW INDEX FROM sales");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $salesIndexes[$row['Key_name']] = true;
    }

    if (!isset($salesIndexes['idx_sales_br_stat_date'])) {
        $db->exec("ALTER TABLE sales ADD INDEX idx_sales_br_stat_date (branch_id, status, created_at)");
        echo "Added index idx_sales_br_stat_date on sales\n";
    } else {
        echo "Index idx_sales_br_stat_date already exists on sales\n";
    }

    // Indexes for grns
    $grnIndexes = [];
    $stmt = $db->query("SHOW INDEX FROM grns");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $grnIndexes[$row['Key_name']] = true;
    }

    if (!isset($grnIndexes['idx_grn_br_stat_recdate'])) {
        $db->exec("ALTER TABLE grns ADD INDEX idx_grn_br_stat_recdate (branch_id, status, received_date)");
        echo "Added index idx_grn_br_stat_recdate on grns\n";
    } else {
        echo "Index idx_grn_br_stat_recdate already exists on grns\n";
    }

    if (!isset($grnIndexes['idx_grn_recdate'])) {
        $db->exec("ALTER TABLE grns ADD INDEX idx_grn_recdate (received_date)");
        echo "Added index idx_grn_recdate on grns\n";
    } else {
        echo "Index idx_grn_recdate already exists on grns\n";
    }

    // Indexes for payments
    $payIndexes = [];
    $stmt = $db->query("SHOW INDEX FROM payments");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payIndexes[$row['Key_name']] = true;
    }

    if (!isset($payIndexes['idx_pmt_br_method'])) {
        $db->exec("ALTER TABLE payments ADD INDEX idx_pmt_br_method (branch_id, payment_method)");
        echo "Added index idx_pmt_br_method on payments\n";
    } else {
        echo "Index idx_pmt_br_method already exists on payments\n";
    }

    echo "Report Engine migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
