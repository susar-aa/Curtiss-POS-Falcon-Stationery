<?php
/**
 * Curtiss POS - Standalone Full-Screen POS Billing Entry Point (Stage 6)
 * Master Rules enforced: active branch session resolution, role fallbacks, and secure access.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/Sale.php';
require_once __DIR__ . '/core/View.php';

Auth::requireLogin();

if (!Auth::hasPermission('pos.access') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_CASHIER)) {
    View::renderBlank('errors/403');
    exit();
}

if (!empty($_GET['branch_id'])) {
    $switchedBranchId = (int)$_GET['branch_id'];
    $_SESSION['branch_id'] = $switchedBranchId;
    $_SESSION['active_branch_id'] = $switchedBranchId;
}

$currentBranch = Auth::getCurrentBranch();
$currentUser = Auth::getCurrentUser();
$businessName = Settings::get('business_name', 'Curtiss POS');
$companyLogo = Settings::get('company_logo', '');
$allActiveBranches = Settings::getAllActiveBranches();

require_once __DIR__ . '/views/pos/pos_screen.php';
