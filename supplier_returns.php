<?php
/**
 * Curtiss POS - Supplier Purchase Returns Controller (Stage 15)
 * Allows searching suppliers, selecting approved GRNs, processing returns
 * with exact lot cost preservation, and tracking return history.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/SupplierReturnEngine.php';

Auth::requireLogin();
if (!Auth::hasPermission('view_supplier_returns') && !Auth::hasPermission('create_supplier_return') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit;
}

$user = Auth::user();
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

// Fetch active suppliers with correct supplier_code column (excluding soft-deleted)
$suppliers = Database::fetchAll("SELECT id, name, supplier_code, phone FROM suppliers WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");

// Fetch return history with optional server-side search and supplier filter
$filters = ['branch_id' => $branchId];
if (!empty($_GET['search'])) {
    $filters['search'] = trim($_GET['search']);
}
if (!empty($_GET['supplier_id'])) {
    $filters['supplier_id'] = (int)$_GET['supplier_id'];
}

$history = SupplierReturnEngine::getReturnHistory($filters);

View::render('purchasing/supplier_returns', [
    'suppliers' => $suppliers,
    'history'   => $history,
    'currentSearch' => $_GET['search'] ?? ''
], 'Supplier Purchase Returns - Curtiss POS');
