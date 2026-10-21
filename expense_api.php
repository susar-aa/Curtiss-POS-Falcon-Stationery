<?php
/**
 * Curtiss POS - Centralized Expense Management API Endpoint
 * 
 * Provides JSON REST API actions for:
 * - Querying expenses with multi-column universal search and server-side pagination
 * - Dashboard KPI statistics
 * - Expense voucher creation (draft and direct post)
 * - Draft updates, GL posting, and reversals
 * - Payments recording against unpaid/partially paid bills
 * - Document/receipt attachment uploads
 * - CSV exporting
 * - Printable voucher data
 * - Active categories and eligible payment account lists
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Expense.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireLogin();
$user     = Auth::user();
$userId   = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Block mutations for view-only Special Users
$mutatingActions = ['create', 'update_draft', 'post', 'reverse', 'record_payment', 'reverse_payment', 'delete_draft', 'restore_draft', 'upload_attachment'];
if (in_array($action, $mutatingActions, true) && Auth::isSpecialUser()) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}

try {
    switch ($action) {

        case 'list':
            if (!Auth::hasPermission('expenses.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = in_array((int)($_GET['limit'] ?? 25), [25, 50, 75, 100, 500]) ? (int)$_GET['limit'] : 25;

            $filters = [
                'branch_id'      => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId,
                'category_id'    => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
                'status'         => !empty($_GET['status']) ? trim($_GET['status']) : null,
                'payment_method' => !empty($_GET['payment_method']) ? trim($_GET['payment_method']) : null,
                'date_from'      => !empty($_GET['date_from']) ? trim($_GET['date_from']) : null,
                'date_to'        => !empty($_GET['date_to']) ? trim($_GET['date_to']) : null,
                'search'         => !empty($_GET['search']) ? trim($_GET['search']) : null,
                'view'           => (!empty($_GET['view']) && $_GET['view'] === 'deleted') ? 'deleted' : 'active'
            ];

            // If superadmin selects "all" (branch_id = 0 or empty string)
            if (isset($_GET['branch_id']) && $_GET['branch_id'] === 'all') {
                $filters['branch_id'] = null;
            }

            $res = Expense::getExpenses($filters, $page, $limit);
            echo json_encode($res);
            break;

        case 'get':
            if (!Auth::hasPermission('expenses.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid expense ID.']);
                exit;
            }

            $expense = Expense::getExpense($id);
            if (!$expense) {
                echo json_encode(['success' => false, 'message' => 'Expense record not found.']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $expense]);
            break;

        case 'stats':
            if (!Auth::hasPermission('expenses.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $reqBranch = isset($_GET['branch_id']) && is_numeric($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $dateFrom  = !empty($_GET['date_from']) ? trim($_GET['date_from']) : null;
            $dateTo    = !empty($_GET['date_to']) ? trim($_GET['date_to']) : null;

            $stats = Expense::getDashboardSummary($reqBranch, $dateFrom, $dateTo);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'create':
            if (!Auth::hasPermission('expenses.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create expenses.']);
                exit;
            }

            $rawMethod = trim($_POST['payment_method'] ?? $_POST['payment_account_key'] ?? Expense::METHOD_CASH_IN_HAND);
            $rawBankId = !empty($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : null;
            [$pMethod, $bAccountId] = Expense::normalizePaymentSource($rawMethod, $rawBankId);

            $payload = [
                'branch_id'       => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $branchId,
                'expense_date'    => trim($_POST['expense_date'] ?? date('Y-m-d')),
                'category_id'     => (int)($_POST['category_id'] ?? 0),
                'account_id'      => !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null,
                'amount'          => round((float)($_POST['amount'] ?? 0), 2),
                'payment_method'  => $pMethod,
                'bank_account_id' => $bAccountId,
                'payee'           => trim($_POST['payee'] ?? ''),
                'description'     => trim($_POST['description'] ?? ''),
                'vendor_supplier' => trim($_POST['vendor_supplier'] ?? ''),
                'invoice_ref'     => trim($_POST['invoice_ref'] ?? ''),
                'reference_no'    => trim($_POST['reference_no'] ?? ''),
                'notes'           => trim($_POST['notes'] ?? ''),
                'status'          => (!empty($_POST['status']) && $_POST['status'] === Expense::STATUS_DRAFT) ? Expense::STATUS_DRAFT : Expense::STATUS_POSTED
            ];

            $res = Expense::createExpense($payload, $userId);

            // Handle optional file attachment upon creation
            if ($res['success'] && !empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                Expense::addAttachment((int)$res['expense_id'], $_FILES['attachment'], $userId);
            }

            echo json_encode($res);
            break;

        case 'update_draft':
            if (!Auth::hasPermission('expenses.edit_draft')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to edit draft expenses.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid expense ID.']);
                exit;
            }

            $rawMethod = trim($_POST['payment_method'] ?? $_POST['payment_account_key'] ?? Expense::METHOD_CASH_IN_HAND);
            $rawBankId = !empty($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : null;
            [$pMethod, $bAccountId] = Expense::normalizePaymentSource($rawMethod, $rawBankId);

            $payload = [
                'branch_id'       => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $branchId,
                'expense_date'    => trim($_POST['expense_date'] ?? date('Y-m-d')),
                'category_id'     => (int)($_POST['category_id'] ?? 0),
                'account_id'      => !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null,
                'amount'          => round((float)($_POST['amount'] ?? 0), 2),
                'payment_method'  => $pMethod,
                'bank_account_id' => $bAccountId,
                'payee'           => trim($_POST['payee'] ?? ''),
                'description'     => trim($_POST['description'] ?? ''),
                'vendor_supplier' => trim($_POST['vendor_supplier'] ?? ''),
                'invoice_ref'     => trim($_POST['invoice_ref'] ?? ''),
                'reference_no'    => trim($_POST['reference_no'] ?? ''),
                'notes'           => trim($_POST['notes'] ?? '')
            ];

            $res = Expense::updateDraft($id, $payload, $userId);
            echo json_encode($res);
            break;

        case 'post':
            if (!Auth::hasPermission('expenses.post')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to post expenses.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid expense ID.']);
                exit;
            }

            $res = Expense::postExpense($id, $userId);
            echo json_encode($res);
            break;

        case 'reverse':
            if (!Auth::hasPermission('expenses.reverse')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to reverse expenses.']);
                exit;
            }

            $id     = (int)($_POST['id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid expense ID.']);
                exit;
            }
            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Mandatory reversal reason is required.']);
                exit;
            }

            $res = Expense::reverseExpense($id, $reason, $userId);
            echo json_encode($res);
            break;

        case 'record_payment':
            if (!Auth::hasPermission('expenses.post')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to record payments.']);
                exit;
            }

            $expenseId = (int)($_POST['expense_id'] ?? 0);
            if ($expenseId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid expense ID.']);
                exit;
            }

            $rawMethod = trim($_POST['payment_method'] ?? $_POST['payment_account_key'] ?? Expense::METHOD_CASH_IN_HAND);
            $rawBankId = !empty($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : null;
            [$pMethod, $bAccountId] = Expense::normalizePaymentSource($rawMethod, $rawBankId);

            $paymentData = [
                'amount'          => round((float)($_POST['amount'] ?? 0), 2),
                'payment_method'  => $pMethod,
                'payment_date'    => trim($_POST['payment_date'] ?? date('Y-m-d')),
                'bank_account_id' => $bAccountId,
                'reference_no'    => trim($_POST['reference_no'] ?? ''),
                'notes'           => trim($_POST['notes'] ?? '')
            ];

            $res = Expense::recordPayment($expenseId, $paymentData, $userId);
            echo json_encode($res);
            break;

        case 'reverse_payment':
            if (!Auth::hasPermission('expenses.reverse')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to reverse expense payments.']);
                exit;
            }

            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $reason    = trim($_POST['reason'] ?? '');
            if ($paymentId <= 0 || empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Payment ID and reversal reason are required.']);
                exit;
            }

            $res = Expense::reversePayment($paymentId, $reason, $userId);
            echo json_encode($res);
            break;

        case 'delete_draft':
            if (!Auth::hasPermission('expenses.edit_draft')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to delete draft.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid expense ID.']);
                exit;
            }

            $res = Expense::softDeleteDraft($id, $userId);
            echo json_encode($res);
            break;

        case 'restore_draft':
            if (!Auth::hasPermission('expenses.edit_draft')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to restore draft.']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid expense ID.']);
                exit;
            }

            $res = Expense::restoreDraft($id, $userId);
            echo json_encode($res);
            break;

        case 'upload_attachment':
            if (!Auth::hasPermission('expenses.create') && !Auth::hasPermission('expenses.edit_draft')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to upload attachments.']);
                exit;
            }

            $id = (int)($_POST['expense_id'] ?? 0);
            if ($id <= 0 || empty($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Valid expense ID and file are required.']);
                exit;
            }

            $res = Expense::addAttachment($id, $_FILES['attachment'], $userId);
            echo json_encode($res);
            break;

        case 'categories':
            $cats = Expense::getCategories(true);
            echo json_encode(['success' => true, 'data' => $cats]);
            break;

        case 'payment_accounts':
            $reqBranch = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $accounts = Expense::getEligiblePaymentAccounts($reqBranch);
            echo json_encode(['success' => true, 'data' => $accounts]);
            break;

        case 'export_csv':
            if (!Auth::hasPermission('expenses.export')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to export expenses.']);
                exit;
            }

            $filters = [
                'branch_id'      => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId,
                'category_id'    => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
                'status'         => !empty($_GET['status']) ? trim($_GET['status']) : null,
                'payment_method' => !empty($_GET['payment_method']) ? trim($_GET['payment_method']) : null,
                'date_from'      => !empty($_GET['date_from']) ? trim($_GET['date_from']) : null,
                'date_to'        => !empty($_GET['date_to']) ? trim($_GET['date_to']) : null,
                'search'         => !empty($_GET['search']) ? trim($_GET['search']) : null
            ];
            if (isset($_GET['branch_id']) && $_GET['branch_id'] === 'all') {
                $filters['branch_id'] = null;
            }

            $res = Expense::getExpenses($filters, 1, 500);
            $items = $res['items'] ?? [];

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=expenses_export_' . date('Ymd_His') . '.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Expense #', 'Date', 'Category', 'Expense Account', 'Description', 'Payee', 'Payment Source', 'Amount (LKR)', 'Paid Amount (LKR)', 'Status', 'Recorded By', 'Journal Entry #']);

            foreach ($items as $r) {
                fputcsv($out, [
                    $r['expense_no'],
                    $r['expense_date'],
                    $r['category_name'],
                    $r['expense_account_name'] . ' (' . $r['expense_account_code'] . ')',
                    $r['description'],
                    $r['payee'],
                    ucwords(str_replace('_', ' ', $r['payment_method'])) . ($r['bank_name'] ? " ({$r['bank_name']})" : ''),
                    number_format((float)$r['amount'], 2),
                    number_format((float)$r['paid_amount'], 2),
                    ucfirst($r['status']),
                    $r['created_by_name'],
                    $r['journal_entry_number'] ?? 'N/A'
                ]);
            }
            fclose($out);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action '{$action}'."]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Expense API Error in action '{$action}': " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
