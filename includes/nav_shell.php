<?php
/**
 * Curtiss POS - Navigation Shell PHP Fragment
 *
 * Outputs the CURTISS_NAV JavaScript configuration object.
 * This is the permission-aware bridge between PHP auth and JS nav.
 *
 * Include in layout.php BEFORE nav.js is loaded.
 * All permission checks use the existing Auth:: system.
 * Special User masking is preserved throughout.
 */

// Ensure Auth and all constants are loaded (already included via config.php in layout chain)
$navUser = Auth::user();
$navScriptFull = basename($_SERVER['SCRIPT_NAME']);
$navScript = preg_replace('/\.php$/i', '', $navScriptFull);
$navBranchId = (int)($navUser['active_branch_id'] ?? 0);
$navIsSpecial = ($navUser['role_code'] ?? '') === ROLE_SPECIAL_USER;

if (!function_exists('navIcon')) {
    function navIcon(string $path, string $extra = ''): string {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $path . '</svg>';
    }
}

// ─── Business Day Status ──────────────────────────────────────────────────────
$bizDay = null;
if (Auth::hasPermission('view_cash_drawer') || Auth::hasPermission('start_business_day')) {
    try {
        $db_   = Database::getInstance();
        $pdo_  = $db_->getConnection();
        $stmt_ = $pdo_->prepare(
            "SELECT (status = 'open') AS is_open, business_date, opened_at
             FROM business_days
             WHERE branch_id = :bid ORDER BY id DESC LIMIT 1"
        );
        $stmt_->execute([':bid' => $navBranchId]);
        $bizDay = $stmt_->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        // Non-fatal
    }
}

// ─── Build Quick Actions (permission-aware, clean URLs) ────────────────────────
$quickActions = [];

if (Auth::hasPermission('pos.access')) {
    $quickActions[] = ['label' => 'New Sale', 'url' => 'pos',
        'icon' => navIcon('<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h12M6 12h8"/>')];
}
if (!$navIsSpecial && Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER)) {
    $quickActions[] = ['label' => 'Add Product', 'url' => 'product_create',
        'icon' => navIcon('<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>')];
    $quickActions[] = ['label' => 'New Customer', 'url' => 'customer_create',
        'icon' => navIcon('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>')];
}
if (!$navIsSpecial && Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
    $quickActions[] = ['label' => 'Create PO', 'url' => 'po_create',
        'icon' => navIcon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>')];
    $quickActions[] = ['label' => 'Create GRN', 'url' => 'grn_create',
        'icon' => navIcon('<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>')];
}
if (!$navIsSpecial && Auth::hasPermission('customers.pay')) {
    $quickActions[] = ['label' => 'Collection', 'url' => 'customer_payments',
        'icon' => navIcon('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>')];
}

$quickActions = array_slice($quickActions, 0, 6);

// ─── Build Categories & Modules (Clean URLs) ──────────────────────────────────
$categories = [];

// ── 0. HOME
$categories[] = [
    'label'   => 'Home',
    'icon'    => navIcon('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'),
    'modules' => [
        ['label' => 'Dashboard', 'url' => 'dashboard', 'scripts' => ['dashboard', 'dashboard.php', 'index', 'index.php'], 'desc' => 'Business overview',
         'icon' => navIcon('<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>')],
    ],
];

// ── 1. SALES & POS
$salesModules = [];
if (Auth::hasPermission('pos.access')) {
    $salesModules[] = ['label' => 'POS Terminal', 'url' => 'pos', 'scripts' => ['pos', 'pos.php'], 'desc' => 'Billing & checkout',
        'icon' => navIcon('<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h12M6 12h8M6 16h4"/>')];
}
if (Auth::hasPermission('pos.access') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER)) {
    $salesModules[] = ['label' => 'Sales History', 'url' => 'sales_history', 'scripts' => ['sales_history', 'sales_history.php', 'sales', 'sales.php'], 'desc' => 'Invoice & receipt logs',
        'icon' => navIcon('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>')];
}
if (!$navIsSpecial && Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
    $salesModules[] = ['label' => 'Discounts & Promotions', 'url' => 'discounts', 'scripts' => ['discounts', 'discounts.php'], 'desc' => 'Rules & campaigns',
        'icon' => navIcon('<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>')];
}
if (!$navIsSpecial && (Auth::hasPermission('returns.view') || Auth::hasPermission('returns.create'))) {
    $salesModules[] = ['label' => 'Sales Returns', 'url' => 'returns', 'scripts' => ['returns', 'returns.php'], 'desc' => 'Returns & exchanges',
        'icon' => navIcon('<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>')];
}
if (!$navIsSpecial && Auth::hasPermission('view_cash_drawer') || Auth::hasPermission('start_business_day')) {
    $salesModules[] = ['label' => 'Cash Drawer', 'url' => 'cash_drawer', 'scripts' => ['cash_drawer', 'cash_drawer.php'], 'desc' => 'Business day & cash',
        'icon' => navIcon('<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M12 12h.01"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>')];
}
if (count($salesModules) > 0) {
    $categories[] = ['label' => 'Sales', 'icon' => navIcon('<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>'), 'modules' => $salesModules];
}

