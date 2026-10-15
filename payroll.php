<?php
/**
 * Curtiss POS - Payroll Management Controller
 * Handles Payroll Periods, Employee Entry Calculation, Payslips, Approvals & Payments.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/Payroll.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('payroll.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
    View::renderBlank('errors/403');
    exit();
}

$currentUser = Auth::user();
$currentBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? Auth::getCurrentBranchId() ?? 1);

// Current active branch
$currentBranch = Database::fetchOne("SELECT id, name, code FROM branches WHERE id = :id", [':id' => $currentBranchId]);

// Get branches for branch selector
$branches = Database::fetchAll("SELECT id, name, code FROM branches ORDER BY name ASC");

// Get active bank accounts for payments/remittances
$bankAccounts = Database::fetchAll("
    SELECT id, bank_name, account_name, account_number 
    FROM bank_accounts 
    WHERE status = 'active'
    ORDER BY bank_name ASC, account_name ASC
");

// Get active employees of the current opened branch only
$activeEmployees = Database::fetchAll("
    SELECT id, employee_code, full_name, basic_salary, branch_id 
    FROM employees 
    WHERE status = 'active' AND deleted_at IS NULL AND branch_id = :bid
    ORDER BY employee_code ASC
", [':bid' => $currentBranchId]);

// Get eligible payment accounts (Cash Drawer, Cash in Hand, Petty Cash, Bank Accounts)
$paymentAccounts = Payroll::getEligiblePaymentAccounts($currentBranchId);

// Get available payroll periods for current branch
$availablePeriods = Database::fetchAll("
    SELECT id, period_code, period_name, status 
    FROM payroll_periods 
    WHERE status != 'cancelled' AND (branch_id = :bid OR branch_id IS NULL)
    ORDER BY start_date DESC
    LIMIT 24
", [':bid' => $currentBranchId]);

View::render('payroll/index', [
    'currentUser'      => $currentUser,
    'currentBranchId'  => $currentBranchId,
    'currentBranch'    => $currentBranch,
    'branches'         => $branches,
    'bankAccounts'     => $bankAccounts,
    'activeEmployees'  => $activeEmployees,
    'paymentAccounts'  => $paymentAccounts,
    'availablePeriods' => $availablePeriods
], 'Payroll Management - Curtiss POS');
