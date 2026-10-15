<?php
/**
 * Curtiss POS - Comprehensive Seed Data Generator Script
 * Generates 10 items per section across Brands, Categories, Suppliers, Products, Lots, Branch Inventory, Customers, POs, Vouchers, etc.
 */

require_once __DIR__ . '/../config/config.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbName = DB_NAME;

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "=========================================================\n";
    echo "  CURTISS POS - POPULATING FAKE DATASET (10 PER SECTION)  \n";
    echo "=========================================================\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 1. Brands (10 Items)
    echo "1. Seeding Brands (10)... ";
    $brands = [
        'Lego', 'Fisher-Price', "Carter's", 'Chicco', 'Pampers',
        'Huggies', 'Barbie', 'Hot Wheels', 'Disney Baby', 'Gerber'
    ];
    foreach ($brands as $b) {
        $stmt = $pdo->prepare("INSERT INTO brands (name, status) VALUES (:name, 'active') ON DUPLICATE KEY UPDATE name=VALUES(name)");
        $stmt->execute(['name' => $b]);
    }
    echo "SUCCESS\n";

    // 2. Categories (10 Items)
    echo "2. Seeding Categories (10)... ";
    $categories = [
        'Baby Clothing', 'Kids Footwear', 'Educational Toys', 'Strollers & Prams', 'Baby Care & Hygiene',
        'Feeding & Nursing', 'Nursery Furniture', 'Action Figures & Dolls', 'Outdoor Play', 'Books & Puzzles'
    ];
    foreach ($categories as $c) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, status) VALUES (:name, 'active') ON DUPLICATE KEY UPDATE name=VALUES(name)");
        $stmt->execute(['name' => $c]);
    }
    echo "SUCCESS\n";

    // 3. Suppliers (10 Items)
    echo "3. Seeding Suppliers (10)... ";
    $suppliersData = [
        ['Kidsland Distributors', '0112345671', '0771234561', '12 Galle Road, Colombo 03'],
        ['Island Baby Wholesale', '0112345672', '0771234562', '45 Kandy Road, Kiribathgoda'],
        ['Lanka Toy Imports', '0112345673', '0771234563', '88 Main Street, Pettah, Colombo'],
        ['BabyCare Lanka (Pvt) Ltd', '0112345674', '0771234564', '102 High Level Road, Maharagama'],
        ['Royal Kids Trading', '0112345675', '0771234565', '15 Negombo Road, Ja-Ela'],
        ['Apex Children World', '0112345676', '0771234566', '77 Peradeniya Road, Kandy'],
        ['Ceylon Baby Traders', '0112345677', '0771234567', '23 Matara Road, Galle'],
        ['Smart Kids Suppliers', '0112345678', '0771234568', '56 Havelock Road, Colombo 05'],
        ['Global Toy Importers', '0112345679', '0771234569', '34 Duplication Road, Colombo 04'],
        ['Prime Infant Solutions', '0112345680', '0771234570', '90 Station Road, Nugegoda']
    ];
    foreach ($suppliersData as $s) {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, whatsapp, address, status) VALUES (:name, :phone, :wa, :addr, 'active') ON DUPLICATE KEY UPDATE name=VALUES(name)");
        $stmt->execute(['name' => $s[0], 'phone' => $s[1], 'wa' => $s[2], 'addr' => $s[3]]);
    }
    echo "SUCCESS\n";

    // 4. Products (10 Items)
    echo "4. Seeding Products & Prices (10)... ";
    $productsData = [
        ['883001', 1, 3, 'Lego Classic Creative Bricks Box (484 Pcs)', 1, 4500.00, 6800.00, 50.00, 10.00],
        ['883002', 2, 3, 'Fisher-Price Deluxe Kick & Play Piano Gym', 2, 8500.00, 12500.00, 30.00, 5.00],
        ['883003', 3, 1, 'Carter\'s Cotton Baby Bodysuit 3-Pack (0-6M)', 3, 2200.00, 3800.00, 80.00, 15.00],
        ['883004', 4, 4, 'Chicco Echo Compact Lightweight Stroller (Black)', 4, 38000.00, 52000.00, 12.00, 3.00],
        ['883005', 5, 5, 'Pampers Premium Care Diapers Size 3 (60 Pants)', 5, 4200.00, 5600.00, 100.00, 20.00],
        ['883006', 6, 5, 'Huggies Gentle Care Baby Wipes (80 Sheets)', 6, 650.00, 950.00, 150.00, 30.00],
        ['883007', 7, 8, 'Barbie 3-in-1 Dreamcamper Transforming Playset', 7, 18500.00, 26000.00, 15.00, 4.00],
        ['883008', 8, 8, 'Hot Wheels 10-Car Gift Die-Cast Vehicle Pack', 8, 3500.00, 5200.00, 60.00, 10.00],
        ['883009', 9, 6, 'Disney Baby Natural Glass Feeding Bottle 250ml', 9, 1800.00, 2900.00, 70.00, 15.00],
        ['883010', 10, 6, 'Gerber Organic Grain & Fruit Baby Cereal 227g', 10, 1400.00, 2100.00, 90.00, 20.00]
    ];

    foreach ($productsData as $p) {
        $stmt = $pdo->prepare("
            INSERT INTO products (barcode, brand_id, category_id, name, supplier_id, cost, selling_price, opening_stock, low_stock_threshold, status)
            VALUES (:barcode, :brand, :cat, :name, :supp, :cost, :price, :stock, :low, 'active')
            ON DUPLICATE KEY UPDATE selling_price=VALUES(selling_price), cost=VALUES(cost)
        ");
        $stmt->execute([
            'barcode' => $p[0], 'brand' => $p[1], 'cat' => $p[2], 'name' => $p[3],
            'supp' => $p[4], 'cost' => $p[5], 'price' => $p[6], 'stock' => $p[7], 'low' => $p[8]
        ]);
        $prodId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM products WHERE barcode='{$p[0]}'")->fetchColumn();

        // 5. Branch Inventory & Product Lots (10 Price Lots)
        $pdo->exec("
            INSERT INTO branch_inventory (branch_id, product_id, quantity, low_stock_threshold)
            VALUES (1, {$prodId}, {$p[7]}, {$p[8]})
            ON DUPLICATE KEY UPDATE quantity={$p[7]}
        ");

        $lotNum = 'LOT-2026-' . str_pad($prodId, 3, '0', STR_PAD_LEFT);
        $pdo->exec("
            INSERT INTO product_lots (lot_number, product_id, branch_id, cost, selling_price, initial_quantity, remaining_quantity, status)
            VALUES ('{$lotNum}', {$prodId}, 1, {$p[5]}, {$p[6]}, {$p[7]}, {$p[7]}, 'active')
            ON DUPLICATE KEY UPDATE remaining_quantity={$p[7]}
        ");
    }
    echo "SUCCESS (Products & Price Lots Created)\n";

    // 6. Customers (10 Items)
    echo "5. Seeding Customers (10)... ";
    $customersData = [
        ['CUST-001', 'Nimal Perera', '0771112233', '0771112233', '15 Galle Road, Dehiwala', 'CARD-001', 25000.00, 4500.00],
        ['CUST-002', 'Kasun Fernando', '0772223344', '0772223344', '88 Kandy Road, Kelaniya', 'CARD-002', 30000.00, 0.00],
        ['CUST-003', 'Dilhani Silva', '0773334455', '0773334455', '42 School Lane, Nugegoda', 'CARD-003', 15000.00, 2800.00],
        ['CUST-004', 'Nuwan Jayasinghe', '0774445566', '0774445566', '99 Station Road, Homagama', 'CARD-004', 50000.00, 12500.00],
        ['CUST-005', 'Ruwani de Alwis', '0775556677', '0775556677', '23 Park Street, Colombo 02', 'CARD-005', 20000.00, 0.00],
        ['CUST-006', 'Sanjeewa Bandara', '0776667788', '0776667788', '67 Main Street, Kandy', 'CARD-006', 40000.00, 8900.00],
        ['CUST-007', 'Thilini Rathnayake', '0777778899', '0777778899', '14 Temple Road, Battaramulla', 'CARD-007', 25000.00, 0.00],
        ['CUST-008', 'Chathura Rodrigo', '0778889900', '0778889900', '55 Beach Road, Negombo', 'CARD-008', 35000.00, 6200.00],
        ['CUST-009', 'Anusha Peiris', '0779990011', '0779990011', '31 Lake Road, Kurunegala', 'CARD-009', 10000.00, 0.00],
        ['CUST-010', 'Dinesh Wickramasinghe', '0770001122', '0770001122', '78 Cross Street, Galle', 'CARD-010', 45000.00, 15000.00]
    ];
    foreach ($customersData as $cust) {
        $stmt = $pdo->prepare("
            INSERT INTO customers (customer_code, name, phone, whatsapp, address, card_barcode, credit_limit, credit_balance, status)
            VALUES (:code, :name, :phone, :wa, :addr, :card, :limit, :bal, 'active')
            ON DUPLICATE KEY UPDATE name=VALUES(name), credit_balance=VALUES(credit_balance)
        ");
        $stmt->execute([
            'code' => $cust[0], 'name' => $cust[1], 'phone' => $cust[2], 'wa' => $cust[3],
            'addr' => $cust[4], 'card' => $cust[5], 'limit' => $cust[6], 'bal' => $cust[7]
        ]);
    }
    echo "SUCCESS\n";

    // 7. Gift Vouchers (10 Items)
    echo "6. Seeding Gift Vouchers (10)... ";
    for ($i = 1; $i <= 10; $i++) {
        $vCode = 'GV-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT);
        $amount = $i * 1000.00;
        $pdo->exec("
            INSERT INTO gift_vouchers (voucher_code, amount, status, issue_date, expiry_date, created_by)
            VALUES ('{$vCode}', {$amount}, 'active', CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 6 MONTH), 1)
            ON DUPLICATE KEY UPDATE amount=VALUES(amount)
        ");
    }
    echo "SUCCESS\n";

    // 8. Bank Accounts (3 Items)
    echo "7. Seeding Bank Accounts (3)... ";
    $banks = [
        ['Commercial Bank Corporate', '8001234567', 'Commercial Bank', 'Main Branch City', 3],
        ['Hatton National Bank Operations', '7009876543', 'HNB', 'City Office', 3],
        ['Sampath Bank Business', '6004567890', 'Sampath Bank', 'Corporate Branch', 3]
    ];
    foreach ($banks as $b) {
        $pdo->exec("
            INSERT INTO bank_accounts (account_name, account_number, bank_name, bank_branch_name, status, account_id, branch_id)
            VALUES ('{$b[0]}', '{$b[1]}', '{$b[2]}', '{$b[3]}', 'active', {$b[4]}, 1)
            ON DUPLICATE KEY UPDATE account_name=VALUES(account_name)
        ");
    }
    echo "SUCCESS\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n=========================================================\n";
    echo " FAKE DATASET INITIALIZATION COMPLETE (10 ITEMS PER SECTION)\n";
    echo "=========================================================\n";

} catch (Throwable $e) {
    echo "SEEDING ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
