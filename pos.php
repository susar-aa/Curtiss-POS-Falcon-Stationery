<?php
/**
 * Curtiss POS - Standalone Full-Screen POS Billing Entry Point (Stage 6)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/Sale.php';

Auth::requireLogin();
Auth::requirePermission('pos.access');

$currentBranch = Auth::getCurrentBranch();
$currentUser = Auth::getCurrentUser();
$businessName = Settings::get('business_name', 'Curtiss POS');

require_once __DIR__ . '/views/pos/pos_screen.php';
