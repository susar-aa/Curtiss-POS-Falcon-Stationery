<?php
/**
 * Curtiss POS - Gift Voucher Management Module (Redesign)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/GiftVoucher.php';

Auth::requireLogin();
if (!Auth::hasPermission('vouchers.view')) {
    View::renderBlank('errors/403');
    exit();
}

$errorMsg = $_SESSION['flash_error'] ?? null;
$successMsg = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Create Voucher Action Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_voucher') {
    if (!Auth::hasPermission('vouchers.manage') || Auth::isSpecialUser()) {
        $errorMsg = 'Permission denied to create gift vouchers.';
    } else {
        $amount = (float)($_POST['amount'] ?? 0);
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $res = GiftVoucher::createVoucher($amount, $expiryDate, $notes);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    }
}

// Cancel Voucher Action Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_voucher') {
    if (!Auth::hasPermission('vouchers.manage') || Auth::isSpecialUser()) {
        $errorMsg = 'Permission denied to cancel gift vouchers.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $res = GiftVoucher::cancelVoucher($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$limit = 15;

$voucherData = GiftVoucher::all(['search' => $search, 'status' => $statusFilter], $page, $limit);
$stats = GiftVoucher::getSummaryStats();

View::render('vouchers/voucher_list', [
    'vouchers'     => $voucherData['items'],
    'totalItems'   => $voucherData['totalItems'],
    'page'         => $voucherData['page'],
    'totalPages'   => $voucherData['totalPages'],
    'search'       => $search,
    'statusFilter' => $statusFilter,
    'stats'        => $stats,
    'errorMsg'     => $errorMsg,
    'successMsg'   => $successMsg
], 'Gift Vouchers - Curtiss POS');