// ── 2. CUSTOMERS
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    $custModules = [
        ['label' => 'Customers & Credit', 'url' => 'customers', 'scripts' => ['customers', 'customers.php', 'customer_create', 'customer_create.php', 'customer_edit', 'customer_edit.php', 'customer_profile', 'customer_profile.php'], 'desc' => 'Customer master',
         'icon' => navIcon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>')],
    ];
    if (Auth::hasPermission('customers.pay')) {
        $custModules[] = ['label' => 'Collections', 'url' => 'customer_payments', 'scripts' => ['customer_payments', 'customer_payments.php'], 'desc' => 'Credit payments',
            'icon' => navIcon('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>')];
    }
    $categories[] = ['label' => 'Customers', 'icon' => navIcon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'), 'modules' => $custModules];
}

// ── 3. INVENTORY & STOCK
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    $invModules = [
        ['label' => 'Branch Inventory', 'url' => 'inventory', 'scripts' => ['inventory', 'inventory.php'], 'desc' => 'Stock levels',
         'icon' => navIcon('<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/>')],
        ['label' => 'Price & Cost Lots', 'url' => 'inventory_lots', 'scripts' => ['inventory_lots', 'inventory_lots.php'], 'desc' => 'Lot costing',
         'icon' => navIcon('<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>')],
        ['label' => 'Barcode Labels', 'url' => 'barcode_labels', 'scripts' => ['barcode_labels', 'barcode_labels.php'], 'desc' => 'TSC thermal printing',
         'icon' => navIcon('<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M6 9v6M9 9v6M13 9v6M16 9v6M18 9v6"/>')],
        ['label' => 'Stock Ledger', 'url' => 'inventory_ledger', 'scripts' => ['inventory_ledger', 'inventory_ledger.php'], 'desc' => 'Movement history',
         'icon' => navIcon('<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>')],
        ['label' => 'Stock Transfers', 'url' => 'stock_transfers', 'scripts' => ['stock_transfers', 'stock_transfers.php'], 'desc' => 'Branch-to-branch',
         'icon' => navIcon('<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>')],
        ['label' => 'Stock Adjustments', 'url' => 'stock_adjustments', 'scripts' => ['stock_adjustments', 'stock_adjustments.php'], 'desc' => 'Manual corrections',
         'icon' => navIcon('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>')],
        ['label' => 'Damaged & Wastage', 'url' => 'damaged_stock', 'scripts' => ['damaged_stock', 'damaged_stock.php'], 'desc' => 'Loss recording',
         'icon' => navIcon('<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>')],
        ['label' => 'Physical Audit', 'url' => 'stock_audits', 'scripts' => ['stock_audits', 'stock_audits.php'], 'desc' => 'Stock count',
         'icon' => navIcon('<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>')],
    ];
    $categories[] = ['label' => 'Inventory', 'icon' => navIcon('<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>'), 'modules' => $invModules];
}

