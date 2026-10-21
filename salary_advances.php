<?php
/**
 * Curtiss POS - Salary Advances Management Controller
 * Dedicated page for employee advance disbursements, tracking, 
 * approval, cancellation, and reporting.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/Payroll.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireAuth();

if (!Auth::hasPermission('payroll.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
    View::renderBlank('errors/403');
    exit();
}

$currentUser = Auth::user();
$currentBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? Auth::getCurrentBranchId() ?? 1);

// Active branch info
$currentBranch = Database::fetchOne("SELECT id, name, code FROM branches WHERE id = :id", [':id' => $currentBranchId]);

// Get branches for branch selector
$branches = Database::fetchAll("SELECT id, name, code FROM branches ORDER BY name ASC");

// Active employees of the current opened branch
$activeEmployees = Database::fetchAll("
    SELECT id, employee_code, full_name, basic_salary, branch_id 
    FROM employees 
    WHERE status = 'active' AND deleted_at IS NULL AND branch_id = :bid
    ORDER BY employee_code ASC
", [':bid' => $currentBranchId]);

if (MaskingHelper::isSpecialUser()) {
    foreach ($activeEmployees as &$ae) {
        $ae['basic_salary'] = MaskingHelper::maskValue((float)$ae['basic_salary']);
    }
    unset($ae);
}

// Eligible payment accounts (Cash Drawer 1012, Cash in Hand 1010, Petty Cash 1015, Bank Accounts)
$paymentAccounts = Payroll::getEligiblePaymentAccounts($currentBranchId);

// Available payroll periods for recovery scheduling
$availablePeriods = Database::fetchAll("
    SELECT id, period_code, period_name, status 
    FROM payroll_periods 
    WHERE status != 'cancelled' AND (branch_id = :bid OR branch_id IS NULL)
    ORDER BY start_date DESC
    LIMIT 24
", [':bid' => $currentBranchId]);

View::render('salary_advances/index', [
    'currentUser'      => $currentUser,
    'currentBranchId'  => $currentBranchId,
    'currentBranch'    => $currentBranch,
    'branches'         => $branches,
    'activeEmployees'  => $activeEmployees,
    'paymentAccounts'  => $paymentAccounts,
    'availablePeriods' => $availablePeriods
], 'Salary Advances - Curtiss POS');
