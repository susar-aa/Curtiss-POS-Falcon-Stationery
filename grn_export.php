<?php
/**
 * Curtiss POS - GRN Export Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/GRN.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

GRN::exportList($_GET);
