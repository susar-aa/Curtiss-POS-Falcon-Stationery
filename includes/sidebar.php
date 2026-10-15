<?php
/**
 * Curtiss POS - UI Shell Sidebar Template (Stage 1 to Stage 4 Purchasing & GRN)
 */

$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">C</div>
        <div class="sidebar-brand-text">Curtiss POS</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main Navigation</div>

        <a href="dashboard.php" class="nav-item <?= ($currentScript === 'dashboard.php' || $currentScript === 'index.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </span>
            <span>Dashboard</span>
        </a>

        <?php if (Auth::hasPermission('pos.access')): ?>
        <a href="pos.php" class="nav-item <?= ($currentScript === 'pos.php') ? 'active' : '' ?>" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <path d="M6 8h12"></path>
                    <path d="M6 12h8"></path>
                    <path d="M6 16h4"></path>
                </svg>
            </span>
            <span style="font-weight: 700;">POS Billing Terminal</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('pos.access') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER)): ?>
        <a href="sales_history.php" class="nav-item <?= in_array($currentScript, ['sales_history.php', 'sales.php']) ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </span>
            <span>Sales History</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('view_cash_drawer') || Auth::hasPermission('start_business_day')): ?>
        <a href="cash_drawer.php" class="nav-item <?= ($currentScript === 'cash_drawer.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M12 12h.01"></path>
                    <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"></path>
                </svg>
            </span>
            <span>Cash Drawer & Day Closing</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('returns.view') || Auth::hasPermission('returns.create')): ?>
        <a href="returns.php" class="nav-item <?= ($currentScript === 'returns.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </span>
            <span>Sales Returns & Exchanges</span>
        </a>
        <?php endif; ?>


        <!-- Stage 5 Customers & Credit -->
        <div class="nav-section-title">Customers & Credit</div>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)): ?>
        <a href="customers.php" class="nav-item <?= (in_array($currentScript, ['customers.php', 'customer_create.php', 'customer_edit.php', 'customer_profile.php'])) ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </span>
            <span>Customers & Credit</span>
        </a>

        <?php if (Auth::hasPermission('customers.pay')): ?>
        <a href="customers.php" class="nav-item <?= ($currentScript === 'customers.php' && ($_GET['action'] ?? '') === 'collections') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </span>
            <span>Customer Collections</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('vouchers.view')): ?>
        <a href="vouchers.php" class="nav-item <?= ($currentScript === 'vouchers.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 12V8H4v4a2 2 0 1 0 0 4v4h16v-4a2 2 0 1 0 0-4z"></path>
                    <path d="M12 12v.01"></path>
                </svg>
            </span>
            <span>Gift Vouchers</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Stage 4 Purchasing & GRN -->
        <div class="nav-section-title">Purchasing & GRN</div>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)): ?>
        <a href="purchase_orders.php" class="nav-item <?= (in_array($currentScript, ['purchase_orders.php', 'po_create.php'])) ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </span>
            <span>Purchase Orders (PO)</span>
        </a>

        <a href="grns.php" class="nav-item <?= (in_array($currentScript, ['grns.php', 'grn_create.php'])) ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </span>
            <span>Goods Received (GRN)</span>
        </a>



        <?php if (Auth::hasPermission('view_supplier_returns') || Auth::hasPermission('create_supplier_return')): ?>
        <a href="supplier_returns.php" class="nav-item <?= ($currentScript === 'supplier_returns.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </span>
            <span>Supplier Returns</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Stage 3 Inventory Foundation -->
        <div class="nav-section-title">Inventory & Stock</div>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)): ?>
        <a href="inventory.php" class="nav-item <?= ($currentScript === 'inventory.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                </svg>
            </span>
            <span>Branch Inventory</span>
        </a>

        <a href="inventory_lots.php" class="nav-item <?= ($currentScript === 'inventory_lots.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </span>
            <span>Price & Cost Lots</span>
        </a>

        <a href="inventory_ledger.php" class="nav-item <?= ($currentScript === 'inventory_ledger.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="20" x2="12" y2="10"></line>
                    <line x1="18" y1="20" x2="18" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="16"></line>
                </svg>
            </span>
            <span>Stock Movement Ledger</span>
        </a>
        <a href="stock_transfers.php" class="nav-item <?= ($currentScript === 'stock_transfers.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="17 1 21 5 17 9"></polyline>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                    <polyline points="7 23 3 19 7 15"></polyline>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                </svg>
            </span>
            <span>Branch Stock Transfers</span>
        </a>

        <a href="stock_adjustments.php" class="nav-item <?= ($currentScript === 'stock_adjustments.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
            </span>
            <span>Stock Adjustments</span>
        </a>

        <a href="damaged_stock.php" class="nav-item <?= ($currentScript === 'damaged_stock.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </span>
            <span>Damaged & Wastage</span>
        </a>

        <a href="stock_audits.php" class="nav-item <?= ($currentScript === 'stock_audits.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </span>
            <span>Physical Stock Audit</span>
        </a>
        <?php endif; ?>

        <!-- Stage 2 Product Master Modules -->
        <div class="nav-section-title">Product Master</div>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)): ?>
        <a href="products.php" class="nav-item <?= (in_array($currentScript, ['products.php', 'product_create.php', 'product_edit.php'])) ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </span>
            <span>Product Master</span>
        </a>
        <a href="quick_products.php" class="nav-item <?= ($currentScript === 'quick_products.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
            </span>
            <span>Quick Product Editor</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
        <a href="brands.php" class="nav-item <?= ($currentScript === 'brands.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                </svg>
            </span>
            <span>Brands</span>
        </a>

        <a href="categories.php" class="nav-item <?= ($currentScript === 'categories.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                </svg>
            </span>
            <span>Categories</span>
        </a>

        <a href="suppliers.php" class="nav-item <?= ($currentScript === 'suppliers.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                </svg>
            </span>
            <span>Suppliers</span>
        </a>
        <?php endif; ?>

        <!-- HR & Payroll -->
        <?php if (Auth::hasPermission('employees.view') || Auth::hasPermission('payroll.view')): ?>
        <div class="nav-section-title">HR & Payroll</div>

        <?php if (Auth::hasPermission('employees.view')): ?>
        <a href="employees.php" class="nav-item <?= ($currentScript === 'employees.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </span>
            <span>Employee Directory</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('payroll.view')): ?>
        <a href="payroll.php" class="nav-item <?= ($currentScript === 'payroll.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
            </span>
            <span>Payroll Management</span>
        </a>
        <a href="salary_advances.php" class="nav-item <?= ($currentScript === 'salary_advances.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </span>
            <span>Salary Advances</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <div class="nav-section-title">Administration</div>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
        <a href="branches.php" class="nav-item <?= ($currentScript === 'branches.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l8-4v18"></path>
                    <path d="M19 21V11l-6-3"></path>
                </svg>
            </span>
            <span>Branch Management</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)): ?>
        <a href="users.php" class="nav-item <?= ($currentScript === 'users.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
            </span>
            <span>User Accounts</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)): ?>
        <a href="audit_logs.php" class="nav-item <?= ($currentScript === 'audit_logs.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </span>
            <span>Audit Logs</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('accounting.view') || Auth::hasPermission('view_petty_cash') || Auth::hasPermission('view_bank_accounts')): ?>
        <div class="nav-section-title">Accounting & Finance</div>
        <?php if (Auth::hasPermission('view_cheques')): ?>
        <a href="cheque_management.php" class="nav-item <?= ($currentScript === 'cheque_management.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                    <path d="M12 12h.01"></path>
                    <path d="M17 9v6"></path>
                </svg>
            </span>
            <span>Cheque Management</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('view_bank_accounts')): ?>
        <a href="bank_accounts.php" class="nav-item <?= ($currentScript === 'bank_accounts.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"></path>
                </svg>
            </span>
            <span>Bank Accounts & Deposits</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('manage_bank_reconciliation')): ?>
        <a href="bank_reconciliation.php" class="nav-item <?= ($currentScript === 'bank_reconciliation.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </span>
            <span>Bank Reconciliation</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('view_petty_cash')): ?>
        <a href="petty_cash.php" class="nav-item <?= ($currentScript === 'petty_cash.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                    <circle cx="12" cy="12" r="2"></circle>
                    <path d="M6 12h.01M18 12h.01"></path>
                </svg>
            </span>
            <span>Petty Cash & Expenses</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('accounting.view')): ?>
        <a href="journal_entries.php" class="nav-item <?= ($currentScript === 'journal_entries.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </span>
            <span>Journal Entries</span>
        </a>
        <a href="chart_of_accounts.php" class="nav-item <?= ($currentScript === 'chart_of_accounts.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                </svg>
            </span>
            <span>Chart of Accounts</span>
        </a>
        <a href="general_ledger.php" class="nav-item <?= ($currentScript === 'general_ledger.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </span>
            <span>General Ledger</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('view_reports')): ?>
        <a href="reports.php" class="nav-item <?= ($currentScript === 'reports.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </span>
            <span>Reports & Financials</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)): ?>
        <a href="settings.php" class="nav-item <?= ($currentScript === 'settings.php') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </span>
            <span>System Settings</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>
