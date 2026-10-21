<?php
/**
 * Curtiss POS - Suppliers Controller (with Opening Balance & Details Panel support)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Supplier.php';
require_once __DIR__ . '/core/SupplierOpeningBalance.php';
require_once __DIR__ . '/core/SupplierPayment.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)($user['id'] ?? 1);

// Handle Super Admin / Admin branch switching via GET parameter
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN) && isset($_GET['branch_id'])) {
    $reqBranchId = (int)$_GET['branch_id'];
    if ($reqBranchId > 0) {
        Auth::switchBranch($reqBranchId);
    }
}

$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

$errorMsg = null;
$successMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $address = $_POST['address'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $supplierCode = $_POST['supplier_code'] ?? null;
        $bankName = $_POST['bank_name'] ?? null;
        $bankAccountName = $_POST['bank_account_name'] ?? null;
        $bankAccountNo = $_POST['bank_account_no'] ?? null;
        $bankBranch = $_POST['bank_branch'] ?? null;

        $res = Supplier::create($name, $phone, $whatsapp, $address, $status, $supplierCode, $bankName, $bankAccountName, $bankAccountNo, $bankBranch);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $address = $_POST['address'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $supplierCode = $_POST['supplier_code'] ?? null;
        $bankName = $_POST['bank_name'] ?? null;
        $bankAccountName = $_POST['bank_account_name'] ?? null;
        $bankAccountNo = $_POST['bank_account_no'] ?? null;
        $bankBranch = $_POST['bank_branch'] ?? null;

        $res = Supplier::update($id, $name, $phone, $whatsapp, $address, $status, $supplierCode, $bankName, $bankAccountName, $bankAccountNo, $bankBranch);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $res = Supplier::toggleStatus($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $res = Supplier::delete($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'restore') {
        $id = (int)($_POST['id'] ?? 0);
        $res = Supplier::restore($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'post_opening_balance') {
        $targetBranchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $branchId;
        $res = SupplierOpeningBalance::postOpeningBalance($_POST, $userId, $targetBranchId);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'reverse_opening_balance') {
        $obId = (int)($_POST['opening_balance_id'] ?? 0);
        $reason = $_POST['reversal_reason'] ?? '';
        $targetBranchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $branchId;
        $res = SupplierOpeningBalance::reverseOpeningBalance($obId, $reason, $userId, $targetBranchId);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'process_supplier_payment') {
        $targetBranchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $branchId;
        $res = SupplierPayment::processPayment($_POST, $userId, $targetBranchId);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    }
}

$viewMode = ($_GET['view'] ?? '') === 'deleted' ? 'deleted' : 'active';
$search = $_GET['search'] ?? null;

$deletedSuppliers = Supplier::deleted();
$deletedCount = count($deletedSuppliers);

if ($viewMode === 'deleted') {
    $suppliers = $deletedSuppliers;
} else {
    $suppliers = Supplier::all(false, $search, $branchId);
}

// Fetch active bank accounts and all cash accounts for supplier payment options
$bankAccounts = Database::fetchAll("SELECT * FROM bank_accounts WHERE status = 'active' ORDER BY bank_name ASC");
$cashAccounts = Accounting::getCashAccounts();

// Fetch active branch name for context display and all branches for filter selector
$branchInfo = Database::fetchOne("SELECT name FROM branches WHERE id = :id", [':id' => $branchId]);
$branchName = $branchInfo['name'] ?? "Branch #{$branchId}";
$branches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' AND deleted_at IS NULL ORDER BY id ASC");

// Enrich suppliers list with outstanding balances, active OB, and summary stats using single batched query
$incompleteCount = 0;
$supplierIds = array_column($suppliers, 'id');
$batchSummaries = !empty($supplierIds) ? SupplierPayment::getBatchSupplierSummaries($supplierIds, $branchId) : [];

foreach ($suppliers as &$s) {
    $sId = (int)$s['id'];
    $summary = $batchSummaries[$sId] ?? [];
    $s['total_outstanding']      = $summary['total_outstanding'] ?? 0.00;
    $s['total_credit_balance']   = $summary['total_credit_balance'] ?? 0.00;
    $s['active_opening_balance'] = $summary['active_opening_balance'] ?? null;
    $s['approved_grns_count']    = (int)($summary['approved_grns_count'] ?? 0);
    $s['payment_history_count']  = (int)($summary['payment_history_count'] ?? 0);
    $s['activity_ledger']        = []; // Loaded on-demand via AJAX when opening slide-in drawer
    $s['needs_review']           = (empty(trim($s['phone'] ?? '')) && empty(trim($s['address'] ?? '')));
    if ($s['needs_review'] && $viewMode !== 'deleted') {
        $incompleteCount++;
    }
}
unset($s);

// Sort incomplete quick-created suppliers to the top for user review
if ($viewMode !== 'deleted') {
    usort($suppliers, function($a, $b) {
        $aReview = !empty($a['needs_review']) ? 1 : 0;
        $bReview = !empty($b['needs_review']) ? 1 : 0;
        if ($aReview !== $bReview) {
            return $bReview <=> $aReview;
        }
        return strcmp($a['name'], $b['name']);
    });
}

View::render('suppliers/index', [
    'suppliers'       => $suppliers,
    'bankAccounts'    => $bankAccounts,
    'cashAccounts'    => $cashAccounts,
    'branchId'        => $branchId,
    'branchName'      => $branchName,
    'branches'        => $branches,
    'incompleteCount' => $incompleteCount,
    'deletedCount'    => $deletedCount,
    'viewMode'        => $viewMode,
    'search'          => $search,
    'errorMsg'        => $errorMsg,
    'successMsg'      => $successMsg
], 'Suppliers - Curtiss POS');
