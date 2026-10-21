<?php
/**
 * Curtiss POS - Central Reports & Financial Statements Controller
 * Single source of truth reporting hub for Sales, Purchasing, Inventory, 
 * Supplier Outstanding, Trial Balance, Profit & Loss, Balance Sheet, and KPI Performance.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireLogin();
if (!Auth::hasPermission('view_reports')) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$userBranches = $_SESSION['user_branches'] ?? [];
$canViewAllBranches = Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
$canViewFinancials = Auth::hasPermission('view_financial_statements') && !Auth::isSpecialUser();
$canExport = Auth::hasPermission('export_reports');

// Load dropdown options for filters
$branches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' ORDER BY name ASC");
$categories = Database::fetchAll("SELECT id, name FROM categories ORDER BY name ASC");
$brands = Database::fetchAll("SELECT id, name FROM brands ORDER BY name ASC");
$suppliers = Database::fetchAll("SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name ASC");

$defaultDateFrom = date('Y-m-01');
$defaultDateTo   = date('Y-m-d');
$activeReportType = $_GET['type'] ?? 'center';
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
$selectedBranch = (!empty($_SESSION['all_branches_view']) && $canViewAllBranches) ? 'all' : ($branchId ?: 'all');

View::render('reports/index', [
    'user'               => $user,
    'canViewAllBranches' => $canViewAllBranches,
    'canViewFinancials'  => $canViewFinancials,
    'canExport'          => $canExport,
    'branches'           => $branches,
    'categories'         => $categories,
    'brands'             => $brands,
    'suppliers'          => $suppliers,
    'defaultDateFrom'    => $defaultDateFrom,
    'defaultDateTo'      => $defaultDateTo,
    'activeReportType'   => $activeReportType,
    'selectedBranch'     => $selectedBranch
], 'Central Reports & Financial Statements - Curtiss POS');
