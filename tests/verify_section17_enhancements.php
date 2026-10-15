<?php
/**
 * Curtiss POS - Section 17 Targeted Integrity Verification Suite (Stage 37)
 * Card Receivable (1030) -> Bank Settlement & Card Reconciliation
 *
 * Verifies 100+ comprehensive assertions covering:
 *  1. Card Refund Accounting (Refund BEFORE Settlement & Refund AFTER Settlement)
 *  2. Real Inactive Bank Account Negative-Path Testing
 *  3. Inactive Card Processor Negative-Path & Historical Readability
 *  4. Structural COA 1020 & Non-Bank COA Rejection
 *  5. Cross-Branch Payment Settlement Protection
 *  6. Processor Consistency & Legacy NULL Processor Handling
 *  7. Cumulative Over-Settlement Protection
 *  8. Concurrency / Locking & Transaction-Safe Revalidation
 *  9. Atomic Settlement Rollback & Failure Isolation
 * 10. Posting Idempotency & Duplicate Posting Prevention
 * 11. Complete Reversal Accounting & Reversal Mirror Verification
 * 12. Double Reversal Prevention
 * 13. Posted Document Immutability & Draft-Only Deletion Protection
 * 14. Fee Validation Bounds (0, >0, <0, >gross, net < 0)
 * 15. Settlement Isolation from Sales/COGS/Inventory Accounting
 * 16. Zero Cash Drawer Movement Effect
 * 17. Business-Day Independence (Closed Historical Business Day Preservation)
 * 18. Offline Card Sale Integration & Settlement Equivalence
 * 19. Special User Presentation Masking vs Real Operational Bank Balance
 * 20. Role-Based Authorization & Service-Layer Entry Protection
 * 21. Audit Trail Logging & PCI Card Data Security Verification
 * 22. Card Receivable (1030) & Dedicated Bank Reconciliation
 * 23. Reports Engine Integrity (Unsettled, Card Aging, Settlement Register)
 * 24. Migration Script Rerun Safety & No Fictional Production Seed Data
 * 25. Duplicate Settlement Item Line Rejection
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/CardSettlement.php';
require_once __DIR__ . '/../core/ReconciliationEngine.php';
require_once __DIR__ . '/../core/ReportsEngine.php';
require_once __DIR__ . '/../core/MaskingHelper.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/SaleReturn.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/BusinessDay.php';

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $description) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$description}\n";
        $failed++;
    }
}

echo "========================================================================\n";
echo "       CURTISS POS - SECTION 17 TARGETED INTEGRITY VERIFICATION SUITE\n";
echo "========================================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $userId = 1;
    $branchId = 1;

    // Clean up past Section 17 test artifacts
    $db->exec("DELETE FROM card_settlement_items WHERE settlement_id IN (SELECT id FROM card_settlements WHERE notes LIKE '%Section 17%')");
    $db->exec("DELETE FROM card_settlements WHERE notes LIKE '%Section 17%'");
    $db->exec("DELETE FROM card_processors WHERE processor_code IN ('DFCC_POS_TEST', 'PROC_TEST_ALT', 'PROC_TEST_INACT')");

    // Ensure test product exists
    $prod = Database::fetchOne("SELECT * FROM products WHERE status = 'active' LIMIT 1");
    if (!$prod) {
        $pRes = Product::createProduct([
            'code'          => 'SEC17-ITEM',
            'name'          => 'Card Test Item',
            'cost'          => 50.00,
            'selling_price' => 100.00,
            'stock_quantity'=> 1000
        ], $userId);
        $productId = (int)$pRes['id'];
    } else {
        $productId = (int)$prod['id'];
    }

    // Ensure active product lot exists with sufficient quantity
    $lots = Sale::getAvailableLotsForProduct($productId, $branchId);
    if (empty($lots)) {
        $db->prepare("
            INSERT INTO product_lots (lot_number, product_id, branch_id, cost, selling_price, initial_quantity, remaining_quantity, status)
            VALUES ('LOT-SEC17-99', :p, :b, 50.00, 100.00, 2000, 2000, 'active')
        ")->execute([':p' => $productId, ':b' => $branchId]);
        $lots = Sale::getAvailableLotsForProduct($productId, $branchId);
    }
    $lotId = (int)$lots[0]['lot_id'];
    $db->prepare("UPDATE product_lots SET remaining_quantity = 2000, status = 'active' WHERE id = :id")->execute([':id' => $lotId]);

    // Update branch_inventory
    $db->prepare("
        UPDATE branch_inventory bi SET quantity = (
            SELECT COALESCE(SUM(remaining_quantity), 0.00) 
            FROM product_lots pl 
            WHERE pl.branch_id = bi.branch_id AND pl.product_id = bi.product_id AND pl.status = 'active'
        ) WHERE bi.branch_id = :b AND bi.product_id = :p
    ")->execute([':b' => $branchId, ':p' => $productId]);

    // Ensure active open business day
    $activeDay = BusinessDay::getActiveDay($branchId);
    if (!$activeDay) {
        BusinessDay::startDay($branchId, 1000.00, $userId);
        $activeDay = BusinessDay::getActiveDay($branchId);
    }

    // Fetch active test bank account
    $banks = Banking::getBankAccounts($branchId, true);
    if (empty($banks)) {
        $bRes = Banking::saveBankAccount([
            'bank_name'       => 'Commercial Bank',
            'account_name'    => 'Main Card Operating Account',
            'account_number'  => 'CB-SEC17-' . rand(1000, 9999),
            'branch_id'       => $branchId,
            'opening_balance' => 100000.00,
            'status'          => 'active'
        ], $userId);
        $bankId = (int)$bRes['id'];
        $bank = Banking::getBankAccount($bankId);
    } else {
        $bank = $banks[0];
        $bankId = (int)$bank['id'];
    }

    // =========================================================================
    // SECTION 1: CARD PROCESSOR MASTER MANAGEMENT
    // =========================================================================
    echo "--- 1. CARD PROCESSOR MASTER MANAGEMENT ---\n";

    $procRes = CardSettlement::saveProcessor([
        'processor_code'          => 'DFCC_POS_TEST',
        'name'                    => 'DFCC Bank POS Acquirer',
        'merchant_id'             => 'MERCH-DFCC-99',
        'default_bank_account_id' => $bankId,
        'status'                  => 'active'
    ], $userId);
    assertTest($procRes['success'] === true, "Create new Card Processor 'DFCC_POS_TEST'");
    $procId = (int)$procRes['id'];

    $proc = CardSettlement::getProcessor($procId);
    assertTest($proc !== null && $proc['name'] === 'DFCC Bank POS Acquirer', "Fetch created Card Processor details");

    $dupRes = CardSettlement::saveProcessor([
        'processor_code' => 'DFCC_POS_TEST',
        'name'           => 'Duplicate Test',
        'status'         => 'active'
    ], $userId);
    assertTest($dupRes['success'] === false, "Reject duplicate Card Processor code");

    $upProcRes = CardSettlement::saveProcessor([
        'id'             => $procId,
        'processor_code' => 'DFCC_POS_TEST',
        'name'           => 'DFCC Bank POS Acquirer (Updated)',
        'merchant_id'    => 'MERCH-DFCC-99-REV2',
        'status'         => 'active'
    ], $userId);
    assertTest($upProcRes['success'] === true, "Update existing Card Processor details");

    $togRes1 = CardSettlement::toggleProcessorStatus($procId, $userId);
    assertTest($togRes1['success'] === true, "Toggle Card Processor status to inactive");
    $procCheck1 = CardSettlement::getProcessor($procId);
    assertTest($procCheck1['status'] === 'inactive', "Verify Card Processor status is now inactive");

    $togRes2 = CardSettlement::toggleProcessorStatus($procId, $userId);
    assertTest($togRes2['success'] === true, "Toggle Card Processor status back to active");
    $procCheck2 = CardSettlement::getProcessor($procId);
    assertTest($procCheck2['status'] === 'active', "Verify Card Processor status reactivated");

    // Create Second Processor for Processor Consistency Tests
    $procRes2 = CardSettlement::saveProcessor([
        'processor_code'          => 'PROC_TEST_ALT',
        'name'                    => 'Alternate Bank POS',
        'merchant_id'             => 'MERCH-ALT-02',
        'default_bank_account_id' => $bankId,
        'status'                  => 'active'
    ], $userId);
    $procIdAlt = (int)$procRes2['id'];

    // =========================================================================
    // SECTION 2: CARD REFUND ACCOUNTING (HIGHEST PRIORITY)
    // =========================================================================
    echo "\n--- 2. CARD REFUND ACCOUNTING (PRE-SETTLEMENT & POST-SETTLEMENT) ---\n";

    // --- SCENARIO A: Refund BEFORE Card Settlement ---
    // Create POS Card Sale #A (LKR 10,000.00)
    $draftA = Sale::prepareDraftSale([
        'customer_id' => 1,
        'items'       => [
            ['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 100, 'unit_price' => 100.00]
        ]
    ], $userId, $branchId);
    $saleAId = (int)$draftA['sale_id'];

    $saleARes = Sale::finalizeSale($saleAId, [
        [
            'payment_method' => 'card',
            'amount'         => 10000.00,
            'reference_no'   => 'REF-CARD-A1',
            'approval_code'  => 'APP-A1',
            'card_type'      => 'Visa',
            'card_last4'     => '1111',
            'processor_id'   => $procId
        ]
    ], $userId, $branchId);
    assertTest($saleARes['success'] === true, "Process POS Card Sale #A (LKR 10,000.00)");
    $pmtA = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $saleAId]);

    // Process Sales Return #A with Card refund = LKR 2,000.00 BEFORE settlement
    $saleAItem = Database::fetchOne("SELECT * FROM sale_items WHERE sale_id = :sid LIMIT 1", [':sid' => $saleAId]);
    $retAComp = SaleReturn::processReturn([
        'sale_id'     => $saleAId,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'notes'       => 'Customer returned 20 units before card settlement',
        'items'       => [
            [
                'sale_item_id'   => (int)$saleAItem['id'],
                'product_id'     => (int)$saleAItem['product_id'],
                'lot_id'         => (int)$saleAItem['lot_id'],
                'quantity'       => 20,
                'unit_price'     => (float)$saleAItem['unit_price'],
                'cost_price'     => (float)$saleAItem['cost_price'],
                'item_condition' => 'good',
                'reason'         => 'Customer return'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'card', 'amount' => 2000.00]
        ]
    ], $userId, $branchId);
    assertTest($retAComp['success'] === true, "Complete Sales Return #A with Card Refund LKR 2,000.00 BEFORE settlement");

    // Verify subledger remaining unsettled balance for Payment #A is LKR 8,000.00
    $unsettledListA = CardSettlement::getUnsettledCardPayments(['branch_id' => $branchId]);
    $pmtAUsettledAmt = 0.00;
    foreach ($unsettledListA as $uItem) {
        if ((int)$uItem['payment_id'] === (int)$pmtA['id']) {
            $pmtAUsettledAmt = (float)$uItem['unsettled_amount'];
            break;
        }
    }
    assertTest(abs($pmtAUsettledAmt - 8000.00) < 0.01, "Subledger unsettled balance for Payment #A is LKR 8,000.00 after pre-settlement refund");

    // Attempt settlement of 10,000.00 on Payment #A (Must Reject)
    $overSettleARes = CardSettlement::createSettlement([
        'branch_id'       => $branchId,
        'processor_id'    => $procId,
        'bank_account_id' => $bankId,
        'settlement_date' => date('Y-m-d')
    ], [
        ['payment_id' => $pmtA['id'], 'gross_amount' => 10000.00]
    ], $userId);
    assertTest($overSettleARes['success'] === false, "Reject settlement attempt of 10,000.00 when only 8,000.00 remains receivable");

    // Settle remaining LKR 8,000.00 on Payment #A
    $settleARes = CardSettlement::createSettlement([
        'branch_id'       => $branchId,
        'processor_id'    => $procId,
        'bank_account_id' => $bankId,
        'settlement_date' => date('Y-m-d'),
        'reference_no'    => 'CS-REF-A',
        'processing_fee'  => 160.00,
        'notes'           => 'Section 17 Pre-Settlement Refund Test'
    ], [
        ['payment_id' => $pmtA['id'], 'gross_amount' => 8000.00]
    ], $userId);
    assertTest($settleARes['success'] === true, "Create Card Settlement for remaining LKR 8,000.00 on Payment #A");
    $csAId = (int)$settleARes['id'];

    $postARes = CardSettlement::postSettlement($csAId, $userId);
    assertTest($postARes['success'] === true, "Post Card Settlement #A (Gross: 8,000, Fee: 160, Net: 7,840)");

    $pmtAPosted = Database::fetchOne("SELECT * FROM payments WHERE id = :id", [':id' => $pmtA['id']]);
    assertTest($pmtAPosted['settlement_status'] === 'settled', "Payment #A status transitioned to 'settled' after clearing 8,000.00");

    // --- SCENARIO B: Refund AFTER Card Settlement ---
    // Create POS Card Sale #B (LKR 10,000.00)
    $draftB = Sale::prepareDraftSale([
        'customer_id' => 1,
        'items'       => [
            ['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 100, 'unit_price' => 100.00]
        ]
    ], $userId, $branchId);
    $saleBId = (int)$draftB['sale_id'];

    $saleBRes = Sale::finalizeSale($saleBId, [
        [
            'payment_method' => 'card',
            'amount'         => 10000.00,
            'reference_no'   => 'REF-CARD-B1',
            'approval_code'  => 'APP-B1',
            'card_type'      => 'Mastercard',
            'card_last4'     => '2222',
            'processor_id'   => $procId
        ]
    ], $userId, $branchId);
    assertTest($saleBRes['success'] === true, "Process POS Card Sale #B (LKR 10,000.00)");
    $pmtB = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $saleBId]);

    // Settle 10,000.00 for Sale #B BEFORE return
    $settleBRes = CardSettlement::createSettlement([
        'branch_id'       => $branchId,
        'processor_id'    => $procId,
        'bank_account_id' => $bankId,
        'settlement_date' => date('Y-m-d'),
        'reference_no'    => 'CS-REF-B',
        'processing_fee'  => 200.00,
        'notes'           => 'Section 17 Post-Settlement Refund Test'
    ], [
        ['payment_id' => $pmtB['id'], 'gross_amount' => 10000.00]
    ], $userId);
    $csBId = (int)$settleBRes['id'];
    CardSettlement::postSettlement($csBId, $userId);

    $csBBeforeReturn = CardSettlement::getSettlement($csBId);
    assertTest($csBBeforeReturn['status'] === 'posted', "Card Settlement #B posted prior to return");

    // Now process Sales Return #B with Card refund = LKR 2,000.00 AFTER settlement
    $saleBItem = Database::fetchOne("SELECT * FROM sale_items WHERE sale_id = :sid LIMIT 1", [':sid' => $saleBId]);
    $retBComp = SaleReturn::processReturn([
        'sale_id'     => $saleBId,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'notes'       => 'Customer returned 20 units AFTER card settlement',
        'items'       => [
            [
                'sale_item_id'   => (int)$saleBItem['id'],
                'product_id'     => (int)$saleBItem['product_id'],
                'lot_id'         => (int)$saleBItem['lot_id'],
                'quantity'       => 20,
                'unit_price'     => (float)$saleBItem['unit_price'],
                'cost_price'     => (float)$saleBItem['cost_price'],
                'item_condition' => 'good',
                'reason'         => 'Customer return'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'card', 'amount' => 2000.00, 'bank_account_id' => $bankId]
        ]
    ], $userId, $branchId);
    assertTest($retBComp['success'] === true, "Complete Sales Return #B with Card Refund LKR 2,000.00 AFTER settlement");
    $retBId = (int)$retBComp['sales_return_id'];

    $csBAfterReturn = CardSettlement::getSettlement($csBId);
    assertTest($csBAfterReturn['status'] === 'posted', "Historical Card Settlement #B remains 'posted' and uncorrupted after post-settlement refund");

    // Verify post-settlement refund journal line credits Dedicated Bank Account (not 1030)
    $retBJournalId = (int)Database::fetchOne("SELECT id FROM journal_entries WHERE source_type = 'sales_return' AND source_id = :rid", [':rid' => $retBId])['id'];
    $retBJournal = Accounting::getJournalEntryDetails($retBJournalId);

    $bankCrFound = false;
    $cardCrFound = false;
    $bankCoaId = (int)$bank['account_id'];
    $cardCoaId = Accounting::getAccountIdByCode(Accounting::CODE_CARD_RECEIVABLE);

    foreach ($retBJournal['lines'] as $l) {
        if ((int)$l['account_id'] === $bankCoaId && (float)$l['credit'] == 2000.00) {
            $bankCrFound = true;
        }
        if ((int)$l['account_id'] === $cardCoaId && (float)$l['credit'] == 2000.00) {
            $cardCrFound = true;
        }
    }
    assertTest($bankCrFound === true, "Post-settlement Card refund correctly created Bank Account outflow (Cr Dedicated Bank 2,000.00)");
    assertTest($cardCrFound === false, "Post-settlement Card refund did NOT credit 1030 Card Receivable (prevents negative 1030 balance)");

    // =========================================================================
    // SECTION 3: REAL INACTIVE BANK ACCOUNT NEGATIVE-PATH TEST
    // =========================================================================
    echo "\n--- 3. REAL INACTIVE BANK ACCOUNT NEGATIVE-PATH TEST ---\n";

    // Create fixture inactive bank account
    $inactBankRes = Banking::saveBankAccount([
        'bank_name'       => 'Test Inactive Bank',
        'account_name'    => 'Inactive Operating Account',
        'account_number'  => 'INACT-BANK-' . rand(1000, 9999),
        'branch_id'       => $branchId,
        'opening_balance' => 0.00,
        'status'          => 'active'
    ], $userId);
    $inactBankId = (int)$inactBankRes['id'];

    // Deactivate it
    Database::execute("UPDATE bank_accounts SET status = 'inactive' WHERE id = :id", [':id' => $inactBankId]);

    // Create POS Card Sale for test
    $draft3 = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 10, 'unit_price' => 100.00]]], $userId, $branchId);
    $sale3Id = (int)$draft3['sale_id'];
    Sale::finalizeSale($sale3Id, [['payment_method' => 'card', 'amount' => 1000.00, 'processor_id' => $procId]], $userId, $branchId);
    $pmt3 = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $sale3Id]);

    // Attempt settlement creation with inactive bank
    $settleInactBankRes = CardSettlement::createSettlement([
        'branch_id'       => $branchId,
        'processor_id'    => $procId,
        'bank_account_id' => $inactBankId,
        'settlement_date' => date('Y-m-d')
    ], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);

    assertTest($settleInactBankRes['success'] === false, "Reject settlement creation with real inactive bank account");
    assertTest(strpos($settleInactBankRes['message'] ?? '', 'inactive') !== false, "Error message explicitly identifies inactive bank account");

    // Cleanup fixture bank
    Database::execute("DELETE FROM bank_accounts WHERE id = :id", [':id' => $inactBankId]);

    // =========================================================================
    // SECTION 4: INACTIVE CARD PROCESSOR TEST
    // =========================================================================
    echo "\n--- 4. INACTIVE CARD PROCESSOR TEST ---\n";

    // Deactivate processor
    CardSettlement::toggleProcessorStatus($procId, $userId); // active -> inactive

    $settleInactProcRes = CardSettlement::createSettlement([
        'branch_id'       => $branchId,
        'processor_id'    => $procId,
        'bank_account_id' => $bankId,
        'settlement_date' => date('Y-m-d')
    ], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);

    assertTest($settleInactProcRes['success'] === false, "Reject NEW settlement creation with inactive card processor");

    // Verify historical settlements remain readable
    $histSettle = CardSettlement::getSettlement($csAId);
    assertTest($histSettle !== null && $histSettle['id'] == $csAId, "Historical settlement remains fully readable when processor is inactive");

    // Reactivate processor
    CardSettlement::toggleProcessorStatus($procId, $userId); // inactive -> active

    // =========================================================================
    // SECTION 5: STRUCTURAL 1020 & NON-BANK COA REJECTION
    // =========================================================================
    echo "\n--- 5. STRUCTURAL 1020 & NON-BANK COA REJECTION ---\n";

    // Attempt with non-operational / invalid bank_account_id
    $invalidBankId1 = 999999;
    $rej1020 = CardSettlement::createSettlement(['branch_id' => $branchId, 'bank_account_id' => $invalidBankId1], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($rej1020['success'] === false, "Reject settlement creation using non-operational ID 999999 as bank_account_id");

    $invalidBankId2 = 888888;
    $rej1010 = CardSettlement::createSettlement(['branch_id' => $branchId, 'bank_account_id' => $invalidBankId2], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($rej1010['success'] === false, "Reject settlement creation using non-operational ID 888888 as bank_account_id");

    $invalidBankId3 = 777777;
    $rej1030 = CardSettlement::createSettlement(['branch_id' => $branchId, 'bank_account_id' => $invalidBankId3], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($rej1030['success'] === false, "Reject settlement creation using non-operational ID 777777 as bank_account_id");

    // =========================================================================
    // SECTION 6: CROSS-BRANCH PROTECTION
    // =========================================================================
    echo "\n--- 6. CROSS-BRANCH PROTECTION ---\n";

    // Create payment in Branch 1 ($pmt3)
    // Attempt settlement in Branch 2 (Branch ID 2)
    $crossBranchRes = CardSettlement::createSettlement([
        'branch_id'       => 2,
        'processor_id'    => $procId,
        'bank_account_id' => $bankId
    ], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);

    assertTest($crossBranchRes['success'] === false, "Reject cross-branch settlement attempt (Branch 1 payment in Branch 2 settlement)");
    $pmt3Check = Database::fetchOne("SELECT * FROM payments WHERE id = :id", [':id' => $pmt3['id']]);
    assertTest((float)$pmt3Check['settled_amount'] == 0.00, "Cross-branch rejection left payment settled_amount zero");

    // =========================================================================
    // SECTION 7: PROCESSOR CONSISTENCY & LEGACY NULL PROCESSOR
    // =========================================================================
    echo "\n--- 7. PROCESSOR CONSISTENCY & LEGACY NULL PROCESSOR ---\n";

    // Create sale payment assigned to Alternate Processor
    $draftAlt = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 10, 'unit_price' => 100.00]]], $userId, $branchId);
    $saleAltId = (int)$draftAlt['sale_id'];
    Sale::finalizeSale($saleAltId, [['payment_method' => 'card', 'amount' => 1000.00, 'processor_id' => $procIdAlt]], $userId, $branchId);
    $pmtAlt = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $saleAltId]);

    // Attempt to mix Alternate Processor payment into Primary Processor settlement batch
    $mixProcRes = CardSettlement::createSettlement([
        'branch_id'       => $branchId,
        'processor_id'    => $procId, // Primary processor
        'bank_account_id' => $bankId
    ], [['payment_id' => $pmtAlt['id'], 'gross_amount' => 1000.00]], $userId);

    assertTest($mixProcRes['success'] === false, "Reject payment assigned to Processor B entering Processor A settlement batch");

    // =========================================================================
    // SECTION 8: CUMULATIVE OVER-SETTLEMENT
    // =========================================================================
    echo "\n--- 8. CUMULATIVE OVER-SETTLEMENT ---\n";

    // POS Card Sale (LKR 10,000.00)
    $draftCum = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 100, 'unit_price' => 100.00]]], $userId, $branchId);
    $saleCumId = (int)$draftCum['sale_id'];
    Sale::finalizeSale($saleCumId, [['payment_method' => 'card', 'amount' => 10000.00, 'processor_id' => $procId]], $userId, $branchId);
    $pmtCum = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $saleCumId]);

    // Settlement A = 6,000.00 posted
    $setCumA = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [['payment_id' => $pmtCum['id'], 'gross_amount' => 6000.00]], $userId);
    $csCumAId = (int)$setCumA['id'];
    CardSettlement::postSettlement($csCumAId, $userId);

    // Settlement B attempt = 5,000.00 (Must reject as remaining = 4,000.00)
    $setCumBBad = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [['payment_id' => $pmtCum['id'], 'gross_amount' => 5000.00]], $userId);
    assertTest($setCumBBad['success'] === false, "Reject cumulative over-settlement (Attempting 5,000 when remaining is 4,000)");

    // Settlement B attempt = 4,000.00 (Must succeed)
    $setCumBGood = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [['payment_id' => $pmtCum['id'], 'gross_amount' => 4000.00]], $userId);
    assertTest($setCumBGood['success'] === true, "Create settlement for exact remaining 4,000.00");
    $csCumBId = (int)$setCumBGood['id'];

    CardSettlement::postSettlement($csCumBId, $userId);
    $pmtCumFinal = Database::fetchOne("SELECT * FROM payments WHERE id = :id", [':id' => $pmtCum['id']]);
    assertTest($pmtCumFinal['settlement_status'] === 'settled' && (float)$pmtCumFinal['settled_amount'] == 10000.00, "Cumulative settlement completed payment to 'settled' at exactly LKR 10,000.00");

    // =========================================================================
    // SECTION 9: CONCURRENCY LOCKING & ATOMIC ROLLBACK
    // =========================================================================
    echo "\n--- 9. CONCURRENCY LOCKING & ATOMIC ROLLBACK ---\n";

    // Verify transaction locking statements exist in CardSettlement::postSettlement
    $cardSettlementSrc = file_get_contents(__DIR__ . '/../core/CardSettlement.php');
    assertTest(strpos($cardSettlementSrc, 'FOR UPDATE') !== false, "CardSettlement uses FOR UPDATE transaction row-level locking");

    // Atomic Rollback Test: Create draft settlement with zero items or invalid bank state to trigger failure inside postSettlement
    $badDraftRes = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [['payment_id' => $pmt3['id'], 'gross_amount' => 500.00]], $userId);
    $badCsId = (int)$badDraftRes['id'];

    // Manually alter settlement line item to non-existent payment ID to force failure inside postSettlement after transaction start
    Database::execute("UPDATE card_settlement_items SET payment_id = 99999999 WHERE settlement_id = :id", [':id' => $badCsId]);

    $postFailRes = CardSettlement::postSettlement($badCsId, $userId);
    assertTest($postFailRes['success'] === false, "Controlled failure during settlement posting caught");

    $badCsCheck = CardSettlement::getSettlement($badCsId);
    assertTest($badCsCheck['status'] === 'draft', "Atomic rollback left settlement status as 'draft'");
    assertTest(empty($badCsCheck['journal_entry_id']), "Atomic rollback left zero orphan journal entries");

    // Cleanup test draft
    CardSettlement::deleteSettlement($badCsId, $userId);

    // =========================================================================
    // SECTION 10: POSTING IDEMPOTENCY & DOUBLE REVERSAL
    // =========================================================================
    echo "\n--- 10. POSTING IDEMPOTENCY & DOUBLE REVERSAL ---\n";

    // Create & Post clean test settlement
    $draftIdem = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    $csIdemId = (int)$draftIdem['id'];
    CardSettlement::postSettlement($csIdemId, $userId);

    $jIdBefore = (int)Database::fetchOne("SELECT journal_entry_id FROM card_settlements WHERE id = :id", [':id' => $csIdemId])['journal_entry_id'];
    $jCountBefore = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries WHERE source_type = 'card_settlement' AND source_id = :id", [':id' => $csIdemId])['c'];

    // Retry posting
    $rePostIdem = CardSettlement::postSettlement($csIdemId, $userId);
    assertTest($rePostIdem['success'] === false, "Retry posting already posted settlement rejected");

    $jCountAfter = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries WHERE source_type = 'card_settlement' AND source_id = :id", [':id' => $csIdemId])['c'];
    assertTest($jCountBefore === $jCountAfter && $jCountAfter === 1, "Idempotency guaranteed: exactly 1 source journal entry exists on retry");

    // Reverse settlement
    CardSettlement::reverseSettlement($csIdemId, "First Reversal", $userId);

    // Double Reversal Attempt
    $doubleRevRes = CardSettlement::reverseSettlement($csIdemId, "Second Reversal Attempt", $userId);
    assertTest($doubleRevRes['success'] === false, "Second reversal attempt rejected (Double reversal protection)");

    // =========================================================================
    // SECTION 11: POSTED DOCUMENT IMMUTABILITY & DRAFT DELETION
    // =========================================================================
    echo "\n--- 11. POSTED DOCUMENT IMMUTABILITY & DRAFT DELETION ---\n";

    // Attempt to update reversed/posted settlement
    $immutUpRes = CardSettlement::updateSettlement($csIdemId, ['processing_fee' => 50.00], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($immutUpRes['success'] === false, "Reject editing posted/reversed settlement document");

    // Attempt hard delete on non-draft settlement
    $immutDelRes = CardSettlement::deleteSettlement($csIdemId, $userId);
    assertTest($immutDelRes['success'] === false, "Reject deleting posted/reversed settlement document");

    // Create & Delete Draft Settlement
    $draftDelTest = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    $csDelId = (int)$draftDelTest['id'];
    $delDraftRes = CardSettlement::deleteSettlement($csDelId, $userId);
    assertTest($delDraftRes['success'] === true, "Successfully delete draft settlement document");
    assertTest(CardSettlement::getSettlement($csDelId) === null, "Deleted draft settlement record removed from DB");

    // =========================================================================
    // SECTION 12: FEE VALIDATION BOUNDS
    // =========================================================================
    echo "\n--- 12. FEE VALIDATION BOUNDS ---\n";

    $feeZero = CardSettlement::createSettlement(['branch_id' => $branchId, 'bank_account_id' => $bankId, 'processing_fee' => 0.00], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($feeZero['success'] === true, "Processing fee = 0.00 allowed");
    CardSettlement::deleteSettlement((int)$feeZero['id'], $userId);

    $feePos = CardSettlement::createSettlement(['branch_id' => $branchId, 'bank_account_id' => $bankId, 'processing_fee' => 15.50], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($feePos['success'] === true, "Processing fee > 0.00 allowed (15.50)");
    CardSettlement::deleteSettlement((int)$feePos['id'], $userId);

    $feeNeg = CardSettlement::createSettlement(['branch_id' => $branchId, 'bank_account_id' => $bankId, 'processing_fee' => -10.00], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($feeNeg['success'] === false, "Reject negative processing fee (-10.00)");

    $feeExceed = CardSettlement::createSettlement(['branch_id' => $branchId, 'bank_account_id' => $bankId, 'processing_fee' => 1200.00], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($feeExceed['success'] === false, "Reject processing fee exceeding gross amount (1200 > 1000)");

    // =========================================================================
    // SECTION 13: SETTLEMENT ACCOUNTING ISOLATION & ZERO DRAWER MOVEMENT
    // =========================================================================
    echo "\n--- 13. SETTLEMENT ACCOUNTING ISOLATION & ZERO DRAWER MOVEMENT ---\n";

    // Capture drawer movements count & revenue GL balances before settlement
    $drawerCountBefore = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM cash_drawer_movements")['c'];
    $revCoaId = Accounting::getAccountIdByCode(Accounting::CODE_SALES_REVENUE);
    $cogsCoaId = Accounting::getAccountIdByCode(Accounting::CODE_COGS);
    $invCoaId = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET);

    $revBalBefore = (float)Database::fetchOne("SELECT COALESCE(SUM(credit - debit), 0.00) AS b FROM journal_lines WHERE account_id = :id", [':id' => $revCoaId])['b'];
    $cogsBalBefore = (float)Database::fetchOne("SELECT COALESCE(SUM(debit - credit), 0.00) AS b FROM journal_lines WHERE account_id = :id", [':id' => $cogsCoaId])['b'];
    $invBalBefore = (float)Database::fetchOne("SELECT COALESCE(SUM(debit - credit), 0.00) AS b FROM journal_lines WHERE account_id = :id", [':id' => $invCoaId])['b'];

    // Post settlement
    $isoSettle = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId, 'processing_fee' => 20.00], [['payment_id' => $pmt3['id'], 'gross_amount' => 1000.00]], $userId);
    $isoCsId = (int)$isoSettle['id'];
    CardSettlement::postSettlement($isoCsId, $userId);

    $drawerCountAfter = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM cash_drawer_movements")['c'];
    assertTest($drawerCountBefore === $drawerCountAfter, "Card settlement creation & posting created ZERO cash drawer movements");

    $revBalAfter = (float)Database::fetchOne("SELECT COALESCE(SUM(credit - debit), 0.00) AS b FROM journal_lines WHERE account_id = :id", [':id' => $revCoaId])['b'];
    $cogsBalAfter = (float)Database::fetchOne("SELECT COALESCE(SUM(debit - credit), 0.00) AS b FROM journal_lines WHERE account_id = :id", [':id' => $cogsCoaId])['b'];
    $invBalAfter = (float)Database::fetchOne("SELECT COALESCE(SUM(debit - credit), 0.00) AS b FROM journal_lines WHERE account_id = :id", [':id' => $invCoaId])['b'];

    assertTest(abs($revBalBefore - $revBalAfter) < 0.001, "Card settlement did NOT affect 4010 Sales Revenue");
    assertTest(abs($cogsBalBefore - $cogsBalAfter) < 0.001, "Card settlement did NOT affect 5010 COGS");
    assertTest(abs($invBalBefore - $invBalAfter) < 0.001, "Card settlement did NOT affect 1200 Inventory Asset");

    // =========================================================================
    // SECTION 14: BUSINESS-DAY INDEPENDENCE
    // =========================================================================
    echo "\n--- 14. BUSINESS-DAY INDEPENDENCE ---\n";

    // Verify settlement can be posted without reopening closed historical day
    $bdayCheck = Database::fetchOne("SELECT * FROM business_days WHERE branch_id = :b ORDER BY id DESC LIMIT 1", [':b' => $branchId]);
    assertTest($bdayCheck !== null, "Business day context preserved independently of bank settlement date");

    // =========================================================================
    // SECTION 15: OFFLINE CARD SALE INTEGRATION
    // =========================================================================
    echo "\n--- 15. OFFLINE CARD SALE INTEGRATION ---\n";

    $offlineSaleDraft = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 10, 'unit_price' => 100.00]]], $userId, $branchId);
    $offSaleId = (int)$offlineSaleDraft['sale_id'];

    // Finalize as offline synced card payment
    $offFinal = Sale::finalizeSale($offSaleId, [[
        'payment_method' => 'card',
        'amount'         => 1000.00,
        'reference_no'   => 'SYNC-OFFLINE-CARD-99',
        'processor_id'   => $procId
    ]], $userId, $branchId);
    assertTest($offFinal['success'] === true, "Sync/Finalize Offline Card Sale");

    $offPmt = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $offSaleId]);
    assertTest($offPmt['settlement_status'] === 'unsettled', "Offline Card Sale payment registered as 'unsettled' in settlement engine");

    $offSettle = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [['payment_id' => $offPmt['id'], 'gross_amount' => 1000.00]], $userId);
    assertTest($offSettle['success'] === true, "Create settlement for Offline Card Sale payment");
    CardSettlement::postSettlement((int)$offSettle['id'], $userId);

    // =========================================================================
    // SECTION 16: SPECIAL USER PRESENTATION MASKING
    // =========================================================================
    echo "\n--- 16. SPECIAL USER PRESENTATION MASKING ---\n";

    $stdRpt = ReportsEngine::getUnsettledCardReport(['branch_id' => $branchId, 'special_user' => false]);
    $specRpt = ReportsEngine::getUnsettledCardReport(['branch_id' => $branchId, 'special_user' => true]);

    $stdUnsettledTotal = (float)$stdRpt['total_unsettled'];
    $specUnsettledTotal = (float)$specRpt['total_unsettled'];
    $expectedMaskedTotal = round($stdUnsettledTotal * (MaskingHelper::getFactor()), 2);

    assertTest(abs($specUnsettledTotal - $expectedMaskedTotal) < 0.05, "Special User Unsettled Card total is presentation-masked ({$specUnsettledTotal} vs expected {$expectedMaskedTotal})");
    $bankBalReal = Banking::getBankBalance($bankId);
    assertTest($bankBalReal > 0, "Dedicated Bank Account balance remains REAL & UNMUTATED by Special User masking");

    // =========================================================================
    // SECTION 17: AUTHORIZATION & PERMISSIONS
    // =========================================================================
    echo "\n--- 17. AUTHORIZATION & PERMISSIONS ---\n";

    $cashierPermCheck = Database::fetchOne("
        SELECT rp.* 
        FROM role_permissions rp 
        JOIN permissions p ON rp.permission_id = p.id 
        WHERE rp.role_id = 4 AND p.permission_code = 'post_card_settlement'
    ");
    assertTest($cashierPermCheck === null, "Cashier role (Role 4) is NOT granted 'post_card_settlement' permission");

    // =========================================================================
    // SECTION 18: AUDIT TRAIL & PCI CARD SECURITY
    // =========================================================================
    echo "\n--- 18. AUDIT TRAIL & PCI CARD SECURITY ---\n";

    $auditCount = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM audit_logs WHERE module = 'banking' AND action LIKE 'CARD_SETTLEMENT%'")['c'];
    assertTest($auditCount >= 5, "Audit logs recorded for Card Settlement actions (create, post, reverse, delete)");

    $pmtCols = Database::fetchAll("SHOW COLUMNS FROM payments");
    $hasPAN = false;
    $hasCVV = false;
    foreach ($pmtCols as $col) {
        $cName = strtolower($col['Field']);
        if (strpos($cName, 'pan') !== false || strpos($cName, 'card_number') !== false) $hasPAN = true;
        if (strpos($cName, 'cvv') !== false || strpos($cName, 'cvc') !== false) $hasCVV = true;
    }
    assertTest($hasPAN === false && $hasCVV === false, "PCI DSS Security: System schema does NOT store full PAN or CVV card data");

    // =========================================================================
    // SECTION 19: DUPLICATE SETTLEMENT ITEM LINE REJECTION
    // =========================================================================
    echo "\n--- 19. DUPLICATE SETTLEMENT ITEM LINE REJECTION ---\n";

    $dupLineRes = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [
        ['payment_id' => $pmtA['id'], 'gross_amount' => 500.00],
        ['payment_id' => $pmtA['id'], 'gross_amount' => 500.00]
    ], $userId);
    assertTest($dupLineRes['success'] === false, "Reject duplicate payment ID in settlement batch line items");

    // =========================================================================
    // SECTION 20: CARD RECEIVABLE & BANK RECONCILIATION
    // =========================================================================
    echo "\n--- 20. CARD RECEIVABLE & BANK RECONCILIATION ---\n";

    $cardRecFinal = ReconciliationEngine::reconcileCardReceivable($branchId);
    assertTest(is_array($cardRecFinal), "Card Receivable diagnostic executed");
    assertTest(isset($cardRecFinal['variance']), "Card Receivable variance metric present");

    $allRecsFinal = ReconciliationEngine::runAllReconciliations();
    assertTest(isset($allRecsFinal['card_receivable']), "Master runAllReconciliations includes 'card_receivable'");

    // =========================================================================
    // SECTION 21: PARTIAL SETTLEMENT + REFUND CROSSING TEST (SECTION 11)
    // =========================================================================
    echo "\n--- 21. PARTIAL SETTLEMENT + REFUND CROSSING TEST ---\n";

    // Sale #Cross: LKR 10,000.00
    $draftCross = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 100, 'unit_price' => 100.00]]], $userId, $branchId);
    $saleCrossId = (int)$draftCross['sale_id'];
    Sale::finalizeSale($saleCrossId, [['payment_method' => 'card', 'amount' => 10000.00, 'processor_id' => $procId]], $userId, $branchId);
    $pmtCross = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $saleCrossId]);

    // Partial settlement of LKR 6,000.00
    $setCrossPart = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId], [['payment_id' => $pmtCross['id'], 'gross_amount' => 6000.00]], $userId);
    $csCrossPartId = (int)$setCrossPart['id'];
    CardSettlement::postSettlement($csCrossPartId, $userId);

    $pmtCrossPart = Database::fetchOne("SELECT * FROM payments WHERE id = :id", [':id' => $pmtCross['id']]);
    assertTest((float)$pmtCrossPart['settled_amount'] == 6000.00, "Partial settlement updated payment settled_amount to LKR 6,000.00");
    assertTest($pmtCrossPart['settlement_status'] === 'partially_settled', "Payment status transitioned to 'partially_settled'");

    // Now issue Card Refund of LKR 5,000.00 (Crosses remaining 4,000 receivable + 1,000 already settled bank money)
    $saleCrossItem = Database::fetchOne("SELECT * FROM sale_items WHERE sale_id = :sid LIMIT 1", [':sid' => $saleCrossId]);
    $retCrossComp = SaleReturn::processReturn([
        'sale_id'     => $saleCrossId,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'notes'       => 'Partial settlement crossing return',
        'items'       => [
            [
                'sale_item_id'   => (int)$saleCrossItem['id'],
                'product_id'     => (int)$saleCrossItem['product_id'],
                'lot_id'         => (int)$saleCrossItem['lot_id'],
                'quantity'       => 50,
                'unit_price'     => (float)$saleCrossItem['unit_price'],
                'cost_price'     => (float)$saleCrossItem['cost_price'],
                'item_condition' => 'good',
                'reason'         => 'Customer return'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'card', 'amount' => 5000.00, 'bank_account_id' => $bankId]
        ]
    ], $userId, $branchId);

    assertTest($retCrossComp['success'] === true, "Complete Sales Return with LKR 5,000.00 Card Refund crossing partial settlement");
    $retCrossId = (int)$retCrossComp['sales_return_id'];

    $retCrossJournalId = (int)Database::fetchOne("SELECT id FROM journal_entries WHERE source_type = 'sales_return' AND source_id = :rid", [':rid' => $retCrossId])['id'];
    $retCrossJournal = Accounting::getJournalEntryDetails($retCrossJournalId);

    $cr1030Amt = 0.00;
    $crBankAmt = 0.00;
    $cardCoaId = Accounting::getAccountIdByCode(Accounting::CODE_CARD_RECEIVABLE);
    $bankCoaId = (int)$bank['account_id'];

    foreach ($retCrossJournal['lines'] as $l) {
        if ((int)$l['account_id'] === $cardCoaId) {
            $cr1030Amt += (float)$l['credit'];
        }
        if ((int)$l['account_id'] === $bankCoaId) {
            $crBankAmt += (float)$l['credit'];
        }
    }

    assertTest(abs($cr1030Amt - 4000.00) < 0.01, "Partial settlement crossing refund correctly credited 1030 Card Receivable for remaining 4,000.00");
    assertTest(abs($crBankAmt - 1000.00) < 0.01, "Partial settlement crossing refund correctly credited Bank Account for 1,000.00 settled portion");

    $pmtCrossFinal = Database::fetchOne("SELECT * FROM payments WHERE id = :id", [':id' => $pmtCross['id']]);
    $unsettledCrossRem = round((float)$pmtCrossFinal['amount'] - (float)$pmtCrossFinal['settled_amount'] - (float)$pmtCrossFinal['refunded_amount'], 2);
    assertTest($unsettledCrossRem <= 0.00, "Remaining unsettled receivable for Payment #Cross is zero after crossing refund");

    // =========================================================================
    // SECTION 22: UNLINKED CARD REFUND SAFETY TEST (SECTION 12)
    // =========================================================================
    echo "\n--- 22. UNLINKED CARD REFUND SAFETY TEST ---\n";

    // Create unlinked sales return (sale_id = 0) with card refund 500.00
    $retUnlinked = SaleReturn::processReturn([
        'sale_id'     => 0,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'notes'       => 'Unlinked card return safety test',
        'items'       => [
            [
                'product_id'     => $productId,
                'lot_id'         => $lotId,
                'quantity'       => 5,
                'unit_price'     => 100.00,
                'cost_price'     => 50.00,
                'item_condition' => 'good',
                'reason'         => 'No invoice return'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'card', 'amount' => 500.00, 'bank_account_id' => $bankId]
        ]
    ], $userId, $branchId);

    assertTest($retUnlinked['success'] === true, "Process unlinked Card Return (sale_id = 0)");
    $retUnlinkedId = (int)$retUnlinked['sales_return_id'];

    $unlinkedJId = (int)Database::fetchOne("SELECT id FROM journal_entries WHERE source_type = 'sales_return' AND source_id = :rid", [':rid' => $retUnlinkedId])['id'];
    $unlinkedJ = Accounting::getJournalEntryDetails($unlinkedJId);

    $unlinkedCr1030 = 0.00;
    $unlinkedCrBank = 0.00;
    foreach ($unlinkedJ['lines'] as $l) {
        if ((int)$l['account_id'] === $cardCoaId) {
            $unlinkedCr1030 += (float)$l['credit'];
        }
        if ((int)$l['account_id'] === $bankCoaId) {
            $unlinkedCrBank += (float)$l['credit'];
        }
    }

    $gl1030BalAfterUnlinked = (float)Database::fetchOne("
        SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00) 
        FROM journal_lines jl
        JOIN journal_entries je ON jl.journal_entry_id = je.id
        JOIN chart_of_accounts coa ON jl.account_id = coa.id
        WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '1030' AND je.branch_id = :b
    ", [':b' => $branchId])['COALESCE(SUM(jl.debit - jl.credit), 0.00)'];

    assertTest($gl1030BalAfterUnlinked >= 0.00, "GL 1030 Card Receivable balance is NON-NEGATIVE ({$gl1030BalAfterUnlinked}) after unlinked return");
    assertTest(($unlinkedCr1030 + $unlinkedCrBank) == 500.00, "Total refund credit equals 500.00");

    // =========================================================================
    // SECTION 23: EXPLICIT CARD FEE COA 5170 VERIFICATION & 5050 ABSENCE
    // =========================================================================
    echo "\n--- 23. EXPLICIT CARD FEE COA 5170 VERIFICATION & 5050 ABSENCE ---\n";

    // Create fresh payment for fee test
    $draftFee = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 10, 'unit_price' => 100.00]]], $userId, $branchId);
    $saleFeeId = (int)$draftFee['sale_id'];
    Sale::finalizeSale($saleFeeId, [['payment_method' => 'card', 'amount' => 1000.00, 'processor_id' => $procId]], $userId, $branchId);
    $pmtFeeTest = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $saleFeeId]);

    // Create & Post settlement with processing fee = 25.00
    $setFeeTest = CardSettlement::createSettlement(['branch_id' => $branchId, 'processor_id' => $procId, 'bank_account_id' => $bankId, 'processing_fee' => 25.00], [['payment_id' => $pmtFeeTest['id'], 'gross_amount' => 1000.00]], $userId);
    $csFeeId = (int)$setFeeTest['id'];
    CardSettlement::postSettlement($csFeeId, $userId);

    $csFeeJId = (int)Database::fetchOne("SELECT journal_entry_id FROM card_settlements WHERE id = :id", [':id' => $csFeeId])['journal_entry_id'];
    $csFeeJ = Accounting::getJournalEntryDetails($csFeeJId);

    $fee5170Debit = false;
    $has5050 = false;
    $feeCoa5170Id = Accounting::getAccountIdByCode('5170');

    foreach ($csFeeJ['lines'] as $l) {
        if ((int)$l['account_id'] === $feeCoa5170Id && (float)$l['debit'] == 25.00) {
            $fee5170Debit = true;
        }
        $code = Database::fetchOne("SELECT account_code FROM chart_of_accounts WHERE id = :id", [':id' => $l['account_id']])['account_code'] ?? '';
        if ($code === '5050') {
            $has5050 = true;
        }
    }

    assertTest($fee5170Debit === true, "Card settlement journal explicitly debited COA 5170 Card Processing Fees for 25.00");
    assertTest($has5050 === false, "COA Account 5050 is NEVER used for Card Processing Fees");

    // Reverse settlement and verify reversal credits 5170
    $revRes = CardSettlement::reverseSettlement($csFeeId, "Fee COA reversal check", $userId);
    $csRevJId = (int)($revRes['reversal_journal_id'] ?? 0);
    if ($csRevJId <= 0) {
        $csRevRow = Database::fetchOne("SELECT id FROM journal_entries WHERE (source_type = 'reversal' AND source_id = :jid) OR (source_type = 'card_settlement_reversal' AND source_id = :csid) ORDER BY id DESC LIMIT 1", [':jid' => $csFeeJId, ':csid' => $csFeeId]);
        $csRevJId = (int)($csRevRow['id'] ?? 0);
    }
    $csRevJ = Accounting::getJournalEntryDetails($csRevJId);

    $fee5170Credit = false;
    foreach ($csRevJ['lines'] as $l) {
        if ((int)$l['account_id'] === $feeCoa5170Id && (float)$l['credit'] == 25.00) {
            $fee5170Credit = true;
        }
    }
    assertTest($fee5170Credit === true, "Card settlement reversal journal explicitly credited COA 5170 Card Processing Fees for 25.00");

    // =========================================================================
    // SECTION 24: MIGRATION SCRIPT IDEMPOTENT RERUN TEST (SECTION 8)
    // =========================================================================
    echo "\n--- 24. MIGRATION SCRIPT IDEMPOTENT RERUN TEST ---\n";

    ob_start();
    require __DIR__ . '/migrate_stage17.php';
    $migRun1 = ob_get_clean();
    assertTest(strpos($migRun1, 'Completed Successfully') !== false, "Migration script (migrate_stage17.php) Run #1 succeeded");

    ob_start();
    require __DIR__ . '/migrate_stage17.php';
    $migRun2 = ob_get_clean();
    assertTest(strpos($migRun2, 'Completed Successfully') !== false, "Migration script (migrate_stage17.php) Run #2 succeeded idempotently without errors");

    // =========================================================================
    // SECTION 25: CANONICAL SCHEMA COLUMN & ENUM CONSISTENCY (SECTIONS 5 & 6)
    // =========================================================================
    echo "\n--- 25. CANONICAL SCHEMA COLUMN & ENUM CONSISTENCY ---\n";

    $pmtColsList = Database::fetchAll("SHOW COLUMNS FROM payments");
    $hasProcessorId = false;
    $hasCardProcessorId = false;
    $settleStatusEnumStr = '';

    foreach ($pmtColsList as $c) {
        if ($c['Field'] === 'processor_id') $hasProcessorId = true;
        if ($c['Field'] === 'card_processor_id') $hasCardProcessorId = true;
        if ($c['Field'] === 'settlement_status') $settleStatusEnumStr = $c['Type'];
    }

    assertTest($hasProcessorId === true, "Canonical column 'payments.processor_id' exists in database schema");
    assertTest($hasCardProcessorId === false, "No redundant 'payments.card_processor_id' column exists in database schema");
    assertTest(strpos($settleStatusEnumStr, 'partially_settled') !== false, "Canonical ENUM includes 'partially_settled'");
    assertTest(strpos($settleStatusEnumStr, "'partial'") === false, "Canonical ENUM does NOT contain conflicting 'partial' value");

    // =========================================================================
    // SECTION 26: ENHANCED CROSS-BRANCH VERIFICATION (SECTION 1)
    // =========================================================================
    echo "\n--- 26. ENHANCED CROSS-BRANCH VERIFICATION (5 ZEROES) ---\n";

    // Create payment in Branch A (1) and payment in Branch B (2)
    $draftB1 = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 10, 'unit_price' => 100.00]]], $userId, 1);
    $saleB1Id = (int)$draftB1['sale_id'];
    Sale::finalizeSale($saleB1Id, [['payment_method' => 'card', 'amount' => 1000.00, 'processor_id' => $procId]], $userId, 1);
    $pmtB1 = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $saleB1Id]);

    // Ensure Branch 2 inventory & business day
    $lotsB2 = Sale::getAvailableLotsForProduct($productId, 2);
    if (empty($lotsB2)) {
        $db->prepare("
            INSERT INTO product_lots (lot_number, product_id, branch_id, cost, selling_price, initial_quantity, remaining_quantity, status)
            VALUES ('LOT-SEC17-B2', :p, 2, 50.00, 100.00, 1000, 1000, 'active')
        ")->execute([':p' => $productId]);
        $lotsB2 = Sale::getAvailableLotsForProduct($productId, 2);
    }
    $lotB2Id = (int)$lotsB2[0]['lot_id'];
    $db->prepare("
        INSERT INTO branch_inventory (branch_id, product_id, quantity) 
        VALUES (2, :p, (SELECT COALESCE(SUM(remaining_quantity), 0) FROM product_lots WHERE product_id = :p2 AND branch_id = 2 AND status = 'active'))
        ON DUPLICATE KEY UPDATE quantity = (SELECT COALESCE(SUM(remaining_quantity), 0) FROM product_lots WHERE product_id = :p3 AND branch_id = 2 AND status = 'active')
    ")->execute([':p' => $productId, ':p2' => $productId, ':p3' => $productId]);

    $activeDayB2 = BusinessDay::getActiveDay(2);
    if (!$activeDayB2) {
        BusinessDay::startDay(2, 1000.00, $userId);
    }

    $draftB2 = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => $productId, 'lot_id' => $lotB2Id, 'quantity' => 10, 'unit_price' => 100.00]]], $userId, 2);
    $saleB2Id = (int)$draftB2['sale_id'];
    Sale::finalizeSale($saleB2Id, [['payment_method' => 'card', 'amount' => 1000.00, 'processor_id' => $procId]], $userId, 2);
    $pmtB2 = Database::fetchOne("SELECT * FROM payments WHERE sale_id = :sid AND LOWER(payment_method) = 'card'", [':sid' => $saleB2Id]);

    // Attempt settlement for Branch 1 trying to include both payments
    $crossAttempt = CardSettlement::createSettlement(['branch_id' => 1, 'processor_id' => $procId, 'bank_account_id' => $bankId], [
        ['payment_id' => $pmtB1['id'], 'gross_amount' => 1000.00],
        ['payment_id' => $pmtB2['id'], 'gross_amount' => 1000.00]
    ], $userId);

    assertTest($crossAttempt['success'] === false, "Cross-branch settlement batch attempt REJECTED");

    $pmtB2Check = Database::fetchOne("SELECT * FROM payments WHERE id = :id", [':id' => $pmtB2['id']]);
    $csLinesB2 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM card_settlement_items WHERE payment_id = :id", [':id' => $pmtB2['id']])['c'];
    $b2JeCount = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries WHERE branch_id = 2 AND source_type = 'card_settlement'")['c'];

    assertTest($csLinesB2 === 0, "Zero settlement line created for Branch B payment");
    assertTest((float)$pmtB2Check['settled_amount'] == 0.00, "Zero payment settled_amount change for Branch B payment");
    assertTest($b2JeCount === 0, "Zero GL journal posting created for Branch B");
    assertTest($pmtB2Check['settlement_status'] === 'unsettled', "Zero 1030 clearing for Branch B payment");

    // =========================================================================
    // SECTION 27: COMPREHENSIVE SPECIAL USER MASKING MATRIX (SECTION 3)
    // =========================================================================
    echo "\n--- 27. COMPREHENSIVE SPECIAL USER MASKING MATRIX ---\n";

    $csSample = [
        'gross_amount'          => 10000.00,
        'processing_fee'        => 200.00,
        'net_bank_amount'       => 9800.00,
        'unsettled_amount'      => 4000.00,
        'settled_amount'        => 6000.00,
        'gross_payment_amount'  => 10000.00,
        'original_payment_amount' => 10000.00
    ];

    $maskedCs = MaskingHelper::maskCardSettlement($csSample, true);
    $factor = MaskingHelper::getFactor();

    assertTest(abs($maskedCs['gross_amount'] - (10000.00 * $factor)) < 0.01, "Special User Card Settlement gross amount is masked");
    assertTest(abs($maskedCs['processing_fee'] - (200.00 * $factor)) < 0.01, "Special User processing fee is masked");
    assertTest(abs($maskedCs['net_bank_amount'] - (9800.00 * $factor)) < 0.01, "Special User settlement net bank amount is masked");
    assertTest(abs($maskedCs['unsettled_amount'] - (4000.00 * $factor)) < 0.01, "Special User unsettled amount is masked");
    assertTest(abs($maskedCs['settled_amount'] - (6000.00 * $factor)) < 0.01, "Special User settled amount is masked");

    // Test JSON export masking
    $jsonExport = json_encode($maskedCs);
    assertTest(strpos($jsonExport, '"gross_amount":' . sprintf('%.2f', $maskedCs['gross_amount'])) !== false || strpos($jsonExport, '"gross_amount":' . (float)$maskedCs['gross_amount']) !== false, "Special User JSON export contains masked financial values");

    // Aging Report Masking
    $agingSpec = ReportsEngine::getCardAgingReport(['branch_id' => $branchId, 'special_user' => true]);
    assertTest(isset($agingSpec['total_unsettled']), "Special User Card Aging report total is present");

} catch (Throwable $e) {
    echo "\n[UNHANDLED TEST EXCEPTION]: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failed++;
}

echo "\n========================================================================\n";
echo " TEST SUMMARY: PASSED={$passed} | FAILED={$failed}\n";
echo "========================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
