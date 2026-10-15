<?php
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "Applying Section 2 Database Schema Changes...\n";

    // 1. Add supplier_code to suppliers if not exists
    $cols = Database::fetchAll("DESCRIBE suppliers");
    $hasSupplierCode = false;
    foreach ($cols as $c) {
        if ($c['Field'] === 'supplier_code') {
            $hasSupplierCode = true;
            break;
        }
    }

    if (!$hasSupplierCode) {
        echo "Adding supplier_code column to suppliers...\n";
        Database::execute("ALTER TABLE suppliers ADD COLUMN supplier_code VARCHAR(50) NULL AFTER id");
        Database::execute("ALTER TABLE suppliers ADD UNIQUE INDEX idx_suppliers_code (supplier_code)");
        
        // Populate existing suppliers with supplier_code
        $suppliers = Database::fetchAll("SELECT id FROM suppliers WHERE supplier_code IS NULL OR supplier_code = '' ORDER BY id ASC");
        foreach ($suppliers as $s) {
            $code = 'SUP-' . sprintf('%04d', $s['id']);
            Database::execute("UPDATE suppliers SET supplier_code = :code WHERE id = :id", [
                ':code' => $code,
                ':id' => $s['id']
            ]);
        }
        echo "Populated supplier_code for existing suppliers.\n";
    } else {
        echo "supplier_code column already exists in suppliers.\n";
    }

    // 2. Update purchase_orders.status ENUM to include 'Partially Received'
    echo "Updating purchase_orders.status ENUM...\n";
    Database::execute("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('Draft','Approved','Partially Received','Cancelled','Completed') NOT NULL DEFAULT 'Draft'");

    // 3. Update grns.difference_reason ENUM to include 'Supplier discount'
    echo "Updating grns.difference_reason ENUM...\n";
    Database::execute("ALTER TABLE grns MODIFY COLUMN difference_reason ENUM('None','Missing items','Damaged items','Quantity difference','Price difference','Supplier discount','Other') NOT NULL DEFAULT 'None'");

    echo "Section 2 Database Migration Complete Success!\n";

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