// ── 4. PRODUCTS
$prodModules = [];
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    $prodModules[] = ['label' => 'Product Master', 'url' => 'products', 'scripts' => ['products', 'products.php', 'product_create', 'product_create.php', 'product_edit', 'product_edit.php'], 'desc' => 'Product catalogue',
        'icon' => navIcon('<circle cx="12" cy="12" r="10"/>') ];
}
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
    $prodModules[] = ['label' => 'Quick Product Editor', 'url' => 'quick_products', 'scripts' => ['quick_products', 'quick_products.php'], 'desc' => 'Fast inline product & stock editing',
        'icon' => navIcon('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>') ];
}
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    $prodModules[] = ['label' => 'Brands', 'url' => 'brands', 'scripts' => ['brands', 'brands.php'], 'desc' => 'Brand management',
        'icon' => navIcon('<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>')];
    $prodModules[] = ['label' => 'Categories', 'url' => 'categories', 'scripts' => ['categories', 'categories.php'], 'desc' => 'Product categories',
        'icon' => navIcon('<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>')];
}
if (count($prodModules) > 0) {
    $categories[] = ['label' => 'Products', 'icon' => navIcon('<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'), 'modules' => $prodModules];
}

// ── 5. PURCHASING & SUPPLIERS
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    $purchModules = [
        ['label' => 'Purchase Orders', 'url' => 'purchase_orders', 'scripts' => ['purchase_orders', 'purchase_orders.php', 'po_create', 'po_create.php'], 'desc' => 'PO management',
         'icon' => navIcon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>')],
        ['label' => 'Goods Received (GRN)', 'url' => 'grns', 'scripts' => ['grns', 'grns.php', 'grn_create', 'grn_create.php'], 'desc' => 'Stock receiving',
         'icon' => navIcon('<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>')],
    ];
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
        $purchModules[] = ['label' => 'Suppliers', 'url' => 'suppliers', 'scripts' => ['suppliers', 'suppliers.php'], 'desc' => 'Supplier master',
            'icon' => navIcon('<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>')];
    }

    if (Auth::hasPermission('view_supplier_returns') || Auth::hasPermission('create_supplier_return')) {
        $purchModules[] = ['label' => 'Supplier Returns', 'url' => 'supplier_returns', 'scripts' => ['supplier_returns', 'supplier_returns.php'], 'desc' => 'Return to supplier',
            'icon' => navIcon('<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>')];
    }
    $categories[] = ['label' => 'Purchasing', 'icon' => navIcon('<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>'), 'modules' => $purchModules];
}

