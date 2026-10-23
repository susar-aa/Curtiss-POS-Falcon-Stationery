<?php
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/config/config.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Database.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Sale.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Accounting.php';

// Fetch an existing product and lot
$product = Database::fetchOne("SELECT p.id, p.selling_price, pl.id as lot_id, pl.cost FROM products p JOIN product_lots pl ON p.id = pl.product_id WHERE p.status = 'active' LIMIT 1");
if (!$product) {
    die("No active product found.\n");
}

$origPrice = (float)$product['selling_price'];
$overriddenPrice = $origPrice + 1.00; // e.g. 310 -> 311

echo "Testing Sale Price Override with Product ID {$product['id']}:\n";
echo "Original Price: LKR {$origPrice}\n";
echo "Overridden Price: LKR {$overriddenPrice}\n";

$userId = 1;
$branchId = 1;

$draftPayload = [
    'customer_id'   => 1,
    'sales_rep_id'  => null,
    'discount_str'  => '',
    'items' => [
        [
            'product_id'     => (int)$product['id'],
            'lot_id'         => (int)$product['lot_id'],
            'quantity'       => 1,
            'unit_price'     => $overriddenPrice,
            'original_price' => $origPrice,
            'discount_value' => ''
        ]
    ]
];

$draftRes = Sale::prepareDraftSale($draftPayload, $userId, $branchId);
if (!$draftRes['success']) {
    die("Draft creation failed: " . $draftRes['message'] . "\n");
}
$saleId = $draftRes['sale_id'];
echo "Draft Sale created (ID: $saleId, Total: LKR {$draftRes['total_amount']})\n";

$finalizePayments = [
    [
        'payment_method'  => 'Cash',
        'amount'          => $overriddenPrice,
        'reference_no'    => '',
        'bank_account_id' => null,
        'tendered'        => $overriddenPrice
    ]
];

$finalRes = Sale::finalizeSale($saleId, $finalizePayments, $userId, $branchId);
echo "Finalize Result:\n";
print_r($finalRes);

if ($finalRes['success']) {
    // Check journal entries for this sale
    $je = Database::fetchOne("SELECT * FROM journal_entries WHERE reference_no = :ref ORDER BY id DESC LIMIT 1", [':ref' => $finalRes['invoice_no']]);
    if ($je) {
        $lines = Database::fetchAll("SELECT jl.*, coa.account_code, coa.account_name FROM journal_lines jl JOIN chart_of_accounts coa ON jl.account_id = coa.id WHERE jl.journal_entry_id = :je_id", [':je_id' => $je['id']]);
        echo "Journal Lines for Entry #{$je['id']} (Invoice {$finalRes['invoice_no']}):\n";
        $totalDr = 0; $totalCr = 0;
        foreach ($lines as $l) {
            $dr = (float)$l['debit'];
            $cr = (float)$l['credit'];
            $totalDr += $dr;
            $totalCr += $cr;
            echo "  [{$l['account_code']}] {$l['account_name']}: Dr " . number_format($dr, 2) . " | Cr " . number_format($cr, 2) . " ({$l['memo']})\n";
        }
        echo "Total Debit: " . number_format($totalDr, 2) . " | Total Credit: " . number_format($totalCr, 2) . "\n";
        if (abs($totalDr - $totalCr) < 0.001) {
            echo "✓ JOURNAL ENTRY IS PERFECTLY BALANCED!\n";
        } else {
            echo "✗ JOURNAL ENTRY IS UNBALANCED!\n";
        }
    }
}
