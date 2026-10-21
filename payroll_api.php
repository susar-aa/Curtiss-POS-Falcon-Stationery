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
require_once __DIR__ . '/core/MaskingHelper.php';

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

    // CSRF token validation on state-mutating requests
    $mutatingActions = [
        'create_period', 'update_entry', 'approve_period', 'record_payment', 
        'disburse_advance', 'remit_epf_etf', 'cancel_advance', 'cancel_period'
    ];
    if (in_array($action, $mutatingActions, true)) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        if (!empty($token) && !Auth::validateCsrfToken($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            exit;
        }
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

            if (MaskingHelper::isSpecialUser() && !empty($res['items'])) {
                foreach ($res['items'] as &$p) {
                    $p['total_basic']        = MaskingHelper::maskValue((float)$p['total_basic']);
                    $p['total_allowances']   = MaskingHelper::maskValue((float)$p['total_allowances']);
                    $p['total_commissions']  = MaskingHelper::maskValue((float)$p['total_commissions']);
                    $p['total_gross']        = MaskingHelper::maskValue((float)$p['total_gross']);
                    $p['total_employee_epf'] = MaskingHelper::maskValue((float)$p['total_employee_epf']);
                    $p['total_employer_epf'] = MaskingHelper::maskValue((float)$p['total_employer_epf']);
                    $p['total_employer_etf'] = MaskingHelper::maskValue((float)$p['total_employer_etf']);
                    $p['total_deductions']   = MaskingHelper::maskValue((float)$p['total_deductions']);
                    $p['total_net_salary']   = MaskingHelper::maskValue((float)$p['total_net_salary']);
                    $p['total_paid']         = MaskingHelper::maskValue((float)$p['total_paid']);
                }
                unset($p);
            }

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

            if (MaskingHelper::isSpecialUser()) {
                if ($period) {
                    $period['total_basic']        = MaskingHelper::maskValue((float)$period['total_basic']);
                    $period['total_allowances']   = MaskingHelper::maskValue((float)$period['total_allowances']);
                    $period['total_commissions']  = MaskingHelper::maskValue((float)$period['total_commissions']);
                    $period['total_gross']        = MaskingHelper::maskValue((float)$period['total_gross']);
                    $period['total_employee_epf'] = MaskingHelper::maskValue((float)$period['total_employee_epf']);
                    $period['total_employer_epf'] = MaskingHelper::maskValue((float)$period['total_employer_epf']);
                    $period['total_employer_etf'] = MaskingHelper::maskValue((float)$period['total_employer_etf']);
                    $period['total_deductions']   = MaskingHelper::maskValue((float)$period['total_deductions']);
                    $period['total_net_salary']   = MaskingHelper::maskValue((float)$period['total_net_salary']);
                    $period['total_paid']         = MaskingHelper::maskValue((float)$period['total_paid']);
                }
                if (!empty($entries)) {
                    foreach ($entries as &$e) {
                        $e['basic_salary']             = MaskingHelper::maskValue((float)$e['basic_salary']);
                        $e['allowances']               = MaskingHelper::maskValue((float)$e['allowances']);
                        $e['bonuses']                  = MaskingHelper::maskValue((float)$e['bonuses']);
                        $e['commissions']              = MaskingHelper::maskValue((float)$e['commissions']);
                        $e['overtime_amount']          = MaskingHelper::maskValue((float)$e['overtime_amount']);
                        $e['gross_salary']             = MaskingHelper::maskValue((float)$e['gross_salary']);
                        $e['employee_epf_amount']      = MaskingHelper::maskValue((float)$e['employee_epf_amount']);
                        $e['salary_advances_deducted'] = MaskingHelper::maskValue((float)$e['salary_advances_deducted']);
                        $e['other_deductions']         = MaskingHelper::maskValue((float)$e['other_deductions']);
                        $e['total_deductions']         = MaskingHelper::maskValue((float)$e['total_deductions']);
                        $e['net_salary']               = MaskingHelper::maskValue((float)$e['net_salary']);
                        $e['employer_epf_amount']      = MaskingHelper::maskValue((float)$e['employer_epf_amount']);
                        $e['employer_etf_amount']      = MaskingHelper::maskValue((float)$e['employer_etf_amount']);
                        $e['paid_amount']              = MaskingHelper::maskValue((float)$e['paid_amount']);
                        if (isset($e['period_sales'])) {
                            $e['period_sales']         = MaskingHelper::maskValue((float)$e['period_sales']);
                        }
                    }
                    unset($e);
                }
            }

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

            if (MaskingHelper::isSpecialUser() && !empty($res['items'])) {
                foreach ($res['items'] as &$adv) {
                    $adv['amount']           = MaskingHelper::maskValue((float)$adv['amount']);
                    $adv['recovered_amount'] = MaskingHelper::maskValue((float)$adv['recovered_amount']);
                    $adv['remaining_amount'] = MaskingHelper::maskValue((float)$adv['remaining_amount']);
                    if (isset($adv['basic_salary'])) {
                        $adv['basic_salary'] = MaskingHelper::maskValue((float)$adv['basic_salary']);
                    }
                }
                unset($adv);
            }

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
            if (MaskingHelper::isSpecialUser() && !empty($res)) {
                if (isset($res['basic_salary'])) {
                    $res['basic_salary'] = MaskingHelper::maskValue((float)$res['basic_salary']);
                }
                if (isset($res['remaining_balance'])) {
                    $res['remaining_balance'] = MaskingHelper::maskValue((float)$res['remaining_balance']);
                }
                if (isset($res['total_advances'])) {
                    $res['total_advances'] = MaskingHelper::maskValue((float)$res['total_advances']);
                }
            }
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

        case 'get_advance_details':
        case 'get_advance':
            $advId = (int)($_GET['id'] ?? $_GET['advance_id'] ?? 0);
            $adv = Database::fetchOne("
                SELECT 
                    ea.*,
                    e.employee_code,
                    e.full_name AS employee_name,
                    e.basic_salary,
                    e.job_role,
                    e.phone AS employee_phone,
                    b.name AS branch_name,
                    u.full_name AS created_by_name,
                    cu.full_name AS cancelled_by_name,
                    pp.period_name AS recovery_period_name,
                    pp.period_code AS recovery_period_code,
                    coa.account_name AS source_account_name,
                    ba.bank_name,
                    ba.account_number
                FROM employee_advances ea
                JOIN employees e ON ea.employee_id = e.id
                JOIN branches b ON ea.branch_id = b.id
                LEFT JOIN users u ON ea.created_by = u.id
                LEFT JOIN users cu ON ea.cancelled_by = cu.id
                LEFT JOIN payroll_periods pp ON ea.recovery_period_id = pp.id
                LEFT JOIN chart_of_accounts coa ON ea.source_account_id = coa.id
                LEFT JOIN bank_accounts ba ON ea.bank_account_id = ba.id
                WHERE ea.id = :id
            ", [':id' => $advId]);

            if (!$adv) {
                echo json_encode(['success' => false, 'message' => 'Salary advance not found.']);
                exit;
            }

            if (MaskingHelper::isSpecialUser()) {
                $adv['amount']           = MaskingHelper::maskValue((float)$adv['amount']);
                $adv['recovered_amount'] = MaskingHelper::maskValue((float)$adv['recovered_amount']);
                $adv['remaining_amount'] = MaskingHelper::maskValue((float)$adv['remaining_amount']);
                $adv['basic_salary']     = MaskingHelper::maskValue((float)$adv['basic_salary']);
            }

            echo json_encode(['success' => true, 'advance' => $adv]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action '{$action}'."]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("payroll_api.php error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