// ── 6. ACCOUNTING & FINANCE
if (!$navIsSpecial && (Auth::hasPermission('accounting.view') || Auth::hasPermission('view_petty_cash') || Auth::hasPermission('view_bank_accounts') || Auth::hasPermission('expenses.view'))) {
    $finModules = [];
    if (Auth::hasPermission('expenses.view')) {
        $finModules[] = ['label' => 'Expenses', 'url' => 'expenses', 'scripts' => ['expenses', 'expenses.php'], 'desc' => 'Operating expenses & bills',
            'icon' => navIcon('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>')];
    }
    if (Auth::hasPermission('view_cheques')) {
        $finModules[] = ['label' => 'Cheque Management', 'url' => 'cheque_management', 'scripts' => ['cheque_management', 'cheque_management.php'], 'desc' => 'Cheque lifecycle',
            'icon' => navIcon('<rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M17 9v6"/>')];
    }
    if (Auth::hasPermission('view_bank_accounts')) {
        $finModules[] = ['label' => 'Bank Accounts', 'url' => 'bank_accounts', 'scripts' => ['bank_accounts', 'bank_accounts.php'], 'desc' => 'Deposits & balances',
            'icon' => navIcon('<path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/>')];
    }
    if (Auth::hasPermission('manage_bank_reconciliation')) {
        $finModules[] = ['label' => 'Bank Reconciliation', 'url' => 'bank_reconciliation', 'scripts' => ['bank_reconciliation', 'bank_reconciliation.php'], 'desc' => 'Reconcile accounts',
            'icon' => navIcon('<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>')];
    }
    if (Auth::hasPermission('view_petty_cash')) {
        $finModules[] = ['label' => 'Petty Cash', 'url' => 'petty_cash', 'scripts' => ['petty_cash', 'petty_cash.php'], 'desc' => 'Expenses & cash',
            'icon' => navIcon('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>')];
    }
    if (Auth::hasPermission('accounting.view')) {
        $finModules[] = ['label' => 'Journal Entries', 'url' => 'journal_entries', 'scripts' => ['journal_entries', 'journal_entries.php', 'accounting', 'accounting.php'], 'desc' => 'Journal entry ledger',
            'icon' => navIcon('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>')];
        $finModules[] = ['label' => 'Chart of Accounts', 'url' => 'chart_of_accounts', 'scripts' => ['chart_of_accounts', 'chart_of_accounts.php'], 'desc' => 'Accounts structure',
            'icon' => navIcon('<path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>')];
        $finModules[] = ['label' => 'General Ledger', 'url' => 'general_ledger', 'scripts' => ['general_ledger', 'general_ledger.php'], 'desc' => 'Account ledgers',
            'icon' => navIcon('<rect x="3" y="4" width="18" height="16" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>')];
    }
    if (Auth::hasPermission('view_reports')) {
        $finModules[] = ['label' => 'Reports & Financials', 'url' => 'reports', 'scripts' => ['reports', 'reports.php'], 'desc' => 'Financial reports',
            'icon' => navIcon('<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>')];
    }
    if (count($finModules) > 0) {
        $categories[] = ['label' => 'Finance', 'icon' => navIcon('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'), 'modules' => $finModules];
    }
}
// ── 6.5. HR & PAYROLL
if (!$navIsSpecial && (Auth::hasPermission('employees.view') || Auth::hasPermission('payroll.view'))) {
    $hrModules = [];
    if (Auth::hasPermission('employees.view')) {
        $hrModules[] = ['label' => 'Employee Directory', 'url' => 'employees', 'scripts' => ['employees', 'employees.php'], 'desc' => 'Staff & Sales Reps',
            'icon' => navIcon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>')];
    }
    if (Auth::hasPermission('payroll.view')) {
        $hrModules[] = ['label' => 'Payroll Management', 'url' => 'payroll', 'scripts' => ['payroll', 'payroll.php'], 'desc' => 'Salary & EPF/ETF',
            'icon' => navIcon('<rect x="2" y="4" width="20" height="16" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>')];
        $hrModules[] = ['label' => 'Salary Advances', 'url' => 'salary_advances', 'scripts' => ['salary_advances', 'salary_advances.php'], 'desc' => 'Disbursements & Ledger',
            'icon' => navIcon('<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>')];
    }
    if (count($hrModules) > 0) {
        $categories[] = ['label' => 'HR & Payroll', 'icon' => navIcon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'), 'modules' => $hrModules];
    }
}

// ── 7. ADMINISTRATION
$adminModules = [];
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    $adminModules[] = ['label' => 'Branch Management', 'url' => 'branches', 'scripts' => ['branches', 'branches.php'], 'desc' => 'Branch setup',
        'icon' => navIcon('<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-3"/>')];
}
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) {
    $adminModules[] = ['label' => 'User Accounts', 'url' => 'users', 'scripts' => ['users', 'users.php'], 'desc' => 'User management',
        'icon' => navIcon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>')];
}
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
    $adminModules[] = ['label' => 'Audit Logs', 'url' => 'audit_logs', 'scripts' => ['audit_logs', 'audit_logs.php'], 'desc' => 'Security & compliance log',
        'icon' => navIcon('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>')];
}
if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
    $adminModules[] = ['label' => 'System Settings', 'url' => 'settings', 'scripts' => ['settings', 'settings.php'], 'desc' => 'Configuration',
        'icon' => navIcon('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0 .33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>')];
}
if (count($adminModules) > 0) {
    $categories[] = ['label' => 'Administration', 'icon' => navIcon('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33"/>'), 'modules' => $adminModules];
}

