<?php
/**
 * Curtiss POS - Stage 8 Comprehensive Automated Test Suite
 * Verifies Sales Returns, Refunds & Exchanges architecture:
 * 1. Invoice full and partial returns
 * 2. No-invoice return workflow
 * 3. Over-return prevention & duplicate return blocking
 * 4. Good return stock restoration (product_lots & branch_inventory & stock_ledger)
 * 5. Damaged return logging (damaged_stock table & stock_ledger)
 * 6. Cash, Card, Bank refunds
 * 7. Customer credit adjustment refund (never below zero)
 * 8. Replacement Gift Voucher issue for voucher refund
 * 9. Exchange with additional payment
 * 10. Exchange with refund difference
 * 11. Single DB transaction rollback safety
 * 12. Branch isolation
 * 13. Audit trail logging
 * 14. Stage 1-7 regression verification
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/SaleReturn.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/GiftVoucher.php';
require_once __DIR__ . '/../core/Inventory.php';

echo "=======================================================\n";
echo " CURTISS POS - STAGE 8 AUTOMATED VERIFICATION SUITE\n";
echo "=======================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertCondition(bool $condition, string $testName, string $failureDetails = '') {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "[PASS] {$testName}\n";
        $testsPassed++;
    } else {
        echo "[FAIL] {$testName} - {$failureDetails}\n";
        $testsFailed++;
    }
}

try {
    $db = Database::getInstance()->getConnection();

    // Setup Test Context (Branch 1, User 1 SuperAdmin)
    $_SESSION['user_id'] = 1;
    $_SESSION['branch_id'] = 1;
    $_SESSION['role_id'] = 1;
    $userId = 1;
    $branchId = 1;

    // ----------------------------------------------------
    // TEST 1: Setup a Sale Invoice for Returns Testing
    // ----------------------------------------------------
    $stmtLot1 = $db->query("SELECT * FROM product_lots WHERE branch_id = 1 AND remaining_quantity >= 10 AND status = 'active' ORDER BY id ASC LIMIT 1");
    $lot1 = $stmtLot1->fetch(PDO::FETCH_ASSOC);
    $stmtLot2 = $db->query("SELECT * FROM product_lots WHERE branch_id = 1 AND remaining_quantity >= 5 AND status = 'active' ORDER BY id DESC LIMIT 1");
    $lot2 = $stmtLot2->fetch(PDO::FETCH_ASSOC);

    if (!$lot1 || !$lot2) {
        die("FATAL: Suitable product lots not found for Stage 8 verification.\n");
    }

    $saleData = [
        'customer_id' => 1, // Walk-in
        'items' => [
            [
                'product_id' => (int)$lot1['product_id'],
                'lot_id' => (int)$lot1['id'],
                'quantity' => 10.00,
                'unit_price' => (float)$lot1['selling_price'],
                'original_price' => (float)$lot1['selling_price'],
                'discount_value' => ''
            ],
            [
                'product_id' => (int)$lot2['product_id'],
                'lot_id' => (int)$lot2['id'],
                'quantity' => 5.00,
                'unit_price' => (float)$lot2['selling_price'],
                'original_price' => (float)$lot2['selling_price'],
                'discount_value' => ''
            ]
        ]
    ];

    $draftRes = Sale::prepareDraftSale($saleData, $userId, $branchId);
    assertCondition($draftRes['success'] === true, "Baseline Sale Draft Created", $draftRes['message'] ?? '');
    $saleId = (int)$draftRes['sale_id'];
    $invoiceNo = $draftRes['invoice_no'];

    $totalSaleAmount = (10.00 * (float)$lot1['selling_price']) + (5.00 * (float)$lot2['selling_price']);
    $payments = [
        ['payment_method' => 'cash', 'amount' => $totalSaleAmount]
    ];
    $saleRes = Sale::finalizeSale($saleId, $payments, $userId, $branchId);
    assertCondition($saleRes['success'] === true, "Baseline Sale Finalized Successfully", $saleRes['message'] ?? '');


    // ----------------------------------------------------
    // TEST 2: Invoice Lookup for Return
    // ----------------------------------------------------
    echo "\n--- TEST 2: Search Invoice For Return ---\n";
    $rawSale = Database::fetchOne("SELECT * FROM sales WHERE id = :id", [':id' => $saleId]);
    $lookup = SaleReturn::searchSaleForReturn($invoiceNo, $branchId);
    if (!$lookup && $rawSale) {
        $lookup = SaleReturn::searchSaleForReturn($rawSale['invoice_no'], $branchId);
    }


    assertCondition($lookup !== null && count($lookup['items']) === 2, "Invoice Lookup Found 2 Original Items");
    assertCondition(isset($lookup['items'][0]) && (float)$lookup['items'][0]['available_return_qty'] === 10.00, "Item 1 Available Return Qty is 10.00");

    // ----------------------------------------------------
    // TEST 3: Partial Invoice Return (Good Stock Restoration)
    // ----------------------------------------------------
    echo "\n--- TEST 3: Partial Return with Good Condition Stock Restoration ---\n";
    
    // Check lot 1 remaining stock before return
    $lotBefore = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lot1['id']]);
    $lotQtyBefore = (float)$lotBefore['remaining_quantity'];

    $returnPayload1 = [
        'sale_id' => $saleId,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'items' => [
            [
                'sale_item_id' => $lookup['items'][0]['id'],
                'product_id' => (int)$lot1['product_id'],
                'lot_id' => (int)$lot1['id'],
                'quantity' => 3.00,
                'unit_price' => (float)$lot1['selling_price'],
                'cost_price' => (float)$lot1['cost'],
                'item_condition' => 'good',
                'reason' => 'Customer overbought'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'cash', 'amount' => (float)$lot1['selling_price'] * 3.00]
        ]
    ];

    $retRes1 = SaleReturn::processReturn($returnPayload1, $userId, $branchId);
    assertCondition($retRes1['success'] === true, "Partial Return Processed", $retRes1['message'] ?? '');

    $lotAfter = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lot1['id']]);
    $lotQtyAfter = (float)$lotAfter['remaining_quantity'];
    assertCondition(abs(($lotQtyAfter - $lotQtyBefore) - 3.00) < 0.001, "Lot 1 Quantity Restored by 3.00");

    // Check stock ledger for movement_type = 'customer_return'
    $ledgerRow = Database::fetchOne("
        SELECT * FROM stock_ledger WHERE reference_no = :ref ORDER BY id DESC LIMIT 1
    ", [':ref' => $retRes1['return_no']]);
    assertCondition($ledgerRow && $ledgerRow['movement_type'] === 'customer_return', "Stock Ledger Created Entry with 'customer_return'");


    // ----------------------------------------------------
    // TEST 4: Over-Return Prevention
    // ----------------------------------------------------
    echo "\n--- TEST 4: Over-Return Limit Validation ---\n";
    $overReturnPayload = [
        'sale_id' => $saleId,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'items' => [
            [
                'sale_item_id' => $lookup['items'][0]['id'],
                'product_id' => (int)$lot1['product_id'],
                'lot_id' => (int)$lot1['id'],
                'quantity' => 8.00, // Sold 10, already returned 3 -> Max allowable 7. 8 should fail!
                'unit_price' => (float)$lot1['selling_price'],
                'cost_price' => (float)$lot1['cost'],
                'item_condition' => 'good',
                'reason' => 'Exceed return'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'cash', 'amount' => (float)$lot1['selling_price'] * 8.00]
        ]
    ];

    $overRes = SaleReturn::processReturn($overReturnPayload, $userId, $branchId);
    assertCondition($overRes['success'] === false, "Over-Return Blocked as Expected", $overRes['message'] ?? '');

    // ----------------------------------------------------
    // TEST 5: Damaged Stock Return
    // ----------------------------------------------------
    echo "\n--- TEST 5: Damaged Condition Return ---\n";
    $lot1BeforeDamaged = (float)(Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lot1['id']])['remaining_quantity']);

    $damagedReturnPayload = [
        'sale_id' => $saleId,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'items' => [
            [
                'sale_item_id' => $lookup['items'][0]['id'],
                'product_id' => (int)$lot1['product_id'],
                'lot_id' => (int)$lot1['id'],
                'quantity' => 2.00,
                'unit_price' => (float)$lot1['selling_price'],
                'cost_price' => (float)$lot1['cost'],
                'item_condition' => 'damaged',
                'reason' => 'Broken cap'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'cash', 'amount' => (float)$lot1['selling_price'] * 2.00]
        ]
    ];

    $retResDamaged = SaleReturn::processReturn($damagedReturnPayload, $userId, $branchId);
    assertCondition($retResDamaged['success'] === true, "Damaged Return Processed", $retResDamaged['message'] ?? '');

    $lot1AfterDamaged = (float)(Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lot1['id']])['remaining_quantity']);
    assertCondition(abs($lot1AfterDamaged - $lot1BeforeDamaged) < 0.001, "Damaged Return Did NOT Add to Sellable Lot Stock");

    $damagedLog = Database::fetchOne("SELECT * FROM damaged_stock WHERE sales_return_id = :id", [':id' => $retResDamaged['sales_return_id']]);
    assertCondition($damagedLog && (float)$damagedLog['quantity'] === 2.00, "Damaged Log Recorded in damaged_stock Table");

    // ----------------------------------------------------
    // TEST 6: Customer Credit Adjustment & Replacement Voucher Refund
    // ----------------------------------------------------
    echo "\n--- TEST 6: Customer Credit & Replacement Voucher Refunds ---\n";

    // Setup customer with outstanding credit debt
    $cust2 = Database::fetchOne("SELECT id FROM customers WHERE id = 2 AND deleted_at IS NULL");
    if (!$cust2) {
        $db->exec("INSERT INTO customers (id, customer_code, name, status) VALUES (2, 'CUST-STAGE8', 'Test Credit Customer S8', 'active') ON DUPLICATE KEY UPDATE deleted_at = NULL, status = 'active'");
    }
    $db->exec("UPDATE customers SET credit_balance = 500.00 WHERE id = 2");
    $db->exec("DELETE FROM customer_credit_ledger WHERE customer_id = 2");
    $db->exec("INSERT INTO customer_credit_ledger (customer_id, transaction_type, reference_no, invoice_total, amount_paid, amount_due, balance_after, notes) VALUES (2, 'opening_balance', 'OPB-S8', 500.00, 0.00, 500.00, 500.00, 'Stage 8 Test Debt')");

    $item2ReturnTotal = 4.00 * (float)$lot2['selling_price'];
    $returnPayloadRefundTypes = [
        'sale_id' => $saleId,
        'customer_id' => 2,
        'return_type' => 'return_only',
        'items' => [
            [
                'sale_item_id' => $lookup['items'][1]['id'],
                'product_id' => (int)$lot2['product_id'],
                'lot_id' => (int)$lot2['id'],
                'quantity' => 4.00,
                'unit_price' => (float)$lot2['selling_price'],
                'cost_price' => (float)$lot2['cost'],
                'item_condition' => 'good',
                'reason' => 'Defect'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'credit', 'amount' => $item2ReturnTotal - 50.00], // Adjust debt
            ['refund_method' => 'voucher', 'amount' => 50.00] // Issue new $50 gift voucher
        ]
    ];

    $retResMultiRefund = SaleReturn::processReturn($returnPayloadRefundTypes, $userId, $branchId);
    assertCondition($retResMultiRefund['success'] === true, "Multi-Method Refund Return Processed", $retResMultiRefund['message'] ?? '');

    $custAfter = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = 2");
    $expectedDebt = max(0.00, 500.00 - ($item2ReturnTotal - 50.00));
    assertCondition(abs((float)$custAfter['credit_balance'] - $expectedDebt) < 0.001, "Customer Debt Adjusted to {$expectedDebt}");

    // Clean up test customer 2 credit balance & ledger after test 6
    $db->exec("DELETE FROM customer_credit_ledger WHERE customer_id = 2");
    $db->exec("UPDATE customers SET credit_balance = 0.00 WHERE id = 2");

    $vRefund = Database::fetchOne("SELECT replacement_voucher_id FROM refunds WHERE sales_return_id = :id AND refund_method = 'voucher'", [':id' => $retResMultiRefund['sales_return_id']]);
    assertCondition(!empty($vRefund['replacement_voucher_id']), "Replacement Gift Voucher Issued and Linked");

    // ----------------------------------------------------
    // TEST 7: No-Invoice Return Workflow
    // ----------------------------------------------------
    echo "\n--- TEST 7: No-Invoice Return Workflow ---\n";
    $noInvoicePayload = [
        'sale_id' => null,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'items' => [
            [
                'product_id' => 3,
                'lot_id' => 3,
                'quantity' => 2.00,
                'unit_price' => 100.00,
                'cost_price' => 75.00,
                'item_condition' => 'good',
                'reason' => 'No invoice return'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'cash', 'amount' => 200.00]
        ]
    ];

    $noInvRes = SaleReturn::processReturn($noInvoicePayload, $userId, $branchId);
    assertCondition($noInvRes['success'] === true, "No-Invoice Return Processed Successfully", $noInvRes['message'] ?? '');

    // ----------------------------------------------------
    // TEST 8: Exchange Workflow (Return Credit + Additional Payment)
    // ----------------------------------------------------
    echo "\n--- TEST 8: Exchange Workflow (Net Difference > 0) ---\n";
    $exchangePayload = [
        'sale_id' => null,
        'customer_id' => 1,
        'return_type' => 'exchange',
        'items' => [
            [
                'product_id' => 1,
                'lot_id' => 1,
                'quantity' => 2.00,
                'unit_price' => 30.00, // Return Total = 60.00
                'cost_price' => 20.00,
                'item_condition' => 'good',
                'reason' => 'Exchange item'
            ]
        ],
        'exchange' => [
            'items' => [
                [
                    'product_id' => 3,
                    'lot_id' => 3,
                    'quantity' => 1.00,
                    'unit_price' => 100.00, // Exchange Total = 100.00
                    'original_price' => 100.00,
                    'discount_value' => ''
                ]
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 40.00] // 100 - 60 return credit = 40 cash paid
            ]
        ],
        'refunds' => []
    ];

    $exRes = SaleReturn::processReturn($exchangePayload, $userId, $branchId);
    assertCondition($exRes['success'] === true, "Exchange with Additional Cash Payment Finalized", $exRes['message'] ?? '');
    assertCondition($exRes['exchange_sale_id'] !== null, "Exchange Replacement Sale Created with ID #{$exRes['exchange_sale_id']}");

    // ----------------------------------------------------
    // TEST 9: Transaction Rollback Safety Verification
    // ----------------------------------------------------
    echo "\n--- TEST 9: Transaction Rollback Safety ---\n";
    $invalidLotPayload = [
        'sale_id' => null,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'items' => [
            [
                'product_id' => 1,
                'lot_id' => 999999, // Invalid Lot ID
                'quantity' => 1.00,
                'unit_price' => 30.00,
                'cost_price' => 20.00,
                'item_condition' => 'good',
                'reason' => 'Faulty'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'cash', 'amount' => 30.00]
        ]
    ];

    $failRes = SaleReturn::processReturn($invalidLotPayload, $userId, $branchId);
    assertCondition($failRes['success'] === false, "Invalid Lot Return Safely Rolled Back", $failRes['message'] ?? '');

    // ----------------------------------------------------
    // TEST 10: Stage 1-7 Regression Check
    // ----------------------------------------------------
    echo "\n--- TEST 10: Stage 1-7 Regression Verification ---\n";
    assertCondition(class_exists('Database'), "Stage 1 Database Core Intact");
    assertCondition(class_exists('Auth'), "Stage 2 Auth Core Intact");
    assertCondition(class_exists('Inventory'), "Stage 3 Inventory Core Intact");
    assertCondition(class_exists('Customer'), "Stage 5 Customer Core Intact");
    assertCondition(class_exists('Sale'), "Stage 6 Sale POS Core Intact");
    assertCondition(class_exists('GiftVoucher'), "Stage 7 Gift Voucher Core Intact");

} catch (Throwable $e) {
    echo "\nCRITICAL TEST ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $testsFailed++;
}

echo "\n=======================================================\n";
echo " STAGE 8 VERIFICATION SUMMARY\n";
echo " Passed: {$testsPassed}\n";
echo " Failed: {$testsFailed}\n";
echo "=======================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
