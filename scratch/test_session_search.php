<?php
define('CURTISS_POS', true);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';

// Test Branch 1
$_SESSION['user_id'] = 1;
$_SESSION['branch_id'] = 1;

$branchId1 = Auth::getCurrentBranchId();
$prods1 = Sale::searchProducts($branchId1, 'atlas');
echo "Branch 1 (ID: {$branchId1}) search 'atlas': " . count($prods1) . " items returned\n";

// Test Branch 2
$_SESSION['branch_id'] = 2;
$branchId2 = Auth::getCurrentBranchId();
$prods2 = Sale::searchProducts($branchId2, 'atlas');
echo "Branch 2 (ID: {$branchId2}) search 'atlas': " . count($prods2) . " items returned\n";

// Test Customer Search 'te'
$custs = Customer::all(['search' => 'te', 'status' => 'active'], 1, 10);
echo "Customer search 'te': " . count($custs['items']) . " items returned\n";
