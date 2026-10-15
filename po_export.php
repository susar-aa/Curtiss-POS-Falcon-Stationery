<?php
/**
 * Curtiss POS - Purchase Order Export Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/PurchaseOrder.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

PurchaseOrder::exportList($_GET);
