<?php
/**
 * Curtiss POS - Accounting API Endpoint (Stage 10)
 * Handles Chart of Accounts management, Manual Journal postings,
 * Reversal postings, Historical Backfill, and General Ledger queries.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Accounting.php';

Auth::requireLogin();
if (Auth::isSpecialUser()) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is restricted from detailed accounting records and mutations.']);
    exit;
}
$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'get_coa':
            if (!Auth::hasPermission('accounting.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $filters = [
                'type'      => $_GET['type'] ?? '',
                'status'    => $_GET['status'] ?? '',
                'search'    => $_GET['search'] ?? '',
                'branch_id' => !empty($_GET['branch_id']) ? $_GET['branch_id'] : $branchId
            ];
            $coa = Accounting::getCOA($filters);
            echo json_encode(['success' => true, 'data' => $coa]);
            break;

        case 'get_account_history':
            if (!Auth::hasPermission('accounting.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $accountId = (int)($_GET['account_id'] ?? 0);
            if ($accountId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid account ID.']);
                exit;
            }
            $reqBranch = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $res = Accounting::getAccountHistory($accountId, $reqBranch);
            echo json_encode($res);
            break;

        case 'create_account':
            if (!Auth::hasPermission('accounts.manage')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create accounts.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = Accounting::createAccount($input, $userId);
            echo json_encode($res);
            break;

        case 'update_account':
            if (!Auth::hasPermission('accounts.manage')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to update accounts.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid account ID.']);
                exit;
            }
            $res = Accounting::updateAccount($id, $input, $userId);
            echo json_encode($res);
            break;

        case 'get_journals':
            if (!Auth::hasPermission('accounting.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 25)));
            $filters = [
                'branch_id'      => !empty($_GET['branch_id']) ? $_GET['branch_id'] : $branchId,
                'source_type'    => $_GET['source_type'] ?? '',
                'posting_status' => $_GET['posting_status'] ?? '',
                'start_date'     => $_GET['start_date'] ?? '',
                'end_date'       => $_GET['end_date'] ?? '',
                'search'         => $_GET['search'] ?? ''
            ];
            $res = Accounting::getJournalEntries($filters, $page, $limit);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_journal_details':
            if (!Auth::hasPermission('accounting.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $id = (int)($_GET['id'] ?? 0);
            $details = Accounting::getJournalEntryDetails($id);
            if (!$details) {
                echo json_encode(['success' => false, 'message' => 'Journal entry not found.']);
            } else {
                echo json_encode(['success' => true, 'data' => $details]);
            }
            break;

        case 'post_manual_journal':
            if (!Auth::hasPermission('accounting.post_manual')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to post manual journal entries.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $header = [
                'branch_id'   => !empty($input['branch_id']) ? (int)$input['branch_id'] : $branchId,
                'entry_date'  => !empty($input['entry_date']) ? $input['entry_date'] : date('Y-m-d'),
                'source_type' => 'manual',
                'description' => $input['description'] ?? 'Manual Journal Entry',
                'created_by'  => $userId
            ];
            $lines = $input['lines'] ?? [];
            $res = Accounting::postJournalEntry($header, $lines);
            echo json_encode($res);
            break;

        case 'reverse_journal':
            if (!Auth::hasPermission('accounting.reverse')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to reverse journal entries.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $journalId = (int)($input['journal_id'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            if ($journalId <= 0 || empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Journal ID and reversal reason are required.']);
                exit;
            }
            $res = Accounting::reverseJournalEntry($journalId, $reason, $userId);
            echo json_encode($res);
            break;

        case 'run_backfill':
            if (!Auth::hasPermission('accounting.backfill')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to execute accounting backfill.']);
                exit;
            }
            $res = Accounting::runHistoricalBackfill($userId);
            echo json_encode($res);
            break;

        case 'get_general_ledger':
            if (!Auth::hasPermission('accounting.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 25)));
            $filters = [
                'account_id'  => $_GET['account_id'] ?? '',
                'branch_id'   => !empty($_GET['branch_id']) ? $_GET['branch_id'] : $branchId,
                'source_type' => $_GET['source_type'] ?? '',
                'start_date'  => $_GET['start_date'] ?? '',
                'end_date'    => $_GET['end_date'] ?? '',
                'search'      => $_GET['search'] ?? ''
            ];
            $res = Accounting::getGeneralLedger($filters, $page, $limit);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Accounting API Exception: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
