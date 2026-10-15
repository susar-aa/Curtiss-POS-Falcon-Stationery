<?php
/**
 * Verification Test Suite - Section 3: POS Billing & Checkout Enhancements
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Database.php';

$testCount = 0;
$passedCount = 0;

function assertTest(bool $condition, string $testName, string $details = ''): void
{
    global $testCount, $passedCount;
    $testCount++;
    if ($condition) {
        $passedCount++;
        echo "  [PASS] Test #{$testCount}: {$testName}\n";
    } else {
        echo "  [FAIL] Test #{$testCount}: {$testName} - {$details}\n";
    }
}

echo "=====================================================\n";
echo "    SECTION 3: POS BILLING & CHECKOUT TEST SUITE     \n";
echo "=====================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $branchId = 1;
    $userId = 1;

    // Ensure active business day exists for test
    $activeDay = BusinessDay::getActiveDay($branchId);
    if (!$activeDay) {
        BusinessDay::startDay($branchId, 20000.00, $userId, "Section 3 Verification Test Run");
    }

    // -------------------------------------------------------------------------
    // TEST GROUP 1: PRODUCT SEARCH / BARCODE / SKU
    // -------------------------------------------------------------------------
    echo "--- Group 1: Product Search / Barcode / SKU ---\n";

    // Setup active test product & lot
    $testBarcode = '8809999888877';
    $testSku = 'SKU-SEC3-SPECIFIC';
    
    // Clean up previous test product & lots for test isolation
    $oldProd = Database::fetchOne("SELECT id FROM products WHERE barcode = :b OR sku = :s", [':b' => $testBarcode, ':s' => $testSku]);
    if ($oldProd) {
        $oldPId = (int)$oldProd['id'];
        Database::execute("SET FOREIGN_KEY_CHECKS = 0;");
        Database::execute("DELETE FROM stock_ledger WHERE product_id = :id", [':id' => $oldPId]);
        Database::execute("DELETE FROM sale_items WHERE product_id = :id", [':id' => $oldPId]);
        Database::execute("DELETE FROM grn_items WHERE product_id = :id", [':id' => $oldPId]);
        Database::execute("DELETE FROM purchase_order_items WHERE product_id = :id", [':id' => $oldPId]);
        Database::execute("DELETE FROM product_lots WHERE product_id = :id", [':id' => $oldPId]);
        Database::execute("DELETE FROM branch_inventory WHERE product_id = :id", [':id' => $oldPId]);
        Database::execute("DELETE FROM products WHERE id = :id", [':id' => $oldPId]);
        Database::execute("SET FOREIGN_KEY_CHECKS = 1;");
    }

    $validSup = Database::fetchOne("SELECT id FROM suppliers WHERE status = 'active' LIMIT 1");
    $validSupplierId = $validSup ? (int)$validSup['id'] : 1;

    Database::execute("
        INSERT INTO products (sku, barcode, name, brand_id, category_id, supplier_id, cost, selling_price, status)
        VALUES (:sku, :barcode, 'Section 3 Specific Pen', 1, 1, :supplier_id, 20.00, 35.00, 'active')
    ", [':sku' => $testSku, ':barcode' => $testBarcode, ':supplier_id' => $validSupplierId]);
    $prodId = (int)Database::lastInsertId();

    // Add exactly one lot stock for product
    $lotRes = Inventory::addStockLot($branchId, $prodId, 20.00, 35.00, 50.00, 'LOT-SEC3-SPEC', 'GRN-SEC3-SPEC', 'Section 3 Test Lot', $userId, 'opening_stock');
    $lotId = (int)$lotRes['lot_id'];

    // Search by exact barcode
    $barcodeResults = Sale::searchProducts($branchId, $testBarcode);
    assertTest(!empty($barcodeResults) && $barcodeResults[0]['product_id'] == $prodId, "Barcode search returns matching active product");

    // Search by SKU
    $skuResults = Sale::searchProducts($branchId, $testSku);
    assertTest(!empty($skuResults) && $skuResults[0]['product_id'] == $prodId, "SKU search returns matching active product");

    // Search by Name
    $nameResults = Sale::searchProducts($branchId, "Section 3 Specific Pen");
    assertTest(!empty($nameResults) && $nameResults[0]['product_id'] == $prodId, "Name search returns matching active product");

    // Inactive product blocked test
    Database::execute("UPDATE products SET status = 'inactive' WHERE id = :id", [':id' => $prodId]);
    $inactResults = Sale::searchProducts($branchId, $testBarcode);
    assertTest(empty($inactResults), "Inactive product is blocked from search results");

    // Re-activate product
    Database::execute("UPDATE products SET status = 'active' WHERE id = :id", [':id' => $prodId]);

    // -------------------------------------------------------------------------
    // TEST GROUP 2: LOT & PRICE SELECTION & ZERO-STOCK HIDING
    // -------------------------------------------------------------------------
    echo "\n--- Group 2: Lot & Price Selection & Zero-Stock Hiding ---\n";

    $availLots = Sale::getAvailableLotsForProduct($prodId, $branchId);
    assertTest(count($availLots) === 1, "Available lots fetched for product (exact 1 lot)");

    // Check single lot auto-selection in search results
    $searchAuto = Sale::searchProducts($branchId, $testSku);
    assertTest(!empty($searchAuto) && !empty($searchAuto[0]['auto_selected_lot']), "Single available lot is auto-selected");

    // Deplete lot to 0 and verify zero-stock lot is hidden
    Database::execute("UPDATE product_lots SET remaining_quantity = 0, status = 'depleted' WHERE id = :id", [':id' => $lotId]);
    $zeroLots = Sale::getAvailableLotsForProduct($prodId, $branchId);
    assertTest(empty($zeroLots), "Zero-stock/depleted lot is hidden from available lots");

    // Restore lot quantity
    Database::execute("UPDATE product_lots SET remaining_quantity = 50.00, status = 'active' WHERE id = :id", [':id' => $lotId]);

    // -------------------------------------------------------------------------
    // TEST GROUP 3: DISCOUNT SYNTAX & ROUND-UP SUGGESTIONS
    // -------------------------------------------------------------------------
    echo "\n--- Group 3: Discount Syntax & Round-Up Suggestions ---\n";

    // Fixed discount '10'
    $dFixed = Sale::parseDiscount('10', 100.00);
    assertTest($dFixed['discount_amount'] == 10.00 && $dFixed['type'] === 'fixed', "Fixed discount '10' parsed correctly");

    // Percentage discount '10%'
    $dPct = Sale::parseDiscount('10%', 150.00);
    assertTest($dPct['discount_amount'] == 15.00 && $dPct['type'] === 'percentage', "Percentage discount '10%' parsed correctly");

    // Percentage + Round-up discount '10%+0.75' on 176.94 subtotal (17.69 discount - 0.75 added = 16.94 net discount)
    $dRoundup = Sale::parseDiscount('10%+0.75', 176.94);
    assertTest($dRoundup['type'] === 'roundup' && $dRoundup['total_discount'] == 16.94, "Percentage + Round-up discount '10%+0.75' parsed correctly");

    // Round-up suggestion for 159.25 -> 160.00
    $sug = Sale::calculateRoundupSuggestion(159.25);
    assertTest($sug !== null && $sug['suggested_roundup'] == 0.75 && $sug['suggested_total'] == 160.00, "Round-up suggestion calculates 0.75 diff to next 10-value (160.00)");

    // -------------------------------------------------------------------------
    // TEST GROUP 4: CUSTOMER SELECTION & CREDIT RULES
    // -------------------------------------------------------------------------
    echo "\n--- Group 4: Customer Selection & Credit Rules ---\n";

    $walkin = Database::fetchOne("SELECT * FROM customers WHERE is_walkin = 1 LIMIT 1");
    assertTest($walkin !== null && (int)$walkin['is_walkin'] === 1, "Default Walk-in Customer exists");

    // Search registered customer
    $custRes = Customer::all(['search' => 'Walk-in', 'status' => 'active']);
    assertTest(!empty($custRes['items']), "Customer search finds registered/walk-in customer");

    // -------------------------------------------------------------------------
    // TEST GROUP 5: HOLD / PARK SALE WORKFLOW
    // -------------------------------------------------------------------------
    echo "\n--- Group 5: Hold / Park Sale Workflow ---\n";

    $holdPayload = [
        'customer_id'    => 1,
        'held_notes'     => 'Section 3 Test Hold',
        'discount_value' => '10%',
        'items'          => [
            [
                'product_id'     => $prodId,
                'lot_id'         => $lotId,
                'quantity'       => 2.00,
                'unit_price'     => 35.00,
                'original_price' => 35.00,
                'discount_value' => ''
            ]
        ]
    ];

    $holdRes = Sale::holdInvoice($holdPayload, $userId, $branchId);
    assertTest($holdRes['success'], "Hold sale created successfully");
    $heldSaleId = (int)$holdRes['sale_id'];

    // Verify stock was NOT deducted during hold
    $lotHold = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lotId]);
    assertTest((float)$lotHold['remaining_quantity'] == 50.00, "Stock was NOT deducted during hold");

    // Fetch held invoices list
    $heldList = Sale::getHeldInvoices($branchId);
    $foundHeld = false;
    foreach ($heldList as $h) {
        if ($h['sale_id'] == $heldSaleId) {
            $foundHeld = true;
            break;
        }
    }
    assertTest($foundHeld, "Held sale appears in held invoices list");

    // Resume held sale
    $resData = Sale::getSaleWithItems($heldSaleId, $branchId);
    assertTest($resData !== null && count($resData['items']) === 1, "Resume held sale loads cart items");

    // Cancel held sale
    $cancelRes = Sale::cancelInvoice($heldSaleId, $userId, $branchId, "Test Cancel Held");
    assertTest($cancelRes['success'], "Cancel held sale succeeded");

    // -------------------------------------------------------------------------
    // TEST GROUP 6: ATOMIC SALE FINALIZATION & CONTINUOUS INVOICE NUMBERING
    // -------------------------------------------------------------------------
    echo "\n--- Group 6: Atomic Sale Finalization & Double-Submit Protection ---\n";

    // Continuous invoice number generation
    $invNo1 = Sale::generateInvoiceNumber();
    assertTest(!empty($invNo1) && is_numeric($invNo1), "Continuous invoice number generated: {$invNo1}");

    // Prepare draft sale
    $draftPayload = [
        'customer_id'    => 1,
        'discount_value' => '',
        'items'          => [
            [
                'product_id'     => $prodId,
                'lot_id'         => $lotId,
                'quantity'       => 5.00,
                'unit_price'     => 35.00,
                'original_price' => 35.00,
                'discount_value' => ''
            ]
        ]
    ];

    $draftRes = Sale::prepareDraftSale($draftPayload, $userId, $branchId);
    assertTest($draftRes['success'], "Prepare draft sale succeeded");
    $finalSaleId = (int)$draftRes['sale_id'];

    // Clean up any stale test journal entry for $finalSaleId
    Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid)", [':sid' => $finalSaleId]);
    Database::execute("DELETE FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $finalSaleId]);

    // Finalize sale with cash payment
    $payAllocation = [
        ['payment_method' => 'Cash', 'amount' => 175.00, 'reference_no' => '']
    ];

    $finRes = Sale::finalizeSale($finalSaleId, $payAllocation, $userId, $branchId);
    assertTest($finRes['success'], "Sale finalized successfully (#" . ($finRes['invoice_no'] ?? '') . ")");

    // Verify stock deducted
    $lotFinal = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lotId]);
    assertTest((float)$lotFinal['remaining_quantity'] == 45.00, "Exact lot quantity deducted (50 - 5 = 45)");

    // Double-submit protection test
    $dupFinRes = Sale::finalizeSale($finalSaleId, $payAllocation, $userId, $branchId);
    assertTest(!$dupFinRes['success'], "Double-finalization blocked: " . $dupFinRes['message']);

    // Completed sale immutable test (cancel blocked)
    $canCompRes = Sale::cancelInvoice($finalSaleId, $userId, $branchId);
    assertTest(!$canCompRes['success'], "Cancelling completed invoice blocked: " . $canCompRes['message']);

    // Check Accounting Journal Entry for sale (Total Debit = Payment 175.00 + COGS 100.00 = 275.00)
    $jeSale = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $finalSaleId]);
    assertTest($jeSale !== null && (float)$jeSale['total_debit'] == 275.00, "Accounting Journal Entry posted for completed sale (Dr Cash LKR 175.00 + Dr COGS LKR 100.00 = LKR 275.00)");

    // -------------------------------------------------------------------------
    // TEST GROUP 7: RECEIPT DATA & REPRINT AUDIT
    // -------------------------------------------------------------------------
    echo "\n--- Group 7: Receipt Data & Reprint Audit ---\n";

    $receiptData = Sale::getReceiptData($finalSaleId);
    assertTest($receiptData !== null && !empty($receiptData['items']), "Receipt data fetched successfully for completed sale");

} catch (Throwable $e) {
    echo "\n  [CRITICAL FATAL EXCEPTION]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=====================================================\n";
echo " TEST SUMMARY: {$passedCount} / {$testCount} PASSED (" . round(($passedCount / max(1, $testCount)) * 100, 1) . "%)\n";
echo "=====================================================\n\n";

if ($passedCount === $testCount && $testCount > 0) {
    exit(0);
} else {
    exit(1);
}
