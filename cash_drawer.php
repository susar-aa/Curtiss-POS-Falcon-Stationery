<?php
/**
 * Curtiss POS - Cash Drawer & Day Closing Controller
 * Clean MVC controller invoking View::render('cash_drawer/index', ...)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/BusinessDay.php';

Auth::requireAuth();
if (!Auth::hasPermission('view_cash_drawer') && !Auth::hasPermission('start_business_day')) {
    View::renderBlank('errors/403');
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($user['active_branch_id'] ?? $_SESSION['branch_id'] ?? 1);

$activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
$activeSummary = $activeDay ? BusinessDay::getDrawerSummary((int)$activeDay['id']) : null;
$comprehensiveSummary = $activeDay ? BusinessDay::getDayComprehensiveSummary((int)$activeDay['id']) : null;
$movements = $activeDay ? BusinessDay::getDayMovements((int)$activeDay['id']) : [];
$closedDays = BusinessDay::getClosedDaysList($branchId, 1, 50);

View::render('cash_drawer/index', [
    'activeDay'            => $activeDay,
    'activeSummary'        => $activeSummary,
    'comprehensiveSummary' => $comprehensiveSummary,
    'movements'            => $movements,
    'closedDays'           => $closedDays
], 'Cash Drawer & Day Closing - Curtiss POS');