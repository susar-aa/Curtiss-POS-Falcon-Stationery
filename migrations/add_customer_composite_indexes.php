<?php
/**
 * Migration: Add composite indexes for Customer module optimization
 *
 * Tables:
 * - customers: idx_cust_del_stat_id (deleted_at, status, id)
 * - customer_credit_ledger: idx_ccl_cust_id (customer_id, id)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance()->getConnection();

echo "Running Customer Composite Indexes Migration...\n";

// 1. customers composite index
$existingCust = $db->query("SHOW INDEX FROM customers WHERE Key_name = 'idx_cust_del_stat_id'")->fetchAll();
if (empty($existingCust)) {
    $db->exec("ALTER TABLE `customers` ADD INDEX `idx_cust_del_stat_id` (`deleted_at`, `status`, `id`)");
    echo " -> Added idx_cust_del_stat_id on customers\n";
} else {
    echo " -> idx_cust_del_stat_id already exists on customers\n";
}

// 2. customer_credit_ledger composite index
$existingCcl = $db->query("SHOW INDEX FROM customer_credit_ledger WHERE Key_name = 'idx_ccl_cust_id'")->fetchAll();
if (empty($existingCcl)) {
    $db->exec("ALTER TABLE `customer_credit_ledger` ADD INDEX `idx_ccl_cust_id` (`customer_id`, `id`)");
    echo " -> Added idx_ccl_cust_id on customer_credit_ledger\n";
} else {
    echo " -> idx_ccl_cust_id already exists on customer_credit_ledger\n";
}

echo "Customer Composite Indexes Migration Completed.\n";
