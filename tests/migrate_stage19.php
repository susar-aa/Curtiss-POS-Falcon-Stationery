<?php
/**
 * Curtiss POS - Stage 19 Migration & COA Cleanup Script
 * Adds 4050 Bank Interest / Other Income and detailed Expense COA accounts,
 * performs controlled idempotent reclassification of historical bank interest journals from 4010 to 4050,
 * and updates expense category mappings.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    echo "--- Stage 19 Migration & COA Cleanup Starting ---\n";

    // 1. Add 4050 Bank Interest / Other Income
    $stmt4050 = $db->query("SELECT id FROM chart_of_accounts WHERE account_code = '4050'");
    $acc4050Id = $stmt4050->fetchColumn();
    if (!$acc4050Id) {
        $db->exec("
            INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_system, status)
            VALUES ('4050', 'Bank Interest / Other Income Account', 'income', 1, 'active')
        ");
        $acc4050Id = $db->lastInsertId();
        echo "[SUCCESS] Created Account 4050 (Bank Interest / Other Income Account)\n";
    } else {
        echo "[INFO] Account 4050 already exists (ID: {$acc4050Id})\n";
    }

    // 2. Add Detailed Expense Accounts
    $expenseAccounts = [
        ['5110', 'Fuel & Transport Expense', 'expense'],
        ['5120', 'Meals & Refreshment Expense', 'expense'],
        ['5130', 'Utilities & Bills Expense', 'expense'],
        ['5140', 'Repairs & Maintenance Expense', 'expense'],
        ['5150', 'Accommodation & Travel Expense', 'expense'],
        ['5160', 'General Office Expenses', 'expense']
    ];

    foreach ($expenseAccounts as $acc) {
        $stmtChk = $db->query("SELECT id FROM chart_of_accounts WHERE account_code = '{$acc[0]}'");
        if (!$stmtChk->fetchColumn()) {
            $db->exec("
                INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_system, status)
                VALUES ('{$acc[0]}', '{$acc[1]}', '{$acc[2]}', 1, 'active')
            ");
            echo "[SUCCESS] Created Expense Account {$acc[0]} - {$acc[1]}\n";
        }
    }

    // 3. Reclassify Historical Bank Interest Journals (Credited to 4010 instead of 4050)
    $stmtAcc4010 = $db->query("SELECT id FROM chart_of_accounts WHERE account_code = '4010'");
    $acc4010Id = $stmtAcc4010->fetchColumn();

    if ($acc4010Id && $acc4050Id) {
        // Find journal entries with source_type = 'bank_interest' or source_type = 'bank_adjustment' that credited 4010
        $stmtBankInt = $db->query("
            SELECT je.id, je.branch_id, jl.credit AS amount, je.entry_date, je.entry_number
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.journal_entry_id
            WHERE (je.source_type IN ('bank_interest', 'bank_adjustment') OR je.description LIKE '%Interest%')
              AND jl.account_id = {$acc4010Id}
              AND jl.credit > 0
              AND je.id NOT IN (
                  SELECT source_id FROM journal_entries WHERE source_type = 'reclassify_bank_interest'
              )
        ");
        $intJournals = $stmtBankInt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($intJournals as $ij) {
            $amt = (float)$ij['amount'];
            $bId = (int)$ij['branch_id'];

            // Generate Journal Number
            $jNum = 'JRN-RECLASS-INT-' . $ij['id'];

            // Post Reclassification Entry: Debit 4010 (Sales Revenue), Credit 4050 (Bank Interest / Other Income)
            $stmtReclass = $db->prepare("
                INSERT INTO journal_entries (entry_number, entry_date, branch_id, source_type, source_id, description, total_debit, total_credit, posting_status, created_by)
                VALUES (:jnum, CURRENT_DATE(), :bid, 'reclassify_bank_interest', :sid, :desc, :amt, :amt, 'posted', 1)
            ");
            $stmtReclass->execute([
                ':jnum' => $jNum,
                ':bid'  => $bId,
                ':sid'  => $ij['id'],
                ':desc' => "Stage 19 Migration: Controlled reclassification of Bank Interest from 4010 Sales Revenue to 4050 Other Income (Original Journal #{$ij['entry_number']})",
                ':amt'  => $amt
            ]);
            $reclassJeId = $db->lastInsertId();

            // Line 1: Debit 4010 (Sales Revenue)
            $db->prepare("
                INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, line_description)
                VALUES (:je, :acc, :amt, 0.00, 'Reclassify Bank Interest out of Sales Revenue')
            ")->execute([':je' => $reclassJeId, ':acc' => $acc4010Id, ':amt' => $amt]);

            // Line 2: Credit 4050 (Bank Interest / Other Income)
            $db->prepare("
                INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, line_description)
                VALUES (:je, :acc, 0.00, :amt, 'Reclassify Bank Interest to Other Income')
            ")->execute([':je' => $reclassJeId, ':acc' => $acc4050Id, ':amt' => $amt]);

            echo "[RECLASSIFIED] Reclassified LKR {$amt} from 4010 to 4050 (Reclass Journal #{$jNum})\n";
        }
    }

    // 4. Ensure products table has sku column
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS `sku` VARCHAR(100) NULL AFTER `barcode`");

    $db->commit();
    echo "--- Stage 19 Migration & COA Cleanup Completed Successfully ---\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
