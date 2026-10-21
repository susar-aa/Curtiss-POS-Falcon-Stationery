<?php
/**
 * Curtiss POS - Sales Export Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Sale.php';

if (!defined('ROLE_SUPER_ADMIN')) define('ROLE_SUPER_ADMIN', 'super_admin');
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'admin');
if (!defined('ROLE_MANAGER')) define('ROLE_MANAGER', 'manager');
if (!defined('ROLE_ACCOUNTANT')) define('ROLE_ACCOUNTANT', 'accountant');
if (!defined('ROLE_CASHIER')) define('ROLE_CASHIER', 'cashier');
if (!defined('ROLE_SPECIAL_USER')) define('ROLE_SPECIAL_USER', 'special_user');

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

if (Auth::hasRole(ROLE_SUPER_ADMIN) && isset($_GET['branch_id'])) {
    if ($_GET['branch_id'] === 'all' || (int)$_GET['branch_id'] === 0) {
        $activeBranchId = 0;
    } else {
        $activeBranchId = (int)$_GET['branch_id'];
    }
}

Sale::exportList($_GET, $activeBranchId);
