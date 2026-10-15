<?php
/**
 * Curtiss POS - Employee Management AJAX API Endpoint
 * Handles Employee CRUD, Status Toggle, Soft-Delete / Restore, Sales Rep Lookup & Security Masking.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Employee.php';
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
    $action = trim($_GET['action'] ?? $_POST['action'] ?? 'list');

    // Check if user has permission to view sensitive salary info
    $canViewSalary = Auth::hasPermission('payroll.view') || Auth::hasPermission('employees.manage') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT);

    switch ($action) {
        case 'list':
            if (!Auth::hasPermission('employees.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Access denied to Employee Directory.']);
                exit;
            }

            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 25)));
            $view  = trim($_GET['view'] ?? 'active');

            $filters = [
                'view'      => $view,
                'search'    => trim($_GET['search'] ?? ''),
                'status'    => trim($_GET['status'] ?? ''),
                'job_role'  => trim($_GET['job_role'] ?? ''),
                'branch_id' => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : (Auth::hasRole(ROLE_SUPER_ADMIN) ? null : $currentBranchId)
            ];

            $res = Employee::all($filters, $page, $limit);

            // If user cannot view salary, mask basic salary
            if (!$canViewSalary) {
                foreach ($res['items'] as &$item) {
                    $item['basic_salary'] = null;
                }
            }

            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid employee ID.']);
                exit;
            }

            $emp = Employee::find($id, true);
            if (!$emp) {
                echo json_encode(['success' => false, 'message' => 'Employee not found.']);
                exit;
            }

            if (!$canViewSalary) {
                $emp['basic_salary'] = null;
            }

            echo json_encode(['success' => true, 'employee' => $emp]);
            break;

        case 'create':
            if (!Auth::hasPermission('employees.manage') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to create employees.']);
                exit;
            }

            $input = $_POST;
            if (empty($input['branch_id'])) {
                $input['branch_id'] = $currentBranchId;
            }

            $res = Employee::create($input);
            echo json_encode($res);
            break;

        case 'update':
            if (!Auth::hasPermission('employees.manage') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to update employees.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid employee ID for update.']);
                exit;
            }

            $res = Employee::update($id, $_POST);
            echo json_encode($res);
            break;

        case 'toggle_status':
            if (!Auth::hasPermission('employees.manage') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to change status.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid employee ID.']);
                exit;
            }

            $res = Employee::toggleStatus($id);
            echo json_encode($res);
            break;

        case 'delete':
            if (!Auth::hasPermission('employees.manage') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to delete employee.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid employee ID.']);
                exit;
            }

            $res = Employee::delete($id);
            echo json_encode($res);
            break;

        case 'restore':
            if (!Auth::hasPermission('employees.manage') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges to restore employee.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid employee ID.']);
                exit;
            }

            $res = Employee::restore($id);
            echo json_encode($res);
            break;

        case 'sales_reps':
            // Sales Reps can be listed by cashiers and POS screen
            $branchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $currentBranchId;
            $reps = Employee::getSalesReps($branchId);
            echo json_encode(['success' => true, 'sales_reps' => $reps]);
            break;

        case 'sales_performance':
            $id = (int)($_GET['id'] ?? 0);
            $from = !empty($_GET['from']) ? trim($_GET['from']) : null;
            $to   = !empty($_GET['to']) ? trim($_GET['to']) : null;
            $perf = Employee::getSalesPerformance($id, $from, $to);
            echo json_encode(['success' => true, 'performance' => $perf]);
            break;

        case 'payroll_ledger':
            if (!$canViewSalary) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden: Cannot view payroll ledger.']);
                exit;
            }
            $id = (int)($_GET['id'] ?? 0);
            $ledger = Payroll::getEmployeePayrollLedger($id);
            echo json_encode(['success' => true, 'ledger' => $ledger]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action '{$action}'."]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("employee_api.php error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
