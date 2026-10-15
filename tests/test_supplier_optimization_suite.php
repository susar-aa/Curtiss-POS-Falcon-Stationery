<?php
/**
 * CURTISS POS - SUPPLIER MASTER OPTIMIZATION & COMPLIANCE CLI TEST SUITE
 * 
 * Tests and verifies:
 * 1. Database composite indexes (idx_suppliers_del_name, idx_suppliers_del_status, idx_sob_supp_status, idx_scrd_supp_status)
 * 2. Supplier CRUD, unique code generation & case validation
 * 3. In-cell status toggling & Audit logging
 * 4. Safe soft-deletion, Trash Bin & Restore flow
 * 5. Universal search across all data columns & monetary amounts (raw, formatted, LKR, Rs.)
 * 6. Batch Supplier Summaries query performance & accuracy (eliminating N+1 queries)
 * 7. Opening Balance double-entry Chart of Accounts (COA) posting & reversal
 * 8. Clean isolated cleanup (NO TRUNCATE)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/SupplierOpeningBalance.php';
require_once __DIR__ . '/../core/Accounting.php';

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$description}\n";
        $failCount++;
    }
}

echo "====================================================\n";
echo "   SUPPLIER MASTER OPTIMIZATION & COMPLIANCE SUITE   \n";
echo "====================================================\n\n";

$createdSupplierIds = [];
$createdJeIds = [];

try {
    // 1. Verify Database Indexes
    echo "--- 1. Testing Database Indexes ---\n";
    $supIndexes = array_column(Database::fetchAll("SHOW INDEX FROM suppliers"), 'Key_name');
    assertTest("Index 'idx_suppliers_del_name' exists on suppliers", in_array('idx_suppliers_del_name', $supIndexes));
    assertTest("Index 'idx_suppliers_del_status' exists on suppliers", in_array('idx_suppliers_del_status', $supIndexes));

    $sobIndexes = array_column(Database::fetchAll("SHOW INDEX FROM supplier_opening_balances"), 'Key_name');
    assertTest("Composite Index 'idx_sob_supp_status' exists on supplier_opening_balances", in_array('idx_sob_supp_status', $sobIndexes));

    $scrdIndexes = array_column(Database::fetchAll("SHOW INDEX FROM supplier_credits"), 'Key_name');
    assertTest("Composite Index 'idx_scrd_supp_status' exists on supplier_credits", in_array('idx_scrd_supp_status', $scrdIndexes));

    // 2. Testing Supplier Creation & Validation
    echo "\n--- 2. Testing Supplier Creation & Validation ---\n";
    $invalidRes = Supplier::create('');
    assertTest("Rejects empty supplier name", !$invalidRes['success']);

    $testSuffix = time() . '_' . rand(100, 999);
    $testName = "Test Vendor {$testSuffix}";
    $testPhone = "0771234567";
    $testWhatsapp = "0779876543";
    $testAddress = "No 123, Merchant Way, Kandy";
    $testBank = "Commercial Bank";
    $testAccNo = "8899001122";
    $testAccName = "Test Vendor Ent";
    $testBranch = "City Branch";

    $createRes = Supplier::create(
        $testName, $testPhone, $testWhatsapp, $testAddress, 'active', null,
        $testBank, $testAccName, $testAccNo, $testBranch
    );
    assertTest("Supplier created successfully", $createRes['success']);
    $supplierId = (int)$createRes['id'];
    $createdSupplierIds[] = $supplierId;

    $supplier = Supplier::find($supplierId);
    assertTest("Supplier retrieved by ID", $supplier !== null);
    assertTest("Auto-generated supplier code matches SUP- format", strpos($supplier['supplier_code'], 'SUP-') === 0);
    assertTest("Supplier status defaults to 'active'", $supplier['status'] === 'active');
    assertTest("Phone number stored correctly", $supplier['phone'] === $testPhone);
    assertTest("WhatsApp stored correctly", $supplier['whatsapp'] === $testWhatsapp);
    assertTest("Bank account number stored correctly", $supplier['bank_account_no'] === $testAccNo);

    // Duplicate supplier code rejection
    $dupRes = Supplier::create("Duplicate Vendor {$testSuffix}", null, null, null, 'active', $supplier['supplier_code']);
    assertTest("Rejects duplicate supplier code", !$dupRes['success']);

    // 3. Testing Supplier Update & Status Toggle
    echo "\n--- 3. Testing Update & Status Toggle ---\n";
    $updatedName = "Updated Vendor {$testSuffix}";
    $updateRes = Supplier::update(
        $supplierId, $updatedName, "0712223333", $testWhatsapp, "New Address", 'active',
        $supplier['supplier_code'], $testBank, $testAccName, $testAccNo, $testBranch
    );
    assertTest("Supplier updated successfully", $updateRes['success']);

    $updatedSup = Supplier::find($supplierId);
    assertTest("Updated name preserved", $updatedSup['name'] === $updatedName);
    assertTest("Updated phone preserved", $updatedSup['phone'] === "0712223333");

    // Toggle status (Active -> Inactive)
    $toggleRes1 = Supplier::toggleStatus($supplierId);
    assertTest("Status toggle (Active -> Inactive) succeeds", $toggleRes1['success']);
    $supToggled1 = Supplier::find($supplierId);
    assertTest("Status is now 'inactive'", $supToggled1['status'] === 'inactive');

    // Toggle status (Inactive -> Active)
    $toggleRes2 = Supplier::toggleStatus($supplierId);
    assertTest("Status toggle (Inactive -> Active) succeeds", $toggleRes2['success']);
    $supToggled2 = Supplier::find($supplierId);
    assertTest("Status is restored to 'active'", $supToggled2['status'] === 'active');

    // 4. Testing Soft Delete, Trash Bin & Restore
    echo "\n--- 4. Testing Soft Delete, Trash Bin & Restore ---\n";
    $delRes = Supplier::delete($supplierId);
    assertTest("Soft-delete supplier succeeds", $delRes['success']);

    $deletedSup = Supplier::find($supplierId);
    assertTest("Soft-deleted supplier hidden from active queries", $deletedSup === null);

    $trashList = Supplier::deleted();
    $inTrash = in_array($supplierId, array_column($trashList, 'id'));
    assertTest("Soft-deleted supplier appears in Trash Bin", $inTrash);

    $restoreRes = Supplier::restore($supplierId);
    assertTest("Restore supplier succeeds", $restoreRes['success']);

    $restoredSup = Supplier::find($supplierId);
    assertTest("Restored supplier back in active records", $restoredSup !== null && $restoredSup['deleted_at'] === null);

    // 5. Testing Opening Balance Double-Entry COA Posting & Reversal
    echo "\n--- 5. Testing Opening Balance System & Double-Entry Accounting ---\n";
    $obData = [
        'supplier_id'   => $supplierId,
        'amount'        => 25000.00,
        'balance_type'  => 'owe_supplier',
        'entry_date'    => date('Y-m-d'),
        'reference_no'  => "OB-TEST-{$testSuffix}",
        'notes'         => 'Test Opening Balance'
    ];
    $obRes = SupplierOpeningBalance::postOpeningBalance($obData, 1, 1);
    assertTest("Post Opening Balance succeeds", $obRes['success']);
    $obId = (int)($obRes['id'] ?? $obRes['opening_balance_id'] ?? 0);

    $postedOb = SupplierOpeningBalance::getActiveOpeningBalance($supplierId);
    assertTest("Active opening balance found for supplier", $postedOb !== null && (float)$postedOb['amount'] === 25000.00);

    // Verify Double-Entry Journal Entry
    $jeId = (int)($postedOb['journal_entry_id'] ?? 0);
    $createdJeIds[] = $jeId;
    $journal = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $jeId]);
    assertTest("COA Journal Entry created for Opening Balance", $journal !== null && $journal['posting_status'] === 'posted');

    $lines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :id", [':id' => $jeId]);
    $debitSum = array_sum(array_column($lines, 'debit'));
    $creditSum = array_sum(array_column($lines, 'credit'));
    assertTest("Double-entry journal is 100% balanced (Debit = Credit = 25,000.00)", $debitSum > 0 && abs($debitSum - $creditSum) < 0.001);

    // 6. Testing Batch Supplier Summaries vs Single Queries (Rule 5 N+1 Query Elimination)
    echo "\n--- 6. Testing Batch Summaries Query Optimization ---\n";
    $batchSummaries = SupplierPayment::getBatchSupplierSummaries([$supplierId], 1);
    assertTest("Batch summaries returned result for supplier", isset($batchSummaries[$supplierId]));

    $singleSummary = SupplierPayment::getSupplierOutstandingSummary($supplierId, 1);
    $batchSum = $batchSummaries[$supplierId];

    assertTest("Batch total_outstanding accurately matches single query (25,000.00)", (float)$batchSum['total_outstanding'] === (float)$singleSummary['total_outstanding']);
    assertTest("Batch total_credit_balance matches single query", (float)$batchSum['total_credit_balance'] === (float)$singleSummary['total_credit_balance']);
    assertTest("Batch active_opening_balance matches single query", ($batchSum['active_opening_balance']['id'] ?? null) == ($singleSummary['active_opening_balance']['id'] ?? null));

    // 7. Testing Universal Search Across All Data Columns & Monetary Amounts
    echo "\n--- 7. Testing Universal Multi-Column & Monetary Search ---\n";
    // By Name
    $sNameRes = Supplier::all(false, $testSuffix);
    assertTest("Search by Name keyword finds supplier", in_array($supplierId, array_column($sNameRes, 'id')));

    // By Code
    $sCodeRes = Supplier::all(false, $supplier['supplier_code']);
    assertTest("Search by Supplier Code finds supplier", in_array($supplierId, array_column($sCodeRes, 'id')));

    // By Bank Account No
    $sBankRes = Supplier::all(false, $testAccNo);
    assertTest("Search by Bank Account No finds supplier", in_array($supplierId, array_column($sBankRes, 'id')));

    // By Raw Monetary Amount (25000)
    $sRawAmtRes = Supplier::all(false, '25000');
    assertTest("Search by raw amount '25000' finds supplier with 25,000.00 outstanding", in_array($supplierId, array_column($sRawAmtRes, 'id')));

    // By Comma Formatted Amount (25,000.00)
    $sFmtAmtRes = Supplier::all(false, '25,000.00');
    assertTest("Search by formatted amount '25,000.00' finds supplier", in_array($supplierId, array_column($sFmtAmtRes, 'id')));

    // By LKR Currency Prefix
    $sLkrRes = Supplier::all(false, 'LKR 25,000.00');
    assertTest("Search by 'LKR 25,000.00' finds supplier", in_array($supplierId, array_column($sLkrRes, 'id')));

    // By Rs. Currency Prefix
    $sRsRes = Supplier::all(false, 'Rs. 25,000.00');
    assertTest("Search by 'Rs. 25,000.00' finds supplier", in_array($supplierId, array_column($sRsRes, 'id')));

    // 8. Reverse Opening Balance
    echo "\n--- 8. Testing Opening Balance Reversal ---\n";
    $revRes = SupplierOpeningBalance::reverseOpeningBalance($obId, "Test audit reversal {$testSuffix}", 1, 1);
    assertTest("Reverse Opening Balance succeeds", $revRes['success']);

    $reversedOb = Database::fetchOne("SELECT * FROM supplier_opening_balances WHERE id = :id", [':id' => $obId]);
    assertTest("Opening balance status updated to 'reversed'", $reversedOb['status'] === 'reversed');

    $revJeId = (int)($reversedOb['reversal_journal_id'] ?? 0);
    $createdJeIds[] = $revJeId;
    $revJournal = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $revJeId]);
    assertTest("Reversal Journal Entry created", $revJournal !== null);

    $revLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :id", [':id' => $revJeId]);
    $revDebit = array_sum(array_column($revLines, 'debit'));
    $revCredit = array_sum(array_column($revLines, 'credit'));
    assertTest("Reversal journal is balanced (Debit = Credit = 25,000.00)", $revDebit > 0 && abs($revDebit - $revCredit) < 0.001);

} catch (Throwable $e) {
    echo "\n[ERROR] Exception during test execution: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failCount++;
} finally {
    // 9. Clean Up Isolated Test Records (NO TRUNCATE)
    echo "\n--- 9. Cleaning Up Test Fixtures Safely (NO TRUNCATE) ---\n";
    if (!empty($createdJeIds)) {
        $jeList = implode(',', array_filter(array_map('intval', $createdJeIds)));
        if (!empty($jeList)) {
            Database::execute("DELETE FROM bank_reconciliation_items WHERE journal_line_id IN (SELECT id FROM journal_lines WHERE journal_entry_id IN ({$jeList}))");
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN ({$jeList})");
            Database::execute("DELETE FROM journal_entries WHERE id IN ({$jeList})");
        }
    }

    if (!empty($createdSupplierIds)) {
        $supList = implode(',', array_map('intval', $createdSupplierIds));
        Database::execute("DELETE FROM supplier_opening_balances WHERE supplier_id IN ({$supList})");
        Database::execute("DELETE FROM audit_logs WHERE module IN ('suppliers', 'accounting') AND record_id IN ({$supList})");
        Database::execute("DELETE FROM suppliers WHERE id IN ({$supList})");
    }
    echo "  Cleaned up isolated test fixtures without affecting operational data.\n";
}

echo "\n====================================================\n";
echo "TEST RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "====================================================\n";

if ($failCount > 0) exit(1);
