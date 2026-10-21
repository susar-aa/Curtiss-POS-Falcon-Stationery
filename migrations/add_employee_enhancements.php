<?php
/**
 * Migration: Employee Directory Optimization & Performance Indexes
 * Adds composite and covering indexes for fast directory listing, branch isolation, search, and status filtering.
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Employee Directory migrations...\n";

    $indexes = [];
    $stmt = $db->query("SHOW INDEX FROM employees");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $indexes[$row['Key_name']] = true;
    }

    if (!isset($indexes['idx_emp_del_branch_id'])) {
        $db->exec("ALTER TABLE employees ADD INDEX idx_emp_del_branch_id (deleted_at, branch_id, id)");
        echo "Added index idx_emp_del_branch_id on employees\n";
    } else {
        echo "Index idx_emp_del_branch_id already exists on employees\n";
    }

    if (!isset($indexes['idx_emp_del_stat_id'])) {
        $db->exec("ALTER TABLE employees ADD INDEX idx_emp_del_stat_id (deleted_at, status, id)");
        echo "Added index idx_emp_del_stat_id on employees\n";
    } else {
        echo "Index idx_emp_del_stat_id already exists on employees\n";
    }

    if (!isset($indexes['idx_emp_name'])) {
        $db->exec("ALTER TABLE employees ADD INDEX idx_emp_name (full_name)");
        echo "Added index idx_emp_name on employees\n";
    } else {
        echo "Index idx_emp_name already exists on employees\n";
    }

    if (!isset($indexes['idx_emp_phone'])) {
        $db->exec("ALTER TABLE employees ADD INDEX idx_emp_phone (phone)");
        echo "Added index idx_emp_phone on employees\n";
    } else {
        echo "Index idx_emp_phone already exists on employees\n";
    }

    if (!isset($indexes['idx_emp_nic'])) {
        $db->exec("ALTER TABLE employees ADD INDEX idx_emp_nic (nic)");
        echo "Added index idx_emp_nic on employees\n";
    } else {
        echo "Index idx_emp_nic already exists on employees\n";
    }

    echo "Employee Directory migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
