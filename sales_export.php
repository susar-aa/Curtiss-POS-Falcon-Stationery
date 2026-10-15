<?php
/**
 * Curtiss POS - Sales Export Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Sale.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER)) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

Sale::exportList($_GET, $activeBranchId);
