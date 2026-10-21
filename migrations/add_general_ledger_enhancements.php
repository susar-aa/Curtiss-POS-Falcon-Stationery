<?php
/**
 * Migration: General Ledger Optimization & Performance Indexes
 * Adds composite and covering indexes for fast filtering, sorting, and reporting.
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting General Ledger migrations...\n";

    // Indexes for journal_entries
    $jeIndexes = [];
    $stmt = $db->query("SHOW INDEX FROM journal_entries");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $jeIndexes[$row['Key_name']] = true;
    }

    if (!isset($jeIndexes['idx_je_date_id'])) {
        $db->exec("ALTER TABLE journal_entries ADD INDEX idx_je_date_id (entry_date, id)");
        echo "Added index idx_je_date_id on journal_entries\n";
    } else {
        echo "Index idx_je_date_id already exists on journal_entries\n";
    }

    if (!isset($jeIndexes['idx_je_stat_date_id'])) {
        $db->exec("ALTER TABLE journal_entries ADD INDEX idx_je_stat_date_id (posting_status, entry_date, id)");
        echo "Added index idx_je_stat_date_id on journal_entries\n";
    } else {
        echo "Index idx_je_stat_date_id already exists on journal_entries\n";
    }

    // Indexes for journal_lines
    $jlIndexes = [];
    $stmt = $db->query("SHOW INDEX FROM journal_lines");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $jlIndexes[$row['Key_name']] = true;
    }

    if (!isset($jlIndexes['idx_jl_acc_debit_credit'])) {
        $db->exec("ALTER TABLE journal_lines ADD INDEX idx_jl_acc_debit_credit (account_id, debit, credit)");
        echo "Added index idx_jl_acc_debit_credit on journal_lines\n";
    } else {
        echo "Index idx_jl_acc_debit_credit already exists on journal_lines\n";
    }

    echo "General Ledger migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
