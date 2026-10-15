<?php
/**
 * Curtiss POS - Physical Stock Audit Controller (Redesign Theme & Design Rules Enforced)
 * Queries active & completed audit sessions and delegates to inventory/audits view.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_stock_audits') && !Auth::hasPermission('inventory.view')) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_GET['branch_id'] ?? $user['active_branch_id'] ?? 1);
$statusFilter = trim($_GET['status'] ?? '');

$activeAudit = Database::fetchOne("
    SELECT * FROM stock_audits WHERE branch_id = :b AND status IN ('draft', 'counting', 'review') ORDER BY id DESC LIMIT 1
", [':b' => $branchId]);

$auditItems = [];
if ($activeAudit) {
    $auditItems = Database::fetchAll("
        SELECT sai.*, p.name AS product_name, p.barcode, pl.lot_number
        FROM stock_audit_items sai
        JOIN products p ON sai.product_id = p.id
        JOIN product_lots pl ON sai.lot_id = pl.id
        WHERE sai.audit_id = :aud_id
        ORDER BY p.name ASC, pl.id ASC
    ", [':aud_id' => $activeAudit['id']]);
}

$completedAudits = Database::fetchAll("
    SELECT sa.*, u.username AS creator_name, pu.username AS poster_name
    FROM stock_audits sa
    JOIN users u ON sa.created_by = u.id
    LEFT JOIN users pu ON sa.posted_by = pu.id
    WHERE sa.branch_id = :b AND sa.status = 'posted'
    ORDER BY sa.id DESC LIMIT 50
", [':b' => $branchId]);

View::render('inventory/audits', [
    'activeAudit'      => $activeAudit,
    'auditItems'       => $auditItems,
    'completedAudits'  => $completedAudits,
    'branchId'         => $branchId,
    'statusFilter'     => $statusFilter,
    'activeBranchName' => $user['active_branch_name'] ?? 'Current Branch'
], 'Physical Stock Audit - Curtiss POS');
