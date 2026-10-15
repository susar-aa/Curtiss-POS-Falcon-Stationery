<?php
/**
 * Curtiss POS - Dashboard Controller (Stage 17 Special User Integration)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/MaskingHelper.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

$user = Auth::user();
$isAllBranches = !empty($_SESSION['all_branches_view']);
$activeBranchId = $user['active_branch_id'];

// Fetch summary metrics
$branchCount = Database::fetchOne("SELECT COUNT(*) AS total FROM branches WHERE status = 'active' AND deleted_at IS NULL")['total'] ?? 0;
$userCount = Database::fetchOne("SELECT COUNT(*) AS total FROM users WHERE status = 'active' AND deleted_at IS NULL")['total'] ?? 0;
$auditCount = Auth::isSpecialUser() ? 0 : (Database::fetchOne("SELECT COUNT(*) AS total FROM audit_logs")['total'] ?? 0);

// Product Master metrics
$productCount = Database::fetchOne("SELECT COUNT(*) AS total FROM products WHERE status = 'active' AND deleted_at IS NULL")['total'] ?? 0;
$brandCount = Database::fetchOne("SELECT COUNT(*) AS total FROM brands WHERE status = 'active' AND deleted_at IS NULL")['total'] ?? 0;

// Inventory metrics (Branch-Isolated vs All Branches Consolidated)
if ($isAllBranches) {
    $branchStockTotal = (float)(Database::fetchOne("
        SELECT COALESCE(SUM(bi.quantity), 0) AS total 
        FROM branch_inventory bi
        JOIN products p ON bi.product_id = p.id
        WHERE p.deleted_at IS NULL
    ")['total'] ?? 0);

    if (MaskingHelper::isSpecialUser()) {
        $branchStockTotal = MaskingHelper::maskQuantity($branchStockTotal);
    }

    $activeLotsCount = Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM product_lots pl
        JOIN products p ON pl.product_id = p.id
        WHERE pl.remaining_quantity > 0 AND pl.status = 'active' AND p.deleted_at IS NULL
    ")['total'] ?? 0;

    $ledgerEntriesCount = Auth::isSpecialUser() ? 0 : (Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM stock_ledger sl
        JOIN products p ON sl.product_id = p.id
        WHERE p.deleted_at IS NULL
    ")['total'] ?? 0);

    $lowStockCount = Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM branch_inventory bi
        JOIN products p ON bi.product_id = p.id
        WHERE bi.quantity <= bi.low_stock_threshold AND p.deleted_at IS NULL AND p.status = 'active'
    ")['total'] ?? 0;
} else {
    $branchStockTotal = (float)(Database::fetchOne("
        SELECT COALESCE(SUM(bi.quantity), 0) AS total 
        FROM branch_inventory bi
        JOIN products p ON bi.product_id = p.id
        WHERE bi.branch_id = :branch_id AND p.deleted_at IS NULL
    ", [':branch_id' => $activeBranchId])['total'] ?? 0);

    if (MaskingHelper::isSpecialUser()) {
        $branchStockTotal = MaskingHelper::maskQuantity($branchStockTotal);
    }

    $activeLotsCount = Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM product_lots pl
        JOIN products p ON pl.product_id = p.id
        WHERE pl.branch_id = :branch_id AND pl.remaining_quantity > 0 AND pl.status = 'active' AND p.deleted_at IS NULL
    ", [':branch_id' => $activeBranchId])['total'] ?? 0;

    $ledgerEntriesCount = Auth::isSpecialUser() ? 0 : (Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM stock_ledger sl
        JOIN products p ON sl.product_id = p.id
        WHERE sl.branch_id = :branch_id AND p.deleted_at IS NULL
    ", [':branch_id' => $activeBranchId])['total'] ?? 0);

    $lowStockCount = Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM branch_inventory bi
        JOIN products p ON bi.product_id = p.id
        WHERE bi.branch_id = :branch_id AND bi.quantity <= bi.low_stock_threshold AND p.deleted_at IS NULL AND p.status = 'active'
    ", [':branch_id' => $activeBranchId])['total'] ?? 0;
}

// Fetch recent 5 audit log entries (Blocked for Special User)
$recentAudits = Auth::isSpecialUser() ? [] : Database::fetchAll("
    SELECT * FROM audit_logs 
    ORDER BY id DESC 
    LIMIT 5
");

// Expiry & Batch Management Metrics
require_once __DIR__ . '/core/ExpiryManager.php';
$expirySummary = ExpiryManager::getExpirySummary($isAllBranches ? null : (int)$activeBranchId);

View::render('dashboard/index', [
    'currentUser'        => $user,
    'isAllBranches'      => $isAllBranches,
    'activeBranchName'   => $isAllBranches ? 'All Branches' : $user['active_branch_name'],
    'activeBranchCode'   => $isAllBranches ? 'ALL' : $user['active_branch_code'],
    'branchCount'        => $branchCount,
    'userCount'          => $userCount,
    'auditCount'         => $auditCount,
    'productCount'       => $productCount,
    'brandCount'         => $brandCount,
    'branchStockTotal'   => $branchStockTotal,
    'activeLotsCount'    => $activeLotsCount,
    'ledgerEntriesCount' => $ledgerEntriesCount,
    'lowStockCount'      => $lowStockCount,
    'expirySummary'      => $expirySummary,
    'recentAudits'       => $recentAudits
], 'Dashboard - Curtiss POS');