// ─── Current page label mapping ────────────────────────────────────────────────
$pageLabelMap = [
    'dashboard'             => 'Dashboard',
    'dashboard.php'         => 'Dashboard',
    'audit_logs'            => 'Audit Logs',
    'audit_logs.php'        => 'Audit Logs',
    'pos'                   => 'POS Terminal',
    'pos.php'               => 'POS Terminal',
    'cash_drawer'           => 'Cash Drawer',
    'cash_drawer.php'       => 'Cash Drawer',
    'returns'               => 'Sales Returns',
    'returns.php'           => 'Sales Returns',
    'customers'             => 'Customers',
    'customers.php'         => 'Customers',
    'customer_payments'     => 'Collections',
    'customer_payments.php' => 'Collections',
    'purchase_orders'       => 'Purchase Orders',
    'purchase_orders.php'   => 'Purchase Orders',
    'grns'                  => 'GRN',
    'grns.php'              => 'GRN',
    'supplier_payments'     => 'Supplier Payments',
    'supplier_payments.php' => 'Supplier Payments',
    'supplier_returns'      => 'Supplier Returns',
    'supplier_returns.php'  => 'Supplier Returns',
    'supplier_credits'      => 'Supplier Credits',
    'supplier_credits.php'  => 'Supplier Credits',
    'inventory'             => 'Branch Inventory',
    'inventory.php'         => 'Branch Inventory',
    'inventory_lots'        => 'Price & Cost Lots',
    'inventory_lots.php'    => 'Price & Cost Lots',
    'inventory_ledger'      => 'Stock Ledger',
    'inventory_ledger.php'  => 'Stock Ledger',
    'stock_transfers'       => 'Stock Transfers',
    'stock_transfers.php'   => 'Stock Transfers',
    'stock_adjustments'     => 'Stock Adjustments',
    'stock_adjustments.php' => 'Stock Adjustments',
    'damaged_stock'         => 'Damaged Stock',
    'damaged_stock.php'     => 'Damaged Stock',
    'stock_audits'          => 'Physical Audit',
    'stock_audits.php'      => 'Physical Audit',
    'products'              => 'Product Master',
    'products.php'          => 'Product Master',
    'quick_products'        => 'Quick Product Editor',
    'quick_products.php'    => 'Quick Product Editor',
    'brands'                => 'Brands',
    'brands.php'            => 'Brands',
    'categories'            => 'Categories',
    'categories.php'        => 'Categories',
    'suppliers'             => 'Suppliers',
    'suppliers.php'         => 'Suppliers',
    'cheque_management'     => 'Cheque Management',
    'cheque_management.php' => 'Cheque Management',
    'bank_accounts'         => 'Bank Accounts',
    'bank_accounts.php'     => 'Bank Accounts',
    'bank_reconciliation'   => 'Bank Reconciliation',
    'bank_reconciliation.php' => 'Bank Reconciliation',
    'petty_cash'            => 'Petty Cash',
    'petty_cash.php'        => 'Petty Cash',
    'expenses'              => 'Expense Management',
    'expenses.php'          => 'Expense Management',
    'journal_entries'       => 'Journal Entries',
    'journal_entries.php'   => 'Journal Entries',
    'chart_of_accounts'     => 'Chart of Accounts',
    'chart_of_accounts.php' => 'Chart of Accounts',
    'general_ledger'        => 'General Ledger',
    'general_ledger.php'    => 'General Ledger',
    'accounting'            => 'Journal Entries',
    'accounting.php'        => 'Journal Entries',
    'reports'               => 'Reports',
    'reports.php'           => 'Reports',
    'branches'              => 'Branch Management',
    'branches.php'          => 'Branch Management',
    'users'                 => 'User Accounts',
    'users.php'             => 'User Accounts',
    'settings'              => 'System Settings',
    'settings.php'          => 'System Settings',
    'employees'             => 'Employee Directory',
    'employees.php'         => 'Employee Directory',
    'payroll'               => 'Payroll Management',
    'payroll.php'           => 'Payroll Management',
    'salary_advances'       => 'Salary Advances',
    'salary_advances.php'   => 'Salary Advances',
    'discounts'             => 'Discounts & Promotions',
    'discounts.php'         => 'Discounts & Promotions',
];

