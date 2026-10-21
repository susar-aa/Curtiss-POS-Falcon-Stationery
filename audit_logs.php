<?php
/**
 * Curtiss POS - Audit Logs Controller
 * System compliance & security audit log viewer
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Audit.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
    View::renderBlank('errors/403');
    exit();
}

// Active session branch filter
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

// Superadmin can view all branches or filter by specific branch
$branches = [];
if (Auth::hasRole(ROLE_SUPER_ADMIN)) {
    $branches = Database::fetchAll("SELECT id, name FROM branches WHERE deleted_at IS NULL ORDER BY name ASC");
}

$filters = [
    'module'    => $_GET['module'] ?? '',
    'action'    => $_GET['action'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to'] ?? '',
    'search'    => $_GET['search'] ?? '',
    'branch_id' => !empty($_GET['branch_id']) ? $_GET['branch_id'] : (Auth::hasRole(ROLE_SUPER_ADMIN) ? '' : $activeBranchId)
];

// Support export feature
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Generate clean CSV of filtered logs
    $exportLogs = Audit::all(array_merge($filters, ['limit' => 5000]));
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Timestamp', 'User', 'Branch', 'Module', 'Action', 'Record ID', 'IP Address', 'User Agent', 'Old Values', 'New Values']);
    foreach ($exportLogs as $log) {
        fputcsv($out, [
            $log['id'],
            $log['created_at'],
            $log['user_name'],
            $log['branch_name'],
            $log['module'],
            $log['action'],
            $log['record_id'],
            $log['ip_address'],
            $log['user_agent'],
            $log['old_values'],
            $log['new_values']
        ]);
    }
    fclose($out);
    exit();
}

$auditLogs = Audit::all($filters);
$modules = Audit::getModules();
$actions = Audit::getActions();

View::render('audit_logs/index', [
    'auditLogs' => $auditLogs,
    'modules'   => $modules,
    'actions'   => $actions,
    'branches'  => $branches,
    'filters'   => $filters
], 'Audit Logs - Curtiss POS');
