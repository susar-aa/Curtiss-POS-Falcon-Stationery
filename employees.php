<?php
/**
 * Curtiss POS - Employee Directory Controller
 * Handles Employee Master Data, Sales Rep Profiling, Soft-Deletes & Directory View.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/Employee.php';
require_once __DIR__ . '/core/Payroll.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('employees.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
    View::renderBlank('errors/403');
    exit();
}

$currentUser = Auth::user();
$currentBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? Auth::getCurrentBranchId() ?? 1);
$viewMode = $_GET['view'] ?? 'active';

// Get count of soft-deleted employees for trash bin badge
$branchFilter = Auth::hasRole(ROLE_SUPER_ADMIN) ? '' : "AND branch_id = {$currentBranchId}";
$delRow = Database::fetchOne("
    SELECT COUNT(id) AS cnt FROM employees WHERE deleted_at IS NOT NULL {$branchFilter}
");
$deletedCount = (int)($delRow['cnt'] ?? 0);

// Get branches for branch selector
$branches = Database::fetchAll("SELECT id, name, code FROM branches ORDER BY name ASC");

// Get eligible payment accounts for advance disbursement
$paymentAccounts = Payroll::getEligiblePaymentAccounts($currentBranchId);

View::render('employees/index', [
    'currentUser'     => $currentUser,
    'currentBranchId' => $currentBranchId,
    'viewMode'        => $viewMode,
    'deletedCount'    => $deletedCount,
    'branches'        => $branches,
    'paymentAccounts' => $paymentAccounts
], 'Employee Directory - Curtiss POS');