// ─── Contextual search mapping ────────────────────────────────────────────────
$contextualSearch = null;
$contextMap = [
    'inventory'             => ['placeholder' => 'Search products or barcode...', 'targetSelector' => '#searchInput, #productSearch, input[name="search"]'],
    'inventory.php'         => ['placeholder' => 'Search products or barcode...', 'targetSelector' => '#searchInput, #productSearch, input[name="search"]'],
    'products'              => ['placeholder' => 'Search products...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'products.php'          => ['placeholder' => 'Search products...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'quick_products'        => ['placeholder' => 'Search products, barcode, or SKU...', 'targetSelector' => '#qeSearchInput'],
    'quick_products.php'    => ['placeholder' => 'Search products, barcode, or SKU...', 'targetSelector' => '#qeSearchInput'],
    'customers'             => ['placeholder' => 'Search customers...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'customers.php'         => ['placeholder' => 'Search customers...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'suppliers'             => ['placeholder' => 'Search suppliers...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'suppliers.php'         => ['placeholder' => 'Search suppliers...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'purchase_orders'       => ['placeholder' => 'Search POs...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'purchase_orders.php'   => ['placeholder' => 'Search POs...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'grns'                  => ['placeholder' => 'Search GRNs...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'grns.php'              => ['placeholder' => 'Search GRNs...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'returns'               => ['placeholder' => 'Search returns...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'returns.php'           => ['placeholder' => 'Search returns...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'reports'               => ['placeholder' => 'Search reports...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'reports.php'           => ['placeholder' => 'Search reports...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'cheque_management'     => ['placeholder' => 'Search cheques...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'cheque_management.php' => ['placeholder' => 'Search cheques...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'bank_accounts'         => ['placeholder' => 'Search accounts...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'bank_accounts.php'     => ['placeholder' => 'Search accounts...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'expenses'              => ['placeholder' => 'Search expenses...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'expenses.php'          => ['placeholder' => 'Search expenses...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'journal_entries'       => ['placeholder' => 'Search JE # or description...', 'targetSelector' => '#searchInput, #filterJournalSearch'],
    'journal_entries.php'   => ['placeholder' => 'Search JE # or description...', 'targetSelector' => '#searchInput, #filterJournalSearch'],
    'chart_of_accounts'     => ['placeholder' => 'Search code or account name...', 'targetSelector' => '#searchInput, #filterCoaSearch'],
    'chart_of_accounts.php' => ['placeholder' => 'Search code or account name...', 'targetSelector' => '#searchInput, #filterCoaSearch'],
    'general_ledger'        => ['placeholder' => 'Filter ledger entries...', 'targetSelector' => '#searchInput, #filterGlSearch'],
    'general_ledger.php'    => ['placeholder' => 'Filter ledger entries...', 'targetSelector' => '#searchInput, #filterGlSearch'],
    'employees'             => ['placeholder' => 'Search employees...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'employees.php'         => ['placeholder' => 'Search employees...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'payroll'               => ['placeholder' => 'Search payroll...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'payroll.php'           => ['placeholder' => 'Search payroll...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'salary_advances'       => ['placeholder' => 'Search advances...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'salary_advances.php'   => ['placeholder' => 'Search advances...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'discounts'             => ['placeholder' => 'Search promotions...', 'targetSelector' => '#searchInput, input[name="search"]'],
    'discounts.php'         => ['placeholder' => 'Search promotions...', 'targetSelector' => '#searchInput, input[name="search"]'],
];
if (isset($contextMap[$navScript])) {
    $contextualSearch = $contextMap[$navScript];
}

// ─── Active branch name ────────────────────────────────────────────────────────
$navBranchName = '';
foreach (($_SESSION['user_branches'] ?? []) as $branch) {
    if ((int)$branch['id'] === $navBranchId) {
        $navBranchName = $branch['name'] ?? '';
        break;
    }
}

// ─── CSRF token for JS ────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Output JS config block ───────────────────────────────────────────────────
$navUserData = [
    'id'                 => (int)$navUser['id'],
    'username'           => $navUser['username'] ?? '',
    'full_name'          => $navUser['full_name'] ?? $navUser['username'] ?? 'User',
    'role'               => $navUser['role_code'] ?? '',
    'role_name'          => $navUser['role_name'] ?? ucfirst($navUser['role_code'] ?? 'User'),
    'active_branch_id'   => $navBranchId,
    'active_branch_name' => $navBranchName,
];

?>
<meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<script>
window.CURTISS_NAV = <?= json_encode([
    'categories'      => $categories,
    'currentScript'   => $navScript,
    'currentPageLabel'=> $pageLabelMap[$navScript] ?? null,
    'currentPageIcon' => '',
    'user'            => $navUserData,
    'hasPOS'          => Auth::hasPermission('pos.access'),
    'bizDay'          => $bizDay,
    'quickActions'    => $quickActions,
    'contextualSearch'=> $contextualSearch,
    'isSpecialUser'   => $navIsSpecial,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
</script>
