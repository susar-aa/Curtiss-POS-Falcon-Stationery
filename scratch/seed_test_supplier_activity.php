<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/SupplierOpeningBalance.php';
require_once __DIR__ . '/../core/SupplierPayment.php';

echo "=== SEEDING TEST SUPPLIER ACTIVITY DATA ===\n";

try {
    $db = Database::getInstance()->getConnection();

    // 1. Ensure test supplier "New Vendor Gamma" (SUP-TEST-99) exists
    $sup = Database::fetchOne("SELECT * FROM suppliers WHERE supplier_code = 'SUP-TEST-99'");
    if (!$sup) {
        Database::execute("
            INSERT INTO suppliers (supplier_code, name, phone, whatsapp, address, status)
            VALUES ('SUP-TEST-99', 'New Vendor Gamma', '+94 77 999 8888', '+94 77 999 8888', '123 Gamma Industrial Zone, Colombo 03', 'active')
        ");
        $supId = (int)Database::lastInsertId();
        echo "Created supplier 'New Vendor Gamma' (#{$supId})\n";
    } else {
        $supId = (int)$sup['id'];
        echo "Found supplier 'New Vendor Gamma' (#{$supId})\n";
    }

    // 2. Add an Opening Balance if not active
    $activeOb = SupplierOpeningBalance::getActiveOpeningBalance($supId);
    if (!$activeOb) {
        $obRes = SupplierOpeningBalance::postOpeningBalance([
            'supplier_id' => $supId,
            'amount' => 150000.00,
            'balance_type' => 'owe_supplier',
            'entry_date' => date('Y-m-d', strtotime('-10 days')),
            'reference_no' => 'OB-GAMMA-001',
            'notes' => 'Initial Supplier Opening Balance'
        ], 1, 1);
        echo "Posted Opening Balance: " . $obRes['message'] . "\n";
    } else {
        echo "Opening Balance already active (#{$activeOb['id']})\n";
    }

    // 3. Add Sample Purchase Order
    $poCheck = Database::fetchOne("SELECT id FROM purchase_orders WHERE supplier_id = :id AND po_number = 'PO-GAMMA-1001'", [':id' => $supId]);
    if (!$poCheck) {
        Database::execute("
            INSERT INTO purchase_orders (po_number, supplier_id, branch_id, order_date, expected_date, status, total_amount, discount_amount, net_amount, notes, created_by, created_at)
            VALUES ('PO-GAMMA-1001', :sup_id, 1, :order_date, :del_date, 'Approved', 80000.00, 0.00, 80000.00, 'Test Purchase Order for Stationery items', 1, NOW())
        ", [
            ':sup_id' => $supId,
            ':order_date' => date('Y-m-d', strtotime('-7 days')),
            ':del_date' => date('Y-m-d', strtotime('-5 days'))
        ]);
        echo "Created Purchase Order PO-GAMMA-1001\n";
    }

    // 4. Add Sample GRN
    $grnCheck = Database::fetchOne("SELECT id FROM grns WHERE supplier_id = :id AND grn_number = 'GRN-GAMMA-2001'", [':id' => $supId]);
    if (!$grnCheck) {
        Database::execute("
            INSERT INTO grns (grn_number, po_id, supplier_id, branch_id, supplier_invoice_no, supplier_invoice_date, received_date, status, actual_grn_total, paid_amount, payment_status, notes, created_by, created_at)
            VALUES ('GRN-GAMMA-2001', NULL, :sup_id, 1, 'INV-GAMMA-9901', :inv_date, :rec_date, 'Approved', 10000.00, 0.00, 'unpaid', 'Received 20 boxes paper', 1, NOW())
        ", [
            ':sup_id' => $supId,
            ':inv_date' => date('Y-m-d', strtotime('-4 days')),
            ':rec_date' => date('Y-m-d', strtotime('-3 days'))
        ]);
        echo "Created GRN GRN-GAMMA-2001\n";
    }

    // 5. Add Sample Supplier Payment
    $pmtCheck = Database::fetchOne("SELECT id FROM supplier_payments WHERE supplier_id = :id AND payment_no = 'SPAY-GAMMA-3001'", [':id' => $supId]);
    if (!$pmtCheck) {
        Database::execute("
            INSERT INTO supplier_payments (payment_no, supplier_id, branch_id, payment_date, payment_method, bank_name, reference_no, total_amount, notes, created_by, created_at)
            VALUES ('SPAY-GAMMA-3001', :sup_id, 1, :pay_date, 'Bank', 'Commercial Bank', 'TRF-990011', 25000.00, 'Part payment for opening balance', 1, NOW())
        ", [
            ':sup_id' => $supId,
            ':pay_date' => date('Y-m-d', strtotime('-2 days'))
        ]);
        echo "Created Supplier Payment SPAY-GAMMA-3001\n";
    }

    // 6. Add Sample Supplier Return
    $retCheck = Database::fetchOne("SELECT id FROM supplier_returns WHERE supplier_id = :id AND return_number = 'SRET-GAMMA-4001'", [':id' => $supId]);
    if (!$retCheck) {
        $grnRow = Database::fetchOne("SELECT id FROM grns WHERE supplier_id = :id LIMIT 1", [':id' => $supId]);
        $grnIdVal = $grnRow ? (int)$grnRow['id'] : 1;
        Database::execute("
            INSERT INTO supplier_returns (return_number, grn_id, supplier_id, branch_id, return_date, total_return_amount, applied_to_payable, credit_amount, notes, created_by, created_at)
            VALUES ('SRET-GAMMA-4001', :grn_id, :sup_id, 1, :ret_date, 5000.00, 5000.00, 0.00, 'Damaged goods returned to vendor', 1, NOW())
        ", [
            ':grn_id' => $grnIdVal,
            ':sup_id' => $supId,
            ':ret_date' => date('Y-m-d', strtotime('-1 days'))
        ]);
        echo "Created Supplier Return SRET-GAMMA-4001\n";
    }

    echo "=== SEEDING COMPLETED SUCCESSFULLY! ===\n";

} catch (Throwable $e) {
    echo "Error seeding test data: " . $e->getMessage() . "\n";
}
