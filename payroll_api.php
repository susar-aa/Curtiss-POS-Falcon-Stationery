<?php
/**
 * Curtiss POS - Payroll AJAX API Endpoint
 * Handles Payroll Periods, Entry Modifications, Approvals, Payments, Advances, Remittances & Payslips.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Payroll.php';
require_once __DIR__ . '/core/Logger.php';

try {
    $currentUser = Auth::user();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in.']);
        exit;
    }

    $userId = (int)$currentUser['id'];
    $currentBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? Auth::getCurrentBranchId() ?? 1);
    $action = trim($_GET['action'] ?? $_POST['action'] ?? 'list_periods');

    // Base permission check for Payroll
    if (!Auth::hasPermission('payroll.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: Access denied to Payroll Management.']);
        exit;
    }

    switch ($action) {
        case 'list_periods':
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 25)));

            $filters = [
                'status'    => trim($_GET['status'] ?? ''),
                'search'    => trim($_GET['search'] ?? ''),
                'branch_id' => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : (Auth::hasRole(ROLE_SUPER_ADMIN) ? null : $currentBranchId)
            ];

            $res = Payroll::getPeriods($filters, $page, $limit);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_period':
            $id = (int)($_GET['id'] ?? 0);
            $period = Payroll::findPeriod($id);
            if (!$period) {
                echo json_encode(['success' => false, 'message' => 'Payroll period not found.']);
                exit;
            }
            $entries = Payroll::getPeriodEntries($id);
            echo json_encode(['success' => true, 'period' => $period, 'entries' => $entries]);
            break;

        case 'create_period':
            if (!Auth::hasPermission('payroll.create') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to create payroll periods.']);
                exit;
            }

            $input = $_POST;
            if (empty($input['branch_id']) && !Auth::hasRole(ROLE_SUPER_ADMIN)) {
                $input['branch_id'] = $currentBranchId;
            }

            $res = Payroll::createPeriod($input, $userId);
            echo json_encode($res);
            break;

        case 'get_period_entries':
            $periodId = (int)($_GET['period_id'] ?? 0);
            $entries = Payroll::getPeriodEntries($periodId);
            echo json_encode(['success' => true, 'entries' => $entries]);
            break;

        case 'update_entry':
            if (!Auth::hasPermission('payroll.create') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to edit payroll entry.']);
                exit;
            }

            $entryId = (int)($_POST['entry_id'] ?? 0);
            $res = Payroll::updateEntry($entryId, $_POST, $userId);
            echo json_encode($res);
            break;

        case 'approve_period':
            if (!Auth::hasPermission('payroll.approve') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to approve payroll periods.']);
                exit;
            }

            $periodId = (int)($_POST['period_id'] ?? 0);
            $res = Payroll::approvePeriod($periodId, $userId);
            echo json_encode($res);
            break;

        case 'record_payment':
            if (!Auth::hasPermission('payroll.pay') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to disburse salary payments.']);
                exit;
            }

            $res = Payroll::recordPayment($_POST, $userId);
            echo json_encode($res);
            break;

        case 'disburse_advance':
            if (!Auth::hasPermission('payroll.pay') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to disburse advances.']);
                exit;
            }

            $res = Payroll::disburseAdvance($_POST, $userId);
            echo json_encode($res);
            break;

        case 'list_advances':
        case 'get_advances':
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 25)));

            $filters = [
                'status'      => trim($_GET['status'] ?? ''),
                'search'      => trim($_GET['search'] ?? ''),
                'employee_id' => !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null,
                'branch_id'   => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : (Auth::hasRole(ROLE_SUPER_ADMIN) ? null : $currentBranchId)
            ];

            $res = Payroll::getAdvances($filters, $page, $limit);
            echo json_encode([
                'success'     => true,
                'data'        => $res,
                'items'       => $res['items'] ?? [],
                'total'       => $res['total'] ?? 0,
                'page'        => $res['page'] ?? 1,
                'limit'       => $res['limit'] ?? $limit,
                'total_pages' => $res['total_pages'] ?? 1
            ]);
            break;

        case 'remit_epf_etf':
            if (!Auth::hasPermission('payroll.remit') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to remit statutory contributions.']);
                exit;
            }

            $res = Payroll::remitEpfEtf($_POST, $userId);
            echo json_encode($res);
            break;

        case 'cancel_advance':
            if (!Auth::hasPermission('payroll.pay') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to cancel advances.']);
                exit;
            }

            $advanceId = (int)($_POST['advance_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? 'Cancelled by authorized user');
            $res = Payroll::cancelAdvance($advanceId, $reason, $userId);
            echo json_encode($res);
            break;

        case 'cancel_period':
            if (!Auth::hasPermission('payroll.approve') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to cancel payroll periods.']);
                exit;
            }

            $periodId = (int)($_POST['period_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? 'Cancelled by authorized user');
            $res = Payroll::cancelPeriod($periodId, $reason, $userId);
            echo json_encode($res);
            break;

        case 'get_payment_accounts':
            $branchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $currentBranchId;
            $accounts = Payroll::getEligiblePaymentAccounts($branchId);
            echo json_encode(['success' => true, 'accounts' => $accounts]);
            break;

        case 'get_employee_advance_summary':
            $employeeId = (int)($_GET['employee_id'] ?? 0);
            $res = Payroll::getEmployeeAdvanceSummary($employeeId);
            echo json_encode($res);
            break;

        case 'get_payslip':
            $entryId = (int)($_GET['entry_id'] ?? 0);
            $payslip = Payroll::getPayslip($entryId);
            if (!$payslip) {
                echo json_encode(['success' => false, 'message' => 'Payslip not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'payslip' => $payslip]);
            break;

        case 'get_period_payslips':
            $periodId = (int)($_GET['period_id'] ?? 0);
            $payslips = Payroll::getPeriodPayslips($periodId);
            echo json_encode(['success' => true, 'payslips' => $payslips]);
            break;

        case 'advance_report':
            $filters = [
                'branch_id'      => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $currentBranchId,
                'employee_id'    => !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null,
                'start_date'     => $_GET['start_date'] ?? null,
                'end_date'       => $_GET['end_date'] ?? null,
                'payment_method' => $_GET['payment_method'] ?? null,
            ];
            $res = Payroll::getAdvanceReport($filters);
            echo json_encode($res);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action '{$action}'."]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("payroll_api.php error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
