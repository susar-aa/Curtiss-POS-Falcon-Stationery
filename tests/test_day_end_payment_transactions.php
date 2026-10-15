<?php
/**
 * CLI Test Script: Day End Report Payment Transactions Verification
 */

define('CLI_MODE', true);
$_GET['id'] = 14;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['role_id'] = 1;
$_SESSION['branch_id'] = 1;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BusinessDay.php';

echo "=== VERIFYING DAY END REPORT & PAYMENT TRANSACTIONS ===\n";

$pdo = Database::getInstance()->getConnection();

// 1. Insert an isolated test petty cash voucher for Day 14 date (2026-10-12)
$testVoucherNo = 'PCV-TEST-' . time();
$pdo->prepare("
    INSERT INTO petty_cash_vouchers (
        voucher_no, branch_id, voucher_date, category_id, account_id,
        amount, payee, description, reference_no, status, created_by, created_at
    ) VALUES (
        :vno, 1, '2026-10-12', 1, 1,
        1000.00, 'Tea & Snacks', 'Daily Office Refreshments', 'REF-TEST-01', 'posted', 1, '2026-10-12 18:30:00'
    )
")->execute([':vno' => $testVoucherNo]);

$testVoucherId = (int)$pdo->lastInsertId();
echo "[STEP 1] Created test petty cash voucher #{$testVoucherNo} (LKR 1,000.00)\n";

try {
    // 2. Fetch comprehensive summary
    $summary = BusinessDay::getDayComprehensiveSummary(14);
    if (empty($summary['success'])) {
        echo "[FAIL] getDayComprehensiveSummary returned failure: " . ($summary['message'] ?? '') . "\n";
        exit(1);
    }
    echo "[PASS] getDayComprehensiveSummary returned successfully.\n";

    // 3. Verify sections in data
    echo "\n--- Summary Data Sections ---\n";
    echo "- Customer Collections: " . count($summary['customer_collections']['items']) . " items (Total: " . $summary['customer_collections']['total'] . ")\n";
    echo "- Supplier Payments:    " . count($summary['supplier_payments']['items']) . " items (Total: " . $summary['supplier_payments']['total'] . ")\n";
    echo "- Employee Advances:    " . count($summary['employee_advances']['items']) . " items (Total: " . $summary['employee_advances']['total'] . ")\n";
    echo "- Petty Cash Expenses:  " . count($summary['petty_cash_expenses']['items']) . " items (Total: " . $summary['petty_cash_expenses']['total'] . ")\n";
    echo "- Payroll Payments:     " . count($summary['payroll_payments']['items']) . " items (Total: " . $summary['payroll_payments']['total'] . ")\n";

    if ($summary['employee_advances']['total'] <= 0) {
        echo "[FAIL] Expected employee advances > 0 on Day 14!\n";
        exit(1);
    }

    if ($summary['petty_cash_expenses']['total'] < 1000.00) {
        echo "[FAIL] Expected petty cash expenses >= 1000.00 on Day 14!\n";
        exit(1);
    }

    // 4. Render day_closing_receipt.php
    ob_start();
    include __DIR__ . '/../day_closing_receipt.php';
    $receiptHtml = ob_get_clean();

    echo "\n--- Receipt HTML Content Checks ---\n";

    // Check Employee Advance presence
    if (strpos($receiptHtml, 'EMPLOYEE ADVANCE PAYMENTS') !== false && strpos($receiptHtml, 'Chamath') !== false) {
        echo "[PASS] Employee Advance Payments section present with employee name on receipt.\n";
    } else {
        echo "[FAIL] Employee Advance Payments section missing from receipt!\n";
        exit(1);
    }

    // Check Petty Cash presence
    if (strpos($receiptHtml, 'PETTY CASH / OPERATING EXPENSES') !== false && strpos($receiptHtml, $testVoucherNo) !== false) {
        echo "[PASS] Petty Cash / Operating Expenses section present with voucher #{$testVoucherNo} on receipt.\n";
    } else {
        echo "[FAIL] Petty Cash / Operating Expenses section missing from receipt!\n";
        exit(1);
    }

    // Check Payroll presence
    if (strpos($receiptHtml, 'PAYROLL SALARY DISBURSEMENTS') !== false) {
        echo "[PASS] Payroll Salary Disbursements section present on receipt.\n";
    } else {
        echo "[FAIL] Payroll Salary Disbursements section missing from receipt!\n";
        exit(1);
    }

    // Check Cash Reconciliation breakdown
    if (strpos($receiptHtml, '- Employee Advance Payments:') !== false && strpos($receiptHtml, '- Payroll Salary Disbursements:') !== false) {
        echo "[PASS] Cash Reconciliation correctly details advance and payroll deductions.\n";
    } else {
        echo "[FAIL] Cash Reconciliation missing advance or payroll breakdown rows!\n";
        exit(1);
    }

} finally {
    // Clean up test voucher (no table truncation, clean test record only)
    $pdo->prepare("DELETE FROM petty_cash_vouchers WHERE id = :id")->execute([':id' => $testVoucherId]);
    echo "\n[CLEANUP] Deleted test voucher ID #{$testVoucherId}.\n";
}

echo "\n[ALL CHECKS PASSED WITH EXIT CODE 0]\n";
exit(0);
