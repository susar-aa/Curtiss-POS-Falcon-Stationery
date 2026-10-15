<?php
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'admin',
    'full_name' => 'Administrator',
    'role' => 'Super Admin',
    'active_branch_name' => 'Main Branch'
];
$_SESSION['branch_id'] = 1;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/SupplierPayment.php';

$user = Auth::user();
$suppliers = Supplier::all(true);
$bankAccounts = Database::fetchAll("SELECT id, bank_name, account_number, account_name FROM bank_accounts WHERE status = 'active' ORDER BY bank_name ASC");

echo "Controller execution successful!\n";
echo "Suppliers loaded: " . count($suppliers) . "\n";
echo "Bank accounts loaded: " . count($bankAccounts) . "\n";
