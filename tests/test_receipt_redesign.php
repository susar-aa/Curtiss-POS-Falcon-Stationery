<?php
/**
 * Curtiss POS - Bill Receipt Redesign Verification Suite
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Settings.php';

$passed = 0;
$failed = 0;
$testsRun = 0;

function assertTest(bool $condition, string $testName, string $details = '') {
    global $passed, $failed, $testsRun;
    $testsRun++;
    if ($condition) {
        $passed++;
        echo "[PASS] {$testsRun}. {$testName}\n";
    } else {
        $failed++;
        echo "[FAIL] {$testsRun}. {$testName} - {$details}\n";
    }
}

echo "=== TESTING BILL RECEIPT REDESIGN ===\n\n";

// 1. Find a completed sale
$lastSale = Database::fetchOne("SELECT id, invoice_no, total_amount, subtotal FROM sales WHERE status = 'completed' ORDER BY id DESC LIMIT 1");
assertTest(!empty($lastSale), "Found completed sale in database for receipt testing");

if ($lastSale) {
    $saleId = (int)$lastSale['id'];
    $receiptData = Sale::getReceiptData($saleId);

    // 2. Verify getReceiptData structure
    assertTest(isset($receiptData['gross_subtotal']), "Sale::getReceiptData contains gross_subtotal: " . ($receiptData['gross_subtotal'] ?? 'MISSING'));
    assertTest(isset($receiptData['item_discount_total']), "Sale::getReceiptData contains item_discount_total: " . ($receiptData['item_discount_total'] ?? 'MISSING'));
    assertTest(isset($receiptData['invoice_discount_total']), "Sale::getReceiptData contains invoice_discount_total: " . ($receiptData['invoice_discount_total'] ?? 'MISSING'));
    assertTest(isset($receiptData['total_savings']), "Sale::getReceiptData contains total_savings: " . ($receiptData['total_savings'] ?? 'MISSING'));

    // 3. Render receipt.php into buffer
    $_SESSION['user_id'] = 1;
    $_SESSION['role_id'] = 1;
    $_SESSION['role_code'] = 'super_admin';
    $_SESSION['branch_id'] = 1;
    $_GET['sale_id'] = $saleId;

    ob_start();
    include __DIR__ . '/../receipt.php';
    $html = ob_get_clean();

    assertTest(!empty($html), "receipt.php rendered output successfully");

    // 4. Check System Info
    $branchInfo = Settings::getBranchInfo(1);
    assertTest(
        strpos($html, htmlspecialchars($branchInfo['company_name'])) !== false,
        "Receipt shows company name: {$branchInfo['company_name']}"
    );
    assertTest(
        strpos($html, htmlspecialchars($branchInfo['branch_name'])) !== false,
        "Receipt shows branch name: {$branchInfo['branch_name']}"
    );
    assertTest(
        strpos($html, htmlspecialchars($branchInfo['branch_phone'])) !== false || strpos($html, htmlspecialchars($branchInfo['company_phone'])) !== false,
        "Receipt shows phone contact info"
    );

    // 5. Check Developer Credit
    assertTest(
        strpos($html, 'www.trycurtiss.com') !== false,
        "Receipt includes developer credit 'www.trycurtiss.com'"
    );

    // 6. Check Alignments and Table Structure
    assertTest(
        strpos($html, 'class="table-items"') !== false,
        "Receipt contains structured table-items layout"
    );
    assertTest(
        strpos($html, 'class="table-totals"') !== false,
        "Receipt contains structured table-totals breakdown"
    );
    assertTest(
        strpos($html, 'tabular-nums') !== false,
        "Receipt uses tabular-nums for vertical alignment"
    );

    // 8. Test a sale with discount if available
    $discSale = Database::fetchOne("SELECT id FROM sales WHERE discount_amount > 0 AND status = 'completed' LIMIT 1");
    if ($discSale) {
        $_GET['sale_id'] = (int)$discSale['id'];
        ob_start();
        include __DIR__ . '/../receipt.php';
        $discHtml = ob_get_clean();
        assertTest(
            strpos($discHtml, 'TOTAL SAVINGS') !== false,
            "Receipt renders TOTAL SAVINGS banner when discount is present"
        );
        assertTest(
            strpos($discHtml, 'Bill Discount') !== false,
            "Receipt renders Bill Discount row"
        );
        assertTest(
            strpos($discHtml, '15 Off Gaming Laptop:') === false,
            "Receipt DOES NOT print promotion rule offer name"
        );
        assertTest(
            strpos($discHtml, '| Disc:') === false && strpos($discHtml, '| Promo') === false,
            "Receipt does not print barcode or verbose promo prefix"
        );
    }

    // 9. Test inline item-wise discount display
    $itemDiscSale = Database::fetchOne("SELECT s.id FROM sales s JOIN sale_items si ON s.id = si.sale_id WHERE (si.discount_amount > 0 OR si.promo_discount_amount > 0) AND s.status = 'completed' LIMIT 1");
    if ($itemDiscSale) {
        $_GET['sale_id'] = (int)$itemDiscSale['id'];
        ob_start();
        include __DIR__ . '/../receipt.php';
        $itemDiscHtml = ob_get_clean();
        assertTest(
            strpos($itemDiscHtml, 'class="item-inline-disc"') !== false,
            "Receipt renders item discount inline with class 'item-inline-disc'"
        );
        preg_match('/<div class="item-title">.*?<\/div>/s', $itemDiscHtml, $itemMatch);
        $snippet = $itemMatch[0] ?? 'NO_MATCH';
        assertTest(
            preg_match('/<div class="item-title">.*?<span class="item-inline-disc">\(-\s*[A-Za-z]*\s*[\d,.]+\)<\/span>\s*<\/div>/s', $itemDiscHtml) === 1,
            "Item discount is rendered inline directly inside .item-title",
            "Actual snippet: " . $snippet
        );
    }
}

echo "\n=========================================\n";
echo "RECEIPT TESTS: Total: {$testsRun} | Passed: {$passed} | Failed: {$failed}\n";
echo "=========================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
