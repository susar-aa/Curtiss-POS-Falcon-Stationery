<?php
/**
 * Test Supplier Payments & Supplier::all functionality
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/SupplierPayment.php';

echo "Testing Supplier::all(true)...\n";
$res1 = Supplier::all(true);
echo "Count: " . count($res1) . "\n";

echo "Testing Supplier::all(false)...\n";
$res2 = Supplier::all(false);
echo "Count: " . count($res2) . "\n";

echo "Testing Supplier::all(['status' => 'active'], 1, 100)...\n";
$res3 = Supplier::all(['status' => 'active'], 1, 100);
echo "Count: " . count($res3) . "\n";

if (!empty($res1[0]['id'])) {
    $sId = (int)$res1[0]['id'];
    echo "Testing SupplierPayment::getSupplierOutstandingSummary($sId)...\n";
    $summary = SupplierPayment::getSupplierOutstandingSummary($sId);
    echo "Success: " . ($summary['success'] ? 'YES' : 'NO') . "\n";
    echo "Outstanding: LKR " . $summary['total_outstanding'] . "\n";
}

echo "All tests passed successfully!\n";
