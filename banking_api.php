<?php
/**
 * Curtiss POS - Banking & Bank Reconciliation API Endpoint (Stage 13)
 * Handles AJAX requests for Bank Accounts, Cash Deposits, Activity Register,
 * Bank Reconciliations, and Bank Adjustments.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/Banking.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (Auth::isSpecialUser() && $action !== 'get_accounts') {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is restricted from bank transactions and mutations.']);
    exit;
}

try {
    switch ($action) {

        case 'get_accounts':
            if (!Auth::hasPermission('view_bank_accounts')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view bank accounts.']);
                exit;
            }
            $activeOnly = isset($_GET['all']) && $_GET['all'] === '1' ? false : true;
            $accounts = Banking::getBankAccounts(null, $activeOnly);
            echo json_encode(['success' => true, 'data' => $accounts]);
            break;

        case 'get_account':
            if (!Auth::hasPermission('view_bank_accounts')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view bank accounts.']);
                exit;
            }
            $id = (int)($_GET['id'] ?? 0);
            $account = Banking::getBankAccount($id);
            if ($account) {
                echo json_encode(['success' => true, 'data' => $account]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Bank account not found.']);
            }
            break;

        case 'save_account':
            if (!Auth::hasPermission('manage_bank_accounts')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to manage bank accounts.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = Banking::saveBankAccount($input, $userId);
            echo json_encode($res);
            break;

        case 'create_deposit':
            if (!Auth::hasPermission('make_bank_deposit')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to make cash deposits.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = Banking::createDeposit($input, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'get_register':
            if (!Auth::hasPermission('view_bank_register')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view bank register.']);
                exit;
            }
            $bankAccountId = (int)($_GET['bank_account_id'] ?? 0);
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(1000, (int)($_GET['limit'] ?? 500)));
            $filters = [
                'start_date' => $_GET['start_date'] ?? null,
                'end_date'   => $_GET['end_date'] ?? null,
                'search'     => $_GET['search'] ?? null
            ];
            $register = Banking::getBankRegister($bankAccountId, $filters, $page, $limit);
            echo json_encode(['success' => true, 'data' => $register]);
            break;

        case 'start_reconciliation':
            if (!Auth::hasPermission('manage_bank_reconciliation')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to manage bank reconciliation.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = Banking::startReconciliationSession($input, $userId);
            echo json_encode($res);
            break;

        case 'update_cleared_items':
            if (!Auth::hasPermission('manage_bank_reconciliation')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to manage bank reconciliation.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $reconciliationId = (int)($input['reconciliation_id'] ?? 0);
            $clearedLineIds = $input['cleared_line_ids'] ?? [];
            $res = Banking::updateClearedItems($reconciliationId, $clearedLineIds, $userId);
            echo json_encode($res);
            break;

        case 'post_adjustment':
            if (!Auth::hasPermission('post_bank_adjustment')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to post bank adjustments.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $bankAccountId = (int)($input['bank_account_id'] ?? 0);
            $res = Banking::postBankAdjustment($bankAccountId, $input, $userId);
            echo json_encode($res);
            break;

        case 'complete_reconciliation':
            if (!Auth::hasPermission('manage_bank_reconciliation')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to complete bank reconciliation.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $reconciliationId = (int)($input['reconciliation_id'] ?? 0);
            $res = Banking::completeReconciliation($reconciliationId, $userId);
            echo json_encode($res);
            break;

        case 'get_cheques':
            if (!Auth::hasPermission('view_bank_register')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view cheques register.']);
                exit;
            }
            $bankAccountId = (int)($_GET['bank_account_id'] ?? 0);
            $cheques = Banking::getChequesRegister($bankAccountId);
            echo json_encode(['success' => true, 'data' => $cheques]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid or unknown API action.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("API error in banking_api.php [action={$action}]: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
