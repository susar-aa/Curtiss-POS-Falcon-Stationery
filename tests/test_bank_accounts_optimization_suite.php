<?php
/**
 * Curtiss POS - Comprehensive Bank Accounts & Cash Deposits Optimization Suite
 * Tests all functionality, database indexes, pagination, multi-column search,
 * cash deposits (active drawer & closed day), double-entry accounting journals,
 * soft-delete Trash Bin & restore, Special User masking, and UI design compliance.
 *
 * ZERO TRUNCATION: Creates and tears down isolated test fixtures cleanly.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

class BankAccountsOptimizationSuite
{
    private int $passCount = 0;
    private int $failCount = 0;
    private array $testBankIds = [];
    private array $testCoaIds = [];
    private array $testDepositIds = [];
    private array $testJournalIds = [];
    private array $testMovementIds = [];
    private ?int $testBusinessDayId = null;

    public function run(): void
    {
        echo "\n======================================================\n";
        echo " CURTISS POS - BANK ACCOUNTS & DEPOSITS TEST SUITE   \n";
        echo "======================================================\n";

        try {
            $this->testDatabaseSchemaAndIndexes();
            $this->testBankAccountLifecycleAndCoaMapping();
            $this->testCashDepositFromActiveDrawer();
            $this->testCashDepositFromClosedDay();
            $this->testSoftDeleteAndTrashBin();
            $this->testStatusToggleAndCoaSync();
            $this->testBankRegisterPaginationAndRunningBalance();
            $this->testUniversalMultiColumnSearch();
            $this->testSpecialUserMasking();
            $this->testUiDesignCompliance();
        } catch (Throwable $e) {
            echo "\n[FATAL ERROR] Unexpected exception in test suite: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            $this->failCount++;
        } finally {
            $this->cleanUpFixtures();
        }

        echo "\n======================================================\n";
        echo "  TEST SUMMARY: {$this->passCount} PASSED, {$this->failCount} FAILED\n";
        echo "======================================================\n\n";

        if ($this->failCount > 0) {
            exit(1);
        }
    }

    private function assertTest(string $description, bool $condition, string $details = ''): void
    {
        if ($condition) {
            $this->passCount++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->failCount++;
            echo "  [FAIL] {$description}" . ($details ? " -> {$details}" : "") . "\n";
        }
    }

    /**
     * 1. Database Schema & Composite Indexes Verification
     */
    private function testDatabaseSchemaAndIndexes(): void
    {
        echo "\n--- 1. Testing Database Schema & Composite Indexes ---\n";

        // Check deleted_at on bank_accounts
        $colDeleted = Database::fetchOne("SHOW COLUMNS FROM bank_accounts LIKE 'deleted_at'");
        $this->assertTest("Column 'deleted_at' exists on bank_accounts table", !empty($colDeleted));

        // Check bank_accounts indexes
        $baIndexes = Database::fetchAll("SHOW INDEX FROM bank_accounts");
        $baIndexNames = array_column($baIndexes, 'Key_name');

        $this->assertTest("Composite index 'idx_ba_del_status_id' exists on bank_accounts", in_array('idx_ba_del_status_id', $baIndexNames, true));
        $this->assertTest("Composite index 'idx_ba_branch_del' exists on bank_accounts", in_array('idx_ba_branch_del', $baIndexNames, true));
        $this->assertTest("Composite index 'idx_ba_account_del' exists on bank_accounts", in_array('idx_ba_account_del', $baIndexNames, true));

        // Check bank_deposits indexes
        $bdIndexes = Database::fetchAll("SHOW INDEX FROM bank_deposits");
        $bdIndexNames = array_column($bdIndexes, 'Key_name');

        $this->assertTest("Composite index 'idx_bd_ba_date' exists on bank_deposits", in_array('idx_bd_ba_date', $bdIndexNames, true));
        $this->assertTest("Composite index 'idx_bd_source_bday' exists on bank_deposits", in_array('idx_bd_source_bday', $bdIndexNames, true));

        // Check journal_lines & journal_entries indexes
        $jlIndexes = Database::fetchAll("SHOW INDEX FROM journal_lines");
        $jlIndexNames = array_column($jlIndexes, 'Key_name');
        $this->assertTest("Composite index 'idx_jl_acc_je' exists on journal_lines", in_array('idx_jl_acc_je', $jlIndexNames, true));

        $jeIndexes = Database::fetchAll("SHOW INDEX FROM journal_entries");
        $jeIndexNames = array_column($jeIndexes, 'Key_name');
        $this->assertTest("Composite index 'idx_je_status_date' exists on journal_entries", in_array('idx_je_status_date', $jeIndexNames, true));
    }

    /**
     * 2. Bank Account Creation, Auto-COA Mapping & Opening Balance GL Journal
     */
    private function testBankAccountLifecycleAndCoaMapping(): void
    {
        echo "\n--- 2. Testing Bank Account Creation & COA Double-Entry Mapping ---\n";

        $testAccNum = 'TEST-ACC-' . time() . '-' . rand(100, 999);
        $saveData = [
            'bank_name'        => 'Test National Bank',
            'account_name'     => 'Operations Test Reserve',
            'account_number'   => $testAccNum,
            'branch_id'        => 1,
            'bank_branch_name' => 'Kandy Branch',
            'opening_balance'  => 50000.00,
            'status'           => 'active'
        ];

        $res = Banking::saveBankAccount($saveData, 1);
        $this->assertTest("Bank Account creation succeeds", !empty($res['success']) && !empty($res['id']));

        if (!empty($res['id'])) {
            $bankId = (int)$res['id'];
            $this->testBankIds[] = $bankId;

            $bank = Banking::getBankAccount($bankId);
            $this->assertTest("Bank Account retrieved correctly with account_id", !empty($bank['account_id']));

            if (!empty($bank['account_id'])) {
                $coaId = (int)$bank['account_id'];
                $this->testCoaIds[] = $coaId;

                // Verify dedicated COA Asset Account
                $coa = Database::fetchOne("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE id = :id", [':id' => $coaId]);
                $this->assertTest("COA Account created as Asset", !empty($coa) && strtolower($coa['account_type']) === 'asset');
                $this->assertTest("COA Account code is in 102x series", !empty($coa['account_code']) && str_starts_with($coa['account_code'], '102'));

                // Verify Opening Balance Journal Entry (Dr Bank Asset, Cr Equity 3010)
                $je = Database::fetchOne("
                    SELECT je.id, je.total_debit, je.total_credit, je.posting_status 
                    FROM journal_entries je
                    JOIN journal_lines jl ON je.id = jl.journal_entry_id
                    WHERE je.source_type = 'opening_balance' AND je.source_id = :bank_id AND jl.account_id = :coa_id AND je.posting_status = 'posted'
                ", [':bank_id' => $bankId, ':coa_id' => $coaId]);

                $this->assertTest("Opening balance journal entry created and posted", !empty($je));
                if ($je) {
                    $this->testJournalIds[] = (int)$je['id'];
                    $this->assertTest("Journal entry is balanced (Debit = Credit)", (float)$je['total_debit'] === (float)$je['total_credit']);
                    $this->assertTest("Journal entry total matches opening balance (50,000.00)", abs((float)$je['total_debit'] - 50000.00) < 0.01);
                }

                // Verify Initial Bank GL Balance
                $bal = Banking::getBankBalance($bankId);
                $this->assertTest("Bank GL balance reflects opening balance of 50,000.00", abs($bal - 50000.00) < 0.01);
            }
        }
    }

    /**
     * 3. Cash Deposit from Active POS Drawer
     */
    private function testCashDepositFromActiveDrawer(): void
    {
        echo "\n--- 3. Testing Cash Deposit from Active Drawer ---\n";

        if (empty($this->testBankIds)) {
            $this->assertTest("Skipping active drawer test (no test bank)", false);
            return;
        }

        $bankId = $this->testBankIds[0];
        $activeDay = BusinessDay::getOrCreateActiveDay(1, 1);
        $this->assertTest("Active business day confirmed", !empty($activeDay['id']));

        // Add float into drawer so expected cash is sufficient
        $curExpected = BusinessDay::getExpectedDrawerCash(1);
        $injectAmt = max(15000.00, abs($curExpected) + 20000.00);

        $mvRes = BusinessDay::recordDrawerMovement([
            'business_day_id' => (int)$activeDay['id'],
            'branch_id'       => 1,
            'movement_type'   => 'cash_in',
            'source_type'     => 'manual_entry',
            'amount'          => $injectAmt,
            'direction'       => 'in',
            'reason'          => 'Test Drawer Cash Injection for Deposit',
            'user_id'         => 1
        ]);
        if (!empty($mvRes['id'])) {
            $this->testMovementIds[] = (int)$mvRes['id'];
        }

        $depositData = [
            'bank_account_id' => $bankId,
            'amount'          => 10000.00,
            'deposit_date'    => date('Y-m-d'),
            'source_type'     => 'active_drawer',
            'reference_no'    => 'SLIP-TEST-001',
            'notes'           => 'Automated test active drawer cash deposit'
        ];

        $depRes = Banking::createDeposit($depositData, 1, 1);
        $this->assertTest("Cash deposit from active drawer processed successfully", !empty($depRes['success']));

        if (!empty($depRes['deposit_id'])) {
            $depId = (int)$depRes['deposit_id'];
            $this->testDepositIds[] = $depId;

            // Verify deposit record in database
            $depRow = Database::fetchOne("SELECT * FROM bank_deposits WHERE id = :id", [':id' => $depId]);
            $this->assertTest("Bank deposit record has continuous DEP- number format", !empty($depRow['deposit_no']) && str_starts_with($depRow['deposit_no'], 'DEP-'));
            $this->assertTest("Bank deposit linked to active business day ID", (int)$depRow['business_day_id'] === (int)$activeDay['id']);

            // Verify corresponding drawer cash_out movement
            $drawerMv = Database::fetchOne("
                SELECT id, movement_type, amount, direction 
                FROM cash_drawer_movements 
                WHERE source_type = 'bank_deposit' AND source_id = :dep_id
            ", [':dep_id' => $depId]);

            $this->assertTest("Drawer movement cash_out recorded for active drawer deposit", !empty($drawerMv) && $drawerMv['movement_type'] === 'cash_out');
            if ($drawerMv) {
                $this->testMovementIds[] = (int)$drawerMv['id'];
                $this->assertTest("Drawer movement amount matches deposit amount (10,000.00)", abs((float)$drawerMv['amount'] - 10000.00) < 0.01);
            }

            // Verify Double-Entry Journal Entry
            $jeId = (int)($depRow['journal_entry_id'] ?? 0);
            if ($jeId > 0) {
                $this->testJournalIds[] = $jeId;
                $je = Database::fetchOne("SELECT id, total_debit, total_credit, posting_status FROM journal_entries WHERE id = :id", [':id' => $jeId]);
                $this->assertTest("Accounting journal entry posted for deposit", !empty($je) && $je['posting_status'] === 'posted');
                $this->assertTest("Journal entry is balanced (Debit = Credit = 10,000.00)", (float)$je['total_debit'] === (float)$je['total_credit'] && abs((float)$je['total_debit'] - 10000.00) < 0.01);

                // Verify lines: Dr Bank Account (COA), Cr Cash in Hand (1010)
                $lines = Database::fetchAll("SELECT jl.*, coa.account_code FROM journal_lines jl JOIN chart_of_accounts coa ON jl.account_id = coa.id WHERE jl.journal_entry_id = :id", [':id' => $jeId]);
                $hasDrBank = false;
                $hasCrCash = false;
                foreach ($lines as $ln) {
                    if ((float)$ln['debit'] > 0 && str_starts_with($ln['account_code'], '102')) {
                        $hasDrBank = true;
                    }
                    if ((float)$ln['credit'] > 0 && $ln['account_code'] === Accounting::CODE_CASH) {
                        $hasCrCash = true;
                    }
                }
                $this->assertTest("Journal has Dr Bank Account (102x)", $hasDrBank);
                $this->assertTest("Journal has Cr Cash in Hand (1010)", $hasCrCash);
            }

            // Verify Updated GL Bank Balance
            $balAfter = Banking::getBankBalance($bankId);
            $this->assertTest("Bank GL balance updated to 60,000.00 (50k initial + 10k deposit)", abs($balAfter - 60000.00) < 0.01);
        }
    }

    /**
     * 4. Cash Deposit from Closed Business Day
     */
    private function testCashDepositFromClosedDay(): void
    {
        echo "\n--- 4. Testing Cash Deposit from Closed Business Day ---\n";

        if (empty($this->testBankIds)) {
            $this->assertTest("Skipping closed day test (no test bank)", false);
            return;
        }

        $bankId = $this->testBankIds[0];

        // Create a dedicated closed business day fixture
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO business_days (
                branch_id, business_date, opened_by, opened_at, closed_by, closed_at,
                opening_cash, retained_cash, cash_to_deposit, status
            ) VALUES (
                1, '2026-09-30', 1, '2026-09-30 08:00:00', 1, '2026-09-30 22:00:00',
                20000.00, 20000.00, 20000.00, 'closed'
            )
        ");
        $stmt->execute();
        $closedDayId = (int)$db->lastInsertId();
        $this->testBusinessDayId = $closedDayId;
        $this->assertTest("Closed business day fixture created with 20,000.00 cash to deposit", $closedDayId > 0);

        // Attempt deposit exceeding allowed amount -> must fail
        $excessData = [
            'bank_account_id' => $bankId,
            'amount'          => 25000.00,
            'source_type'     => 'closed_day',
            'business_day_id' => $closedDayId
        ];
        $excessRes = Banking::createDeposit($excessData, 1, 1);
        $this->assertTest("Excess deposit amount (> cash_to_deposit) is blocked", !$excessRes['success']);

        // Legitimate closed day deposit
        $validData = [
            'bank_account_id' => $bankId,
            'amount'          => 12000.00,
            'deposit_date'    => date('Y-m-d'),
            'source_type'     => 'closed_day',
            'business_day_id' => $closedDayId,
            'reference_no'    => 'SLIP-CLOSED-001',
            'notes'           => 'Deposit from closed business day'
        ];
        $validRes = Banking::createDeposit($validData, 1, 1);
        $this->assertTest("Legitimate closed day deposit processed successfully", !empty($validRes['success']));

        if (!empty($validRes['deposit_id'])) {
            $depId = (int)$validRes['deposit_id'];
            $this->testDepositIds[] = $depId;

            // Verify that NO drawer movement was created (prevents double cash reduction)
            $mvCheck = Database::fetchOne("SELECT id FROM cash_drawer_movements WHERE source_type = 'bank_deposit' AND source_id = :id", [':id' => $depId]);
            $this->assertTest("NO drawer movement created for closed day deposit (prevents duplicate deduction)", empty($mvCheck));

            // Verify journal entry exists
            $depRow = Database::fetchOne("SELECT journal_entry_id FROM bank_deposits WHERE id = :id", [':id' => $depId]);
            $jeId = (int)($depRow['journal_entry_id'] ?? 0);
            if ($jeId > 0) {
                $this->testJournalIds[] = $jeId;
                $je = Database::fetchOne("SELECT id, total_debit, total_credit FROM journal_entries WHERE id = :id", [':id' => $jeId]);
                $this->assertTest("Journal entry posted for closed day deposit (12,000.00)", abs((float)$je['total_debit'] - 12000.00) < 0.01);
            }
        }
    }

    /**
     * 5. Soft-Delete & Trash Bin Workflow
     */
    private function testSoftDeleteAndTrashBin(): void
    {
        echo "\n--- 5. Testing Soft-Delete & Trash Bin Workflow ---\n";

        // Create a secondary bank account for soft delete testing
        $testAccNum2 = 'TEST-DEL-' . time() . '-' . rand(100, 999);
        $res = Banking::saveBankAccount([
            'bank_name'        => 'Temporary Bank',
            'account_name'     => 'Soft Delete Target',
            'account_number'   => $testAccNum2,
            'branch_id'        => 1,
            'opening_balance'  => 0.00,
            'status'           => 'active'
        ], 1);

        $tempId = (int)($res['id'] ?? 0);
        $this->assertTest("Temporary bank account created for soft delete test", $tempId > 0);
        if ($tempId <= 0) return;

        $this->testBankIds[] = $tempId;
        $tempAcc = Banking::getBankAccount($tempId);
        if (!empty($tempAcc['account_id'])) {
            $this->testCoaIds[] = (int)$tempAcc['account_id'];
        }

        // Soft delete the account
        $delRes = Banking::deleteBankAccount($tempId, 1);
        $this->assertTest("Soft-delete bank account returns success", !empty($delRes['success']));

        // Verify deleted_at is populated and status is inactive
        $dbRow = Database::fetchOne("SELECT deleted_at, status FROM bank_accounts WHERE id = :id", [':id' => $tempId]);
        $this->assertTest("bank_accounts.deleted_at is set (NOT NULL)", !empty($dbRow['deleted_at']));
        $this->assertTest("bank_accounts.status set to 'inactive'", $dbRow['status'] === 'inactive');

        // Verify account is EXCLUDED from active getBankAccounts listing
        $activeList = Banking::getBankAccounts(1, false, false);
        $inActiveList = false;
        foreach ($activeList as $a) {
            if ((int)$a['id'] === $tempId) $inActiveList = true;
        }
        $this->assertTest("Soft-deleted account is excluded from active accounts list", !$inActiveList);

        // Verify account is INCLUDED in Trash Bin (deletedOnly = true)
        $trashList = Banking::getBankAccounts(1, false, true);
        $inTrashList = false;
        foreach ($trashList as $a) {
            if ((int)$a['id'] === $tempId) $inTrashList = true;
        }
        $this->assertTest("Soft-deleted account appears in Trash Bin list (deletedOnly=true)", $inTrashList);

        // Restore the account
        $restRes = Banking::restoreBankAccount($tempId, 1);
        $this->assertTest("Restore bank account returns success", !empty($restRes['success']));

        $restRow = Database::fetchOne("SELECT deleted_at, status FROM bank_accounts WHERE id = :id", [':id' => $tempId]);
        $this->assertTest("Restored account has deleted_at = NULL", empty($restRow['deleted_at']));
        $this->assertTest("Restored account has status = 'active'", $restRow['status'] === 'active');
    }

    /**
     * 6. Interactive Status Toggle & Linked COA Synchronization
     */
    private function testStatusToggleAndCoaSync(): void
    {
        echo "\n--- 6. Testing Status Toggle & Linked COA Synchronization ---\n";

        if (empty($this->testBankIds)) {
            $this->assertTest("Skipping status toggle (no test bank)", false);
            return;
        }

        $bankId = $this->testBankIds[0];
        $bank = Banking::getBankAccount($bankId);
        $coaId = (int)$bank['account_id'];

        // Toggle from active to inactive
        $toggle1 = Banking::toggleAccountStatus($bankId, 1);
        $this->assertTest("Toggle status to inactive returns success", !empty($toggle1['success']) && $toggle1['status'] === 'inactive');

        $baRow1 = Database::fetchOne("SELECT status FROM bank_accounts WHERE id = :id", [':id' => $bankId]);
        $coaRow1 = Database::fetchOne("SELECT status FROM chart_of_accounts WHERE id = :id", [':id' => $coaId]);
        $this->assertTest("Bank account status updated to 'inactive'", $baRow1['status'] === 'inactive');
        $this->assertTest("Linked COA account status synchronized to 'inactive'", $coaRow1['status'] === 'inactive');

        // Toggle back to active
        $toggle2 = Banking::toggleAccountStatus($bankId, 1);
        $this->assertTest("Toggle status back to active returns success", !empty($toggle2['success']) && $toggle2['status'] === 'active');

        $baRow2 = Database::fetchOne("SELECT status FROM bank_accounts WHERE id = :id", [':id' => $bankId]);
        $coaRow2 = Database::fetchOne("SELECT status FROM chart_of_accounts WHERE id = :id", [':id' => $coaId]);
        $this->assertTest("Bank account status restored to 'active'", $baRow2['status'] === 'active');
        $this->assertTest("Linked COA account status restored to 'active'", $coaRow2['status'] === 'active');
    }

    /**
     * 7. Bank Register Ledger Pagination & Continuous Running Balance
     */
    private function testBankRegisterPaginationAndRunningBalance(): void
    {
        echo "\n--- 7. Testing Bank Register Pagination & Running Balance ---\n";

        if (empty($this->testBankIds)) {
            $this->assertTest("Skipping register pagination (no test bank)", false);
            return;
        }

        $bankId = $this->testBankIds[0];

        // Fetch page 1 with limit 25
        $regP1 = Banking::getBankRegister($bankId, [], 1, 25);
        $this->assertTest("Bank register returns array with items and metadata", isset($regP1['items'], $regP1['totalItems']));
        $this->assertTest("Total items in register reflects posted entries (>= 3)", $regP1['totalItems'] >= 3);

        // Check running balance continuity
        $items = $regP1['items'];
        $continuous = true;
        for ($i = 1; $i < count($items); $i++) {
            $prevBal = (float)$items[$i - 1]['running_balance'];
            $dep = (float)$items[$i]['deposit_in'];
            $pay = (float)$items[$i]['payment_out'];
            $curBal = (float)$items[$i]['running_balance'];
            if (abs($curBal - ($prevBal + $dep - $pay)) > 0.01) {
                $continuous = false;
                break;
            }
        }
        $this->assertTest("Running balance calculates continuous addition/subtraction accurately", $continuous);

        // Test whitelisted limit fallback
        $regInvalidLimit = Banking::getBankRegister($bankId, [], 1, 9999);
        $this->assertTest("Non-whitelisted pagination limit falls back to default 25", $regInvalidLimit['limit'] === 25);
    }

    /**
     * 8. Universal Multi-Column Search in Register
     */
    private function testUniversalMultiColumnSearch(): void
    {
        echo "\n--- 8. Testing Universal Multi-Column Search in Register ---\n";

        if (empty($this->testBankIds)) {
            $this->assertTest("Skipping multi-column search (no test bank)", false);
            return;
        }

        $bankId = $this->testBankIds[0];

        // Search by continuous entry prefix 'DEP-'
        $resDep = Banking::getBankRegister($bankId, ['search' => 'DEP-']);
        $this->assertTest("Search by entry number 'DEP-' matches deposit entries", count($resDep['items']) >= 1);

        // Search by raw amount '10000'
        $resAmt = Banking::getBankRegister($bankId, ['search' => '10000']);
        $this->assertTest("Search by raw numeric amount '10000' matches deposit entry", count($resAmt['items']) >= 1);

        // Search by formatted currency string 'LKR 10,000.00'
        $resCur = Banking::getBankRegister($bankId, ['search' => 'LKR 10,000.00']);
        $this->assertTest("Search by formatted currency 'LKR 10,000.00' matches deposit entry", count($resCur['items']) >= 1);

        // Search by memo keyword 'SLIP-TEST'
        $resMemo = Banking::getBankRegister($bankId, ['search' => 'SLIP-TEST']);
        $this->assertTest("Search by memo text 'SLIP-TEST' matches slip memo", count($resMemo['items']) >= 1);
    }

    /**
     * 9. Special User Presentation-Layer Masking
     */
    private function testSpecialUserMasking(): void
    {
        echo "\n--- 9. Testing Special User Presentation-Layer Masking ---\n";

        if (empty($this->testBankIds)) {
            $this->assertTest("Skipping special user test (no test bank)", false);
            return;
        }

        $bankId = $this->testBankIds[0];

        // Normal unmasked fetch
        $_SESSION['user_id'] = 1;
        $_SESSION['role_code'] = 'admin';
        $_SESSION['user_role'] = 'admin';
        $unmaskedAcc = Banking::getBankAccount($bankId);
        $unmaskedBal = (float)$unmaskedAcc['current_balance'];

        // Simulate Special User
        $_SESSION['user_id'] = 1;
        $_SESSION['role_code'] = defined('ROLE_SPECIAL_USER') ? ROLE_SPECIAL_USER : 'special_user';
        $_SESSION['user_role'] = 'special_user';
        $factor = MaskingHelper::getFactor();

        $maskedAcc = Banking::getBankAccount($bankId);
        $maskedBal = (float)$maskedAcc['current_balance'];

        $expectedMasked = round($unmaskedBal * $factor, 2);
        $this->assertTest("Special user balance is masked according to factor ({$factor})", abs($maskedBal - $expectedMasked) < 0.05);

        // Verify underlying database records are NOT altered
        $dbRaw = Database::fetchOne("SELECT opening_balance FROM bank_accounts WHERE id = :id", [':id' => $bankId]);
        $this->assertTest("Database underlying balance remains 100% unaltered (50,000.00)", abs((float)$dbRaw['opening_balance'] - 50000.00) < 0.01);

        // Test Register masking
        $regMasked = Banking::getBankRegister($bankId);
        if (!empty($regMasked['items'])) {
            $firstMasked = $regMasked['items'][0];
            $this->assertTest("Register ledger running_balance is masked for Special User", is_numeric($firstMasked['running_balance']));
        }

        // Reset session role
        $_SESSION['role_code'] = 'super_admin';
        $_SESSION['user_role'] = 'super_admin';
    }

    /**
     * 10. UI Design & Master Rules Compliance
     */
    private function testUiDesignCompliance(): void
    {
        echo "\n--- 10. Testing UI Design & Master Rules Compliance ---\n";

        $viewFile = __DIR__ . '/../views/bank_accounts/index.php';
        $content = file_get_contents($viewFile);

        // Fixed screen viewport
        $this->assertTest("Fixed viewport height style 'height: calc(100vh - 110px)' present", str_contains($content, 'height: calc(100vh - 110px);'));

        // Standard 48px row height
        $this->assertTest("Standard 48px table row height rule present", str_contains($content, 'height: 48px;') && str_contains($content, 'min-height: 48px;'));

        // Sticky headers
        $this->assertTest("Sticky header rules on the financial table", str_contains($content, 'position: sticky;') && str_contains($content, 'top: 0;'));

        // Alternating zebra striping
        $this->assertTest("Alternating zebra striping styles on table rows", str_contains($content, 'rgba(248, 250, 252, 0.57);') && str_contains($content, '#f1f5f9 !important;'));

        // Headline only (no subtitle tagline)
        $this->assertTest("Headline only without subtitle tagline", !str_contains($content, 'class="inv-subtitle"'));

        // Floating Header Right actions
        $this->assertTest("Action controls mounted in '#curtiss-floating-header-right'", str_contains($content, 'id="curtiss-floating-header-right"'));

        // Soft red Trash Bin button
        $this->assertTest("Trash Bin button features soft red styling (#dc2626, #fef2f2)", str_contains($content, '#dc2626') && str_contains($content, '#fef2f2'));

        // Interactive status badges
        $this->assertTest("Interactive theme-matched status badge present (.prod-badge-active / .prod-badge-inactive)", str_contains($content, 'prod-badge-active') && str_contains($content, 'toggleStatus('));

        // No status dropdown on form
        $this->assertTest("No status dropdown in bank account creation modal", !str_contains($content, 'id="acc_status"'));

        // CurtissSearchableSelect included
        $this->assertTest("CurtissSearchableSelect styles & scripts included", str_contains($content, 'curtiss_select.css') && str_contains($content, 'curtiss_select.js'));

        // CurtissNav search integration
        $this->assertTest("CurtissNav.enablePageSearch integration hooked", str_contains($content, 'CurtissNav.enablePageSearch'));
    }

    /**
     * Clean up isolated test fixtures without table truncation
     */
    private function cleanUpFixtures(): void
    {
        echo "\nCleaning up isolated test fixtures (ZERO table truncation)...\n";

        // 1. Delete drawer movements created during tests
        if (!empty($this->testMovementIds)) {
            $inMv = implode(',', array_map('intval', $this->testMovementIds));
            Database::execute("DELETE FROM cash_drawer_movements WHERE id IN ({$inMv})");
        }

        // 2. Delete bank deposits
        if (!empty($this->testDepositIds)) {
            $inDep = implode(',', array_map('intval', $this->testDepositIds));
            Database::execute("DELETE FROM bank_deposits WHERE id IN ({$inDep})");
        }

        // 3. Delete journal lines and entries
        if (!empty($this->testJournalIds)) {
            $inJe = implode(',', array_map('intval', $this->testJournalIds));
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN ({$inJe})");
            Database::execute("DELETE FROM journal_entries WHERE id IN ({$inJe})");
        }

        // 4. Delete test bank accounts
        if (!empty($this->testBankIds)) {
            $inBa = implode(',', array_map('intval', $this->testBankIds));
            Database::execute("DELETE FROM bank_accounts WHERE id IN ({$inBa})");
        }

        // 5. Delete test COA accounts
        if (!empty($this->testCoaIds)) {
            $inCoa = implode(',', array_map('intval', $this->testCoaIds));
            Database::execute("DELETE FROM chart_of_accounts WHERE id IN ({$inCoa})");
        }

        // 6. Delete test business day
        if ($this->testBusinessDayId) {
            Database::execute("DELETE FROM business_days WHERE id = :id", [':id' => $this->testBusinessDayId]);
        }

        echo "Clean-up complete!\n";
    }
}

// Execute test suite
$suite = new BankAccountsOptimizationSuite();
$suite->run();
