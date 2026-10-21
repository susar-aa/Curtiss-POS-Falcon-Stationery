<?php
/**
 * Migration: Journal Entries Composite Indexes & Performance Enhancements
 * Adds composite indexes on journal_entries and journal_lines for high-performance filtering.
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Running migration: add_journal_entries_enhancements.php\n";

    // 1. Check & Add idx_je_branch_status_date on journal_entries
    $idx1 = $db->query("SHOW INDEX FROM journal_entries WHERE Key_name = 'idx_je_branch_status_date'")->fetch();
    if (!$idx1) {
        $db->exec("CREATE INDEX idx_je_branch_status_date ON journal_entries (branch_id, posting_status, entry_date)");
        echo "[SUCCESS] Created composite index 'idx_je_branch_status_date' on journal_entries\n";
    } else {
        echo "[SKIP] Index 'idx_je_branch_status_date' already exists on journal_entries\n";
    }

    // 2. Check & Add idx_je_branch_source on journal_entries
    $idx2 = $db->query("SHOW INDEX FROM journal_entries WHERE Key_name = 'idx_je_branch_source'")->fetch();
    if (!$idx2) {
        $db->exec("CREATE INDEX idx_je_branch_source ON journal_entries (branch_id, source_type)");
        echo "[SUCCESS] Created composite index 'idx_je_branch_source' on journal_entries\n";
    } else {
        echo "[SKIP] Index 'idx_je_branch_source' already exists on journal_entries\n";
    }

    // 3. Check & Add idx_jl_je_acc on journal_lines
    $idx3 = $db->query("SHOW INDEX FROM journal_lines WHERE Key_name = 'idx_jl_je_acc'")->fetch();
    if (!$idx3) {
        $db->exec("CREATE INDEX idx_jl_je_acc ON journal_lines (journal_entry_id, account_id)");
        echo "[SUCCESS] Created composite index 'idx_jl_je_acc' on journal_lines\n";
    } else {
        echo "[SKIP] Index 'idx_jl_je_acc' already exists on journal_lines\n";
    }

    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
