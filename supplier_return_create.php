<?php
/**
 * Curtiss POS - Supplier Return Create Controller (Stage 15)
 * Renders Excel Spreadsheet Grid UI for creating supplier returns
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Supplier.php';
require_once __DIR__ . '/core/SupplierReturnEngine.php';

Auth::requireAuth();

if (!Auth::hasPermission('create_supplier_return') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$activeBranchName = $user['active_branch_name'] ?? 'Main Branch';
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Security validation failed (Invalid or expired CSRF token). Please try again.';
    } else {
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $returnDate = trim($_POST['return_date'] ?? date('Y-m-d'));
        $notes      = trim($_POST['notes'] ?? '');
        $rawItems   = $_POST['items'] ?? [];

        $items = [];
        foreach ($rawItems as $ri) {
            $qty = (float)($ri['quantity'] ?? 0);
            if ($qty > 0) {
                $items[] = [
                    'product_id' => (int)($ri['product_id'] ?? 0),
                    'lot_id'     => !empty($ri['lot_id']) ? (int)$ri['lot_id'] : null,
                    'unit_cost'  => (float)($ri['unit_cost'] ?? 0),
                    'quantity'   => $qty,
                    'reason'     => trim($ri['reason'] ?? 'Supplier Return')
                ];
            }
        }

        $res = SupplierReturnEngine::processReturnBySupplier([
            'supplier_id' => $supplierId,
            'return_date' => $returnDate,
            'notes'       => $notes,
            'items'       => $items
        ], (int)$user['id'], $branchId);

        if ($res['success']) {
            header('Location: supplier_returns?created=1');
            exit();
        } else {
            $errorMsg = $res['message'];
        }
    }
}

$suppliers = Supplier::all(true);
$batchStats = Database::fetchAll("
    SELECT g.supplier_id,
           COUNT(DISTINCT gi.product_id) AS linked_products,
           COALESCE(SUM(CASE WHEN g.status = 'Approved' THEN (g.actual_grn_total - g.paid_amount) ELSE 0 END), 0.00) AS outstanding_payable
    FROM grns g
    LEFT JOIN grn_items gi ON g.id = gi.grn_id
    WHERE g.status = 'Approved' AND g.branch_id = :bid
    GROUP BY g.supplier_id
", [':bid' => $branchId]);
$statsMap = [];
foreach ($batchStats as $bs) {
    $statsMap[$bs['supplier_id']] = $bs;
}
foreach ($suppliers as &$s) {
    $stat = $statsMap[$s['id']] ?? null;
    $s['linked_products'] = (int)($stat['linked_products'] ?? 0);
    $s['outstanding_payable'] = max(0.00, (float)($stat['outstanding_payable'] ?? 0.00));
}
unset($s);

$nextReturnNumber = SupplierReturnEngine::generateReturnNumber();

View::render('purchasing/supplier_return_form', [
    'suppliers'        => $suppliers,
    'nextReturnNumber' => $nextReturnNumber,
    'activeBranchName' => $activeBranchName,
    'errorMsg'         => $errorMsg
], 'Create Supplier Return - Curtiss POS');
