<?php
/**
 * Curtiss POS - Central Cheque Management View (Redesigned Modern Theme)
 * Pure White / Glassmorphism layout for Received (Customer) and Issued (Supplier) Cheques.
 * Fully compliant with CURTISS_MASTER_RULES:
 * - Fixed screen viewport (calc(100vh - 110px)) and internal table scroll (.prod-table-scroll)
 * - Sticky uppercase table headers
 * - Alternating zebra striping (rgba(248, 250, 252, 0.57) / #ffffff)
 * - Standard 48px row heights & matching 28px badge/button heights
 * - Clean currency formatting without redundant LKR prefix
 * - Headline only (no subtitle taglines)
 * - Full-row click for details modal
 * - Separate tabs for Received (Customer) and Issued (Supplier) Cheques
 * - Floating top-right header actions with searchable filter & soft red Trash Bin button
 * - Realtime search in floating bottom nav bar (with action column button exclusion)
 * - Double-submit protection on all mutation forms
 */
$tab = $tab ?? 'received';
$hasActiveFilters = !empty($statusFilter) || !empty($supplierFilter) || !empty($customerFilter) || !empty($searchFilter);
$view = $view ?? 'active';
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <!-- Filter Bubble Dropdown Wrapper -->
    <div class="top-filter-wrapper" id="top-filter-wrapper" style="position: relative;">
        <button type="button" class="top-bubble-pill top-filter-pill <?= $hasActiveFilters ? 'has-active' : '' ?>" id="top-filter-trigger" onclick="toggleTopFilterDropdown(event)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter</span>
            <?php if ($hasActiveFilters): ?><span class="top-filter-badge"></span><?php endif; ?>
        </button>

        <!-- Floating Filter Dropdown Panel -->
        <div class="top-filter-dropdown" id="top-filter-dropdown">
            <form method="GET" action="cheque_management.php" id="chequeFilterForm">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                <div class="filter-dropdown-header">
                    <span>Filter <?= $tab === 'received' ? 'Received' : 'Issued' ?> Cheques</span>
                    <?php if ($hasActiveFilters): ?>
                        <a href="cheque_management.php?tab=<?= htmlspecialchars($tab) ?>&view=<?= htmlspecialchars($view) ?>" class="filter-reset-link">Reset Filters</a>
                    <?php endif; ?>
                </div>
                <div class="filter-dropdown-body" style="display: flex; flex-direction: column; gap: 12px;">
                    <?php if ($tab === 'received'): ?>
                        <div class="inv-field">
                            <label class="inv-label">Status Filter</label>
                            <select name="status" class="inv-select curtiss-select-enhance">
                                <option value="">All Statuses</option>
                                <option value="all_pending" <?= $statusFilter === 'all_pending' ? 'selected' : '' ?>>All Active (In-Hand & Deposited)</option>
                                <option value="in_hand" <?= $statusFilter === 'in_hand' ? 'selected' : '' ?>>In Hand</option>
                                <option value="deposited" <?= $statusFilter === 'deposited' ? 'selected' : '' ?>>Deposited</option>
                                <option value="cleared" <?= $statusFilter === 'cleared' ? 'selected' : '' ?>>Cleared</option>
                                <option value="returned" <?= $statusFilter === 'returned' ? 'selected' : '' ?>>Returned (Bounced)</option>
                                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label">Customer</label>
                            <select name="customer_id" id="customerSelectFilter" class="inv-select curtiss-select-enhance">
                                <option value="">All Customers</option>
                                <?php if (!empty($customers)): ?>
                                    <?php foreach ($customers as $cus): ?>
                                        <option value="<?= $cus['id'] ?>" <?= ($customerFilter ?? '') == $cus['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cus['name']) ?> (<?= htmlspecialchars($cus['customer_code'] ?? '#' . $cus['id']) ?>)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="inv-field">
                            <label class="inv-label">Status Filter</label>
                            <select name="status" class="inv-select curtiss-select-enhance">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="cleared" <?= $statusFilter === 'cleared' ? 'selected' : '' ?>>Cleared</option>
                                <option value="bounced" <?= $statusFilter === 'bounced' ? 'selected' : '' ?>>Bounced</option>
                                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label">Supplier</label>
                            <select name="supplier_id" id="supplierSelectFilter" class="inv-select curtiss-select-enhance">
                                <option value="">All Suppliers</option>
                                <?php if (!empty($suppliers)): ?>
                                    <?php foreach ($suppliers as $sup): ?>
                                        <option value="<?= $sup['id'] ?>" <?= ($supplierFilter ?? '') == $sup['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sup['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="inv-field">
                        <label class="inv-label">Search Cheque #</label>
                        <input type="text" name="search" class="inv-input" value="<?= htmlspecialchars($searchFilter) ?>" placeholder="Cheque number or bank...">
                    </div>

                    <button type="submit" class="inv-btn inv-btn-primary" style="height: 38px; width: 100%; justify-content: center; margin-top: 4px;">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toggle History View Pill -->
    <?php if ($tab === 'received'): ?>
        <a href="cheque_management.php?tab=received&status=<?= ($statusFilter === 'all_pending' || $statusFilter === 'in_hand') ? 'all' : 'all_pending' ?>&view=<?= htmlspecialchars($view) ?>" class="top-bubble-pill top-action-btn" title="Toggle Cheque History View">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
            <?= ($statusFilter === 'all_pending' || $statusFilter === 'in_hand') ? 'All History' : 'Active Only' ?>
        </a>
    <?php else: ?>
        <a href="cheque_management.php?tab=issued&status=<?= ($statusFilter === 'pending') ? 'all' : 'pending' ?>&view=<?= htmlspecialchars($view) ?>" class="top-bubble-pill top-action-btn" title="Toggle Cheque History View">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
            <?= ($statusFilter === 'pending') ? 'All History' : 'Pending Only' ?>
        </a>
    <?php endif; ?>

    <!-- Soft Red Trash Bin / Active Toggle Button -->
    <?php if ($view === 'deleted'): ?>
        <a href="cheque_management.php?tab=<?= htmlspecialchars($tab) ?>&status=all" class="top-bubble-pill top-action-btn" style="background: #f0fdf4; color: #166534; border-color: #86efac;" title="View Active Cheques">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Active Cheques
        </a>
    <?php else: ?>
        <a href="cheque_management.php?tab=<?= htmlspecialchars($tab) ?>&view=deleted&status=all" class="top-bubble-pill top-action-btn" style="background: #fef2f2; color: #dc2626; border-color: #fca5a5;" title="View Trash Bin">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Trash Bin
        </a>
    <?php endif; ?>

    <a href="cheque_management.php?tab=<?= htmlspecialchars($tab) ?>" class="top-bubble-pill top-action-btn" title="Refresh Data">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
        Refresh
    </a>
</div>

<style>
/* Modern Glassmorphism & Fixed Viewport Theme Styles */
.inv-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin: 0;
}

/* Tabs */
.cheque-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 16px;
    height: 34px;
    min-height: 34px;
    line-height: 32px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    transition: all 0.18s ease;
    box-sizing: border-box;
}
.cheque-tab-btn.active {
    background: #0f172a;
    color: #ffffff;
    border: 1px solid #0f172a;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
}
.cheque-tab-btn:not(.active) {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.cheque-tab-btn:not(.active):hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* Action Buttons */
.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
    font-family: inherit;
    box-sizing: border-box;
}
.inv-btn-sm {
    padding: 0 10px;
    font-size: 11px;
}
.inv-btn-ghost {
    background: rgba(255,255,255,0.9);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.inv-btn-ghost:hover {
    background: #ffffff;
    border-color: #cbd5e1;
}
.inv-btn-primary {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}
.inv-btn-primary:hover { background: #1e293b; }
.inv-btn-success {
    background: #16a34a;
    color: #ffffff;
    border-color: #16a34a;
}
.inv-btn-success:hover { background: #15803d; }
.inv-btn-info {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
}
.inv-btn-info:hover { background: #0369a1; }
.inv-btn-danger {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}
.inv-btn-danger:hover { background: #b91c1c; }

/* Table and Zebra Striping */
.prod-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
}
.prod-table thead tr th {
    height: 38px;
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    box-sizing: border-box;
    text-align: left;
}
.prod-table thead tr th.col-right { text-align: right; }
.prod-table thead tr th.col-center { text-align: center; }

.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.15s ease;
}
.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:hover            { background: #f1f5f9 !important; }

.prod-table td {
    height: 48px;
    padding: 0 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
    box-sizing: border-box;
    border-bottom: 1px solid #f1f5f9;
}
.prod-table td.col-right { text-align: right; }
.prod-table td.col-center { text-align: center; }

/* Table Pagination Footer */
.prod-table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    font-size: 0.78rem;
    flex-wrap: wrap;
    gap: 8px;
    flex-shrink: 0;
}
.prod-footer-info {
    font-weight: 700;
    color: #64748b;
}
.prod-footer-controls {
    display: flex;
    align-items: center;
    gap: 14px;
}
.prod-per-page {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    color: #64748b;
}
.prod-per-page select {
    height: 32px;
    padding: 0 8px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.78rem;
    background: #fff;
    color: #0f172a;
    outline: none;
}
.prod-pagination {
    display: flex;
    align-items: center;
    gap: 4px;
}
.prod-pg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.12s ease;
}
.prod-pg-btn:hover:not(.disabled):not(.active) {
    background: #f1f5f9;
    border-color: #94a3b8;
}
.prod-pg-btn.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}
.prod-pg-btn.disabled {
    opacity: 0.45;
    cursor: not-allowed;
    background: #f8fafc;
}

/* Code Pills & Badges */
.inv-code-pill {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.78rem;
    font-weight: 800;
    background: #f1f5f9;
    color: #0f172a;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.prod-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    text-transform: uppercase;
    box-sizing: border-box;
}
.prod-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.badge-pending, .badge-in_hand {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}
.badge-pending .prod-badge-dot, .badge-in_hand .prod-badge-dot { background: #d97706; }

.badge-deposited {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.badge-deposited .prod-badge-dot { background: #0284c7; }

.badge-cleared {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.badge-cleared .prod-badge-dot { background: #16a34a; }

.badge-bounced, .badge-returned {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.badge-bounced .prod-badge-dot, .badge-returned .prod-badge-dot { background: #dc2626; }

.badge-cancelled {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.badge-cancelled .prod-badge-dot { background: #94a3b8; }

/* Top Floating Filter Dropdown Panel */
.top-bubble-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 14px;
    height: 34px;
    min-height: 34px;
    line-height: 32px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.18s ease;
    box-sizing: border-box;
}
.top-bubble-pill:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.top-filter-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 20px 40px -10px rgba(0,0,0,0.12);
    z-index: 1100;
    padding: 16px;
}
.top-filter-dropdown.show { display: block; }
.filter-dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f5f9;
    font-weight: 800;
    font-size: 0.85rem;
    color: #0f172a;
}
.filter-reset-link {
    font-size: 0.72rem;
    color: #dc2626;
    text-decoration: none;
    font-weight: 700;
}

/* Modals */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(6px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: #ffffff;
    border-radius: 18px;
    width: 100%;
    max-width: 540px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    max-height: 90vh;
}
.modal-header {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.modal-close-btn {
    background: transparent;
    border: none;
    font-size: 1.4rem;
    color: #94a3b8;
    cursor: pointer;
}
.modal-close-btn:hover { color: #0f172a; }
.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}
.modal-footer {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.inv-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.inv-label {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
}
.inv-input, .inv-select {
    height: 38px;
    padding: 0 14px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.85rem;
    background: #ffffff;
    color: #0f172a;
    outline: none;
    box-sizing: border-box;
    width: 100%;
}
</style>

<!-- MAIN FIXED VIEWPORT WORKSPACE -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- PAGE HEADER (HEADLINE & TABS) -->
    <div class="inv-header" style="flex-shrink: 0; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">
                    Cheque Management
                    <?php if ($view === 'deleted'): ?>
                        <span style="font-size: 0.82rem; color: #dc2626; font-weight: 700; background: #fee2e2; padding: 2px 10px; border-radius: 999px; margin-left: 8px;">Trash Bin</span>
                    <?php endif; ?>
                </h1>
            </div>

            <!-- Tab Switcher -->
            <div style="display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; padding: 3px; border-radius: 999px; border: 1px solid #e2e8f0;">
                <a href="cheque_management.php?tab=received" class="cheque-tab-btn <?= $tab === 'received' ? 'active' : '' ?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                    Received Cheques (Customers)
                </a>
                <a href="cheque_management.php?tab=issued" class="cheque-tab-btn <?= $tab === 'issued' ? 'active' : '' ?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                    Issued Cheques (Suppliers)
                </a>
            </div>
        </div>

        <!-- Summary KPI Counters -->
        <div style="font-size: 0.82rem; font-weight: 700; color: #334155; background: #f8fafc; padding: 6px 16px; border-radius: 999px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
            <?php if ($tab === 'received'): ?>
                <span><span style="color: #d97706; font-size: 1.1rem; vertical-align: middle;">•</span> In Hand: <strong style="color: #0f172a;"><?= number_format($summary['count_in_hand'] ?? 0) ?></strong> (<strong style="color: #d97706; font-family: ui-monospace, SFMono-Regular, monospace;"><?= number_format($summary['total_in_hand'] ?? 0, 2) ?></strong>)</span>
                <span><span style="color: #0284c7; font-size: 1.1rem; vertical-align: middle;">•</span> Deposited: <strong style="color: #0f172a;"><?= number_format($summary['count_deposited'] ?? 0) ?></strong> (<strong style="color: #0284c7; font-family: ui-monospace, SFMono-Regular, monospace;"><?= number_format($summary['total_deposited'] ?? 0, 2) ?></strong>)</span>
            <?php else: ?>
                <span><span style="color: #d97706; font-size: 1.1rem; vertical-align: middle;">•</span> Total Pending Cheques: <strong style="color: #0f172a;"><?= number_format($countPending) ?></strong></span>
                <span><span style="color: #d97706; font-size: 1.1rem; vertical-align: middle;">•</span> Total Pending Amount: <strong style="color: #d97706; font-family: ui-monospace, SFMono-Regular, monospace;"><?= number_format($totPending, 2) ?></strong></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- CHEQUES REGISTER TABLE PANEL (FIXED SCREEN, INTERNAL SCROLL) -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.03);">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1; min-height: 0;">
            <table class="prod-table" id="chequeTable">
                <thead>
                    <?php if ($tab === 'received'): ?>
                        <tr>
                            <th>Cheque #</th>
                            <th>Banking Date</th>
                            <th>Customer</th>
                            <th>Cheque Bank</th>
                            <th>Deposited To</th>
                            <th class="col-center">Status</th>
                            <th class="col-right">Amount (LKR)</th>
                            <th class="col-right">Actions</th>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th>Cheque #</th>
                            <th>Banking Date</th>
                            <th>Supplier</th>
                            <th>Bank / Account</th>
                            <th class="col-center">Status</th>
                            <th class="col-right">Amount (LKR)</th>
                            <th class="col-right">Actions</th>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody id="chequeTableBody">
                    <?php if (empty($cheques)): ?>
                        <tr>
                            <td colspan="8" style="padding: 36px 16px; text-align: center; color: #94a3b8; font-weight: 500;">
                                <?= $view === 'deleted' ? 'No soft-deleted cheques found in Trash Bin.' : 'No cheques found matching your search criteria.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cheques as $c): ?>
                            <?php if ($tab === 'received'): ?>
                                <?php
                                $statusBadgeClass = 'badge-in_hand';
                                if ($c['status'] === 'deposited') $statusBadgeClass = 'badge-deposited';
                                elseif ($c['status'] === 'cleared') $statusBadgeClass = 'badge-cleared';
                                elseif ($c['status'] === 'returned') $statusBadgeClass = 'badge-returned';
                                elseif ($c['status'] === 'cancelled') $statusBadgeClass = 'badge-cancelled';
                                ?>
                                <tr class="inv-row" onclick="viewCustomerChequeDetails(<?= $c['id'] ?>)" style="cursor: pointer;">
                                    <td>
                                        <span class="inv-code-pill"><?= htmlspecialchars($c['cheque_no']) ?></span>
                                    </td>
                                    <td style="font-weight: 600; color: #475569;"><?= htmlspecialchars($c['cheque_date']) ?></td>
                                    <td style="font-weight: 700; color: #0f172a;">
                                        <?= htmlspecialchars($c['customer_name']) ?>
                                        <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 500;"><?= htmlspecialchars($c['customer_code'] ?? '') ?></div>
                                    </td>
                                    <td style="font-weight: 600; color: #334155;"><?= htmlspecialchars($c['bank_name']) ?></td>
                                    <td>
                                        <?php if (!empty($c['bank_account_name'])): ?>
                                            <div style="font-weight: 600; color: #334155;"><?= htmlspecialchars($c['destination_bank_name'] ?? $c['bank_account_name']) ?></div>
                                            <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 500;"><?= htmlspecialchars($c['bank_account_number'] ?? '') ?></div>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-style: italic;">Not Deposited</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-center">
                                        <span class="prod-badge <?= $statusBadgeClass ?>">
                                            <span class="prod-badge-dot"></span> <?= ucfirst(str_replace('_', ' ', $c['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="col-right" style="font-weight: 800; color: #0f172a; font-size: 0.9rem;">
                                        <?= number_format($c['amount'], 2) ?>
                                    </td>
                                    <td class="col-right">
                                        <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                            <?php if ($view === 'deleted'): ?>
                                                <button type="button" onclick="event.stopPropagation(); restoreCustomerCheque(<?= $c['id'] ?>)" class="inv-btn inv-btn-sm" style="background: #dcfce7; color: #15803d; border-color: #86efac;" title="Restore Cheque">
                                                    Restore
                                                </button>
                                            <?php else: ?>
                                                <?php if ($c['status'] === 'in_hand'): ?>
                                                    <button type="button" onclick='event.stopPropagation(); openDepositCustomerModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-info" title="Deposit to Bank Account">
                                                        Deposit
                                                    </button>
                                                    <button type="button" onclick='event.stopPropagation(); openClearCustomerModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-success" title="Clear Cheque">
                                                        Clear
                                                    </button>
                                                    <button type="button" onclick='event.stopPropagation(); openReturnCustomerModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-danger" title="Mark as Returned">
                                                        Return
                                                    </button>
                                                    <button type="button" onclick='event.stopPropagation(); openCancelCustomerModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-ghost" style="color: #64748b;" title="Cancel Cheque">
                                                        Cancel
                                                    </button>
                                                    <button type="button" onclick="event.stopPropagation(); deleteCustomerCheque(<?= $c['id'] ?>)" class="inv-btn inv-btn-sm" style="padding: 0 8px; color: #dc2626; background: #fef2f2; border-color: #fca5a5;" title="Move to Trash">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    </button>
                                                <?php elseif ($c['status'] === 'deposited'): ?>
                                                    <button type="button" onclick='event.stopPropagation(); openClearCustomerModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-success" title="Clear Cheque">
                                                        Clear
                                                    </button>
                                                    <button type="button" onclick='event.stopPropagation(); openReturnCustomerModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-danger" title="Mark as Returned">
                                                        Return
                                                    </button>
                                                    <button type="button" onclick="event.stopPropagation(); deleteCustomerCheque(<?= $c['id'] ?>)" class="inv-btn inv-btn-sm" style="padding: 0 8px; color: #dc2626; background: #fef2f2; border-color: #fca5a5;" title="Move to Trash">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    </button>
                                                <?php elseif ($c['status'] === 'cleared'): ?>
                                                    <button type="button" onclick='event.stopPropagation(); openReturnCustomerModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-danger" title="Mark as Returned (Bounce after Clearing)">
                                                        Return
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <!-- Issued (Supplier) Cheques -->
                                <?php
                                $statusBadgeClass = 'badge-pending';
                                if ($c['status'] === 'cleared') $statusBadgeClass = 'badge-cleared';
                                elseif ($c['status'] === 'bounced') $statusBadgeClass = 'badge-bounced';
                                elseif ($c['status'] === 'cancelled') $statusBadgeClass = 'badge-cancelled';
                                ?>
                                <tr class="inv-row" onclick="viewChequeDetails(<?= $c['id'] ?>)" style="cursor: pointer;">
                                    <td>
                                        <span class="inv-code-pill"><?= htmlspecialchars($c['cheque_number']) ?></span>
                                    </td>
                                    <td style="font-weight: 600; color: #475569;"><?= htmlspecialchars($c['cheque_date']) ?></td>
                                    <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($c['supplier_name']) ?></td>
                                    <td>
                                        <div style="font-weight: 600; color: #334155;"><?= htmlspecialchars($c['bank_name']) ?></div>
                                        <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 500;"><?= htmlspecialchars($c['bank_account_name'] ?? 'Pending Account') ?></div>
                                    </td>
                                    <td class="col-center">
                                        <span class="prod-badge <?= $statusBadgeClass ?>">
                                            <span class="prod-badge-dot"></span> <?= ucfirst($c['status']) ?>
                                        </span>
                                    </td>
                                    <td class="col-right" style="font-weight: 800; color: #0f172a; font-size: 0.9rem;">
                                        <?= number_format($c['amount'], 2) ?>
                                    </td>
                                    <td class="col-right">
                                        <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                            <?php if ($view === 'deleted'): ?>
                                                <button type="button" onclick="event.stopPropagation(); restoreCheque(<?= $c['id'] ?>)" class="inv-btn inv-btn-sm" style="background: #dcfce7; color: #15803d; border-color: #86efac;" title="Restore Cheque">
                                                    Restore
                                                </button>
                                            <?php else: ?>
                                                <?php if ($c['status'] === 'pending'): ?>
                                                    <?php if (Auth::hasPermission('clear_cheque')): ?>
                                                        <button type="button" onclick='event.stopPropagation(); openClearModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-success" title="Clear Cheque">
                                                            Clear
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (Auth::hasPermission('bounce_cheque')): ?>
                                                        <button type="button" onclick='event.stopPropagation(); openBounceModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-danger" title="Mark as Bounced">
                                                            Bounce
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (Auth::hasPermission('cancel_cheque')): ?>
                                                        <button type="button" onclick='event.stopPropagation(); openCancelModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-ghost" style="color: #64748b;" title="Cancel Cheque">
                                                            Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                <?php elseif ($c['status'] === 'cleared' && Auth::hasPermission('bounce_cheque')): ?>
                                                    <button type="button" onclick='event.stopPropagation(); openBounceModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-danger" title="Mark as Bounced">
                                                        Bounce
                                                    </button>
                                                <?php endif; ?>

                                                <?php if (in_array($c['status'], ['pending', 'cancelled']) && Auth::hasPermission('cancel_cheque')): ?>
                                                    <button type="button" onclick="event.stopPropagation(); deleteCheque(<?= $c['id'] ?>)" class="inv-btn inv-btn-sm" style="padding: 0 8px; color: #dc2626; background: #fef2f2; border-color: #fca5a5;" title="Move to Trash">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer with Pagination Controls -->
        <?php
        $paginationQueryParams = array_filter([
            'tab'         => $tab,
            'status'      => $statusFilter,
            'supplier_id' => $supplierFilter,
            'customer_id' => $customerFilter ?? null,
            'search'      => $searchFilter,
            'view'        => $view
        ]);
        ?>
        <div class="prod-table-footer">
            <div class="prod-footer-info">
                Showing <?= count($cheques) > 0 ? (($page - 1) * $limit + 1) : 0 ?> to <?= min($page * $limit, $totalItems) ?> of <?= $totalItems ?> entries
            </div>

            <div class="prod-footer-controls">
                <div class="prod-per-page">
                    <span>Rows per page:</span>
                    <select id="chequeRowsPerPage" onchange="changeChequeRowsPerPage(this.value)">
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="75" <?= $limit == 75 ? 'selected' : '' ?>>75</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                        <option value="500" <?= $limit == 500 ? 'selected' : '' ?>>500</option>
                    </select>
                </div>

                <div class="prod-pagination">
                    <?php if ($page > 1): ?>
                        <a href="cheque_management.php?<?= http_build_query(array_merge($paginationQueryParams, ['page' => $page - 1, 'limit' => $limit])) ?>" class="prod-pg-btn">← Prev</a>
                    <?php else: ?>
                        <span class="prod-pg-btn disabled">← Prev</span>
                    <?php endif; ?>

                    <?php 
                    $startPg = max(1, $page - 2);
                    $endPg = min($totalPages, $page + 2);
                    for ($p = $startPg; $p <= $endPg; $p++): 
                    ?>
                        <a href="cheque_management.php?<?= http_build_query(array_merge($paginationQueryParams, ['page' => $p, 'limit' => $limit])) ?>" class="prod-pg-btn <?= ($p === (int)$page) ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="cheque_management.php?<?= http_build_query(array_merge($paginationQueryParams, ['page' => $page + 1, 'limit' => $limit])) ?>" class="prod-pg-btn">Next →</a>
                    <?php else: ?>
                        <span class="prod-pg-btn disabled">Next →</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SUPPLIER CHEQUE MODALS -->

<!-- SUPPLIER CLEAR MODAL -->
<div id="clearModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Clear Cheque to Bank Account</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('clearModal')">&times;</button>
        </div>
        <form onsubmit="handleClearSubmit(event)">
            <input type="hidden" id="clearChequeId">
            <div class="modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px 16px; border-radius: 12px;">
                    <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;" id="clearChequeNum">Cheque: -</div>
                    <div style="font-size: 0.85rem; color: #166534; font-weight: 700; margin-top: 4px;" id="clearChequeAmount">Amount: 0.00</div>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Select Issuing Bank Account *</label>
                    <select id="clearBankAccountId" class="inv-select curtiss-select-enhance" required>
                        <option value="">-- Choose Real Bank Account --</option>
                        <?php foreach ($bankAccounts as $ba): ?>
                            <option value="<?= $ba['id'] ?>"><?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Clearing Date *</label>
                    <input type="date" id="clearDate" class="inv-input" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Bank Clearing / Statement Ref (Optional)</label>
                    <input type="text" id="clearBankRef" class="inv-input" placeholder="e.g. Bank Statement Tx #">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('clearModal')">Cancel</button>
                <button type="submit" id="btnSubmitClear" class="inv-btn inv-btn-success">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirm Clearing
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SUPPLIER BOUNCE MODAL -->
<div id="bounceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: #dc2626;">Mark Cheque as Bounced / Returned</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('bounceModal')">&times;</button>
        </div>
        <form onsubmit="handleBounceSubmit(event)">
            <input type="hidden" id="bounceChequeId">
            <div class="modal-body">
                <div style="background: #fef2f2; border: 1px solid #fecdd3; padding: 14px 16px; border-radius: 12px; color: #991b1b; font-size: 0.83rem; line-height: 1.5;">
                    <strong style="font-weight: 800;">Warning:</strong> Bouncing this cheque will reverse supplier settlement, restore outstanding balance on allocated GRNs, and reverse double-entry GL accounting entries.
                </div>
                <div class="inv-field">
                    <label class="inv-label">Reason for Bouncing *</label>
                    <input type="text" id="bounceReason" class="inv-input" placeholder="e.g. Insufficient funds / Signature mismatch" required>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Bounced Date *</label>
                    <input type="date" id="bounceDate" class="inv-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('bounceModal')">Cancel</button>
                <button type="submit" id="btnSubmitBounce" class="inv-btn inv-btn-danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Confirm Bounce
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SUPPLIER CANCEL MODAL -->
<div id="cancelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cancel Pending Cheque</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('cancelModal')">&times;</button>
        </div>
        <form onsubmit="handleCancelSubmit(event)">
            <input type="hidden" id="cancelChequeId">
            <div class="modal-body">
                <div class="inv-field">
                    <label class="inv-label">Cancellation Reason *</label>
                    <input type="text" id="cancelReason" class="inv-input" placeholder="e.g. Cheque damaged / Entry error" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('cancelModal')">Close</button>
                <button type="submit" id="btnSubmitCancel" class="inv-btn inv-btn-primary">
                    Confirm Cancellation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SUPPLIER DETAILS MODAL -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Supplier Cheque Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('detailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailsBody"></div>
        <div class="modal-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('detailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- CUSTOMER CHEQUE MODALS -->

<!-- CUSTOMER DEPOSIT MODAL -->
<div id="depositCustModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: #0369a1;">Deposit Customer Cheque to Bank</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('depositCustModal')">&times;</button>
        </div>
        <form onsubmit="handleCustomerDepositSubmit(event)">
            <input type="hidden" id="depCustChequeId">
            <div class="modal-body">
                <div style="background: #f0f9ff; border: 1px solid #bae6fd; padding: 14px 16px; border-radius: 12px;">
                    <div style="font-weight: 800; color: #0369a1; font-size: 0.95rem;" id="depCustChequeNum">Cheque: -</div>
                    <div style="font-size: 0.85rem; color: #0f172a; font-weight: 700; margin-top: 4px;" id="depCustChequeAmount">Amount: 0.00</div>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Select Company Destination Bank Account *</label>
                    <select id="depCustBankAccountId" class="inv-select curtiss-select-enhance" required>
                        <option value="">-- Choose Company Bank Account --</option>
                        <?php foreach ($bankAccounts as $ba): ?>
                            <option value="<?= $ba['id'] ?>"><?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Deposit Date *</label>
                    <input type="date" id="depCustDate" class="inv-input" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Deposit Slip / Reference No (Optional)</label>
                    <input type="text" id="depCustRef" class="inv-input" placeholder="e.g. Deposit Slip #12345">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('depositCustModal')">Cancel</button>
                <button type="submit" id="btnSubmitCustDeposit" class="inv-btn inv-btn-info">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirm Deposit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CUSTOMER CLEAR MODAL -->
<div id="clearCustModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: #15803d;">Clear Customer Cheque to Bank Account</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('clearCustModal')">&times;</button>
        </div>
        <form onsubmit="handleCustomerClearSubmit(event)">
            <input type="hidden" id="clearCustChequeId">
            <div class="modal-body">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 14px 16px; border-radius: 12px;">
                    <div style="font-weight: 800; color: #15803d; font-size: 0.95rem;" id="clearCustChequeNum">Cheque: -</div>
                    <div style="font-size: 0.85rem; color: #0f172a; font-weight: 700; margin-top: 4px;" id="clearCustChequeAmount">Amount: 0.00</div>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Company Destination Bank Account *</label>
                    <select id="clearCustBankAccountId" class="inv-select curtiss-select-enhance" required>
                        <option value="">-- Choose Company Bank Account --</option>
                        <?php foreach ($bankAccounts as $ba): ?>
                            <option value="<?= $ba['id'] ?>"><?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Clearing Date *</label>
                    <input type="date" id="clearCustDate" class="inv-input" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Bank Clearing / Statement Ref (Optional)</label>
                    <input type="text" id="clearCustBankRef" class="inv-input" placeholder="e.g. Bank Statement Clearing Ref">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('clearCustModal')">Cancel</button>
                <button type="submit" id="btnSubmitCustClear" class="inv-btn inv-btn-success">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirm Clearing
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CUSTOMER RETURN MODAL -->
<div id="returnCustModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: #dc2626;">Return / Bounce Customer Cheque</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('returnCustModal')">&times;</button>
        </div>
        <form onsubmit="handleCustomerReturnSubmit(event)">
            <input type="hidden" id="returnCustChequeId">
            <div class="modal-body">
                <div style="background: #fef2f2; border: 1px solid #fecdd3; padding: 14px 16px; border-radius: 12px; color: #991b1b; font-size: 0.83rem; line-height: 1.5;">
                    <strong style="font-weight: 800;">Warning:</strong> Returning this cheque will immediately restore the customer's outstanding credit debt in their account, record a cheque_return ledger event, and reverse Chart of Accounts journal entries.
                </div>
                <div class="inv-field">
                    <label class="inv-label">Reason for Return *</label>
                    <input type="text" id="returnCustReason" class="inv-input" placeholder="e.g. Insufficient funds / Payment stopped" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('returnCustModal')">Cancel</button>
                <button type="submit" id="btnSubmitCustReturn" class="inv-btn inv-btn-danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Confirm Return
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CUSTOMER CANCEL MODAL -->
<div id="cancelCustModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cancel In-Hand Customer Cheque</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('cancelCustModal')">&times;</button>
        </div>
        <form onsubmit="handleCustomerCancelSubmit(event)">
            <input type="hidden" id="cancelCustChequeId">
            <div class="modal-body">
                <div class="inv-field">
                    <label class="inv-label">Cancellation Reason *</label>
                    <input type="text" id="cancelCustReason" class="inv-input" placeholder="e.g. Customer requested cancellation / Entry mistake" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('cancelCustModal')">Close</button>
                <button type="submit" id="btnSubmitCustCancel" class="inv-btn inv-btn-primary">
                    Confirm Cancellation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CUSTOMER DETAILS MODAL -->
<div id="detailsCustModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Customer Cheque Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('detailsCustModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailsCustBody"></div>
        <div class="modal-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('detailsCustModal')">Close</button>
        </div>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
    /* ── Initialize Searchable Select Component ── */
    function initSearchableSelects() {
        document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
            if (window.CurtissSearchableSelect && !el.dataset.selectEnhanced) {
                CurtissSearchableSelect.attach(el);
                el.dataset.selectEnhanced = 'true';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initSearchableSelects);

    /* ── Rows Per Page Selector Handler ── */
    function changeChequeRowsPerPage(limit) {
        const url = new URL(window.location.href);
        url.searchParams.set('limit', limit);
        url.searchParams.set('page', 1);
        window.location.href = url.toString();
    }

    /* ── Floating Header Filter Dropdown Toggle ── */
    function toggleTopFilterDropdown(e) {
        if (e) e.stopPropagation();
        const dropdown = document.getElementById('top-filter-dropdown');
        const trigger  = document.getElementById('top-filter-trigger');
        if (!dropdown) return;
        const isShown = dropdown.classList.contains('show');
        if (isShown) {
            dropdown.classList.remove('show');
            if (trigger) trigger.classList.remove('active');
        } else {
            dropdown.classList.add('show');
            if (trigger) trigger.classList.add('active');
            initSearchableSelects();
        }
    }

    document.addEventListener('click', (e) => {
        const wrapper  = document.getElementById('top-filter-wrapper');
        const dropdown = document.getElementById('top-filter-dropdown');
        const trigger  = document.getElementById('top-filter-trigger');
        if (wrapper && !wrapper.contains(e.target) && dropdown) {
            dropdown.classList.remove('show');
            if (trigger) trigger.classList.remove('active');
        }
    });

    /* ── Realtime Search in Floating Bottom Nav Bar (Excluding Action Buttons) ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search cheque #, name, bank, or amount...',
                onSearch: function(query) {
                    filterTableRealtime(query);
                },
                onEnter: function(query) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('search', query.trim());
                    url.searchParams.set('page', 1);
                    window.location.href = url.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    function filterTableRealtime(query) {
        const q = query.toLowerCase().trim();
        const rows = document.querySelectorAll('.prod-table tbody tr');
        rows.forEach(tr => {
            const dataCells = Array.from(tr.querySelectorAll('td:not(.col-right)'));
            const amtCell = tr.querySelector('td.col-right:nth-last-child(2)');
            if (amtCell) dataCells.push(amtCell);

            const text = dataCells.map(td => td.textContent).join(' ').toLowerCase();
            if (!q || text.includes(q)) {
                tr.style.display = '';
            } else {
                tr.style.display = 'none';
            }
        });
    }

    /* ── Supplier Cheque Modal Triggers & Submissions ── */
    function openClearModal(c) {
        document.getElementById('clearChequeId').value = c.id;
        document.getElementById('clearChequeNum').innerText = `Cheque #: ${c.cheque_number} (${c.supplier_name})`;
        document.getElementById('clearChequeAmount').innerText = `Amount: LKR ${parseFloat(c.amount).toLocaleString('en-US',{minimumFractionDigits:2})}`;
        if (c.bank_account_id) {
            const sel = document.getElementById('clearBankAccountId');
            if (sel) sel.value = c.bank_account_id;
        }
        document.getElementById('clearModal').style.display = 'flex';
        setTimeout(initSearchableSelects, 50);
    }

    function openBounceModal(c) {
        document.getElementById('bounceChequeId').value = c.id;
        document.getElementById('bounceModal').style.display = 'flex';
    }

    function openCancelModal(c) {
        document.getElementById('cancelChequeId').value = c.id;
        document.getElementById('cancelModal').style.display = 'flex';
    }

    async function handleClearSubmit(e) {
        e.preventDefault();
        const chequeId = document.getElementById('clearChequeId').value;
        const bankAccId = document.getElementById('clearBankAccountId').value;
        const cDate = document.getElementById('clearDate').value;
        const bankRef = document.getElementById('clearBankRef').value;

        const btn = document.getElementById('btnSubmitClear');
        btn.disabled = true;

        try {
            const res = await fetch('cheque_api.php?action=clear_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: chequeId, bank_account_id: bankAccId, cleared_date: cDate, bank_ref: bankRef })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Failed to clear cheque.');
            btn.disabled = false;
        }
    }

    async function handleBounceSubmit(e) {
        e.preventDefault();
        const chequeId = document.getElementById('bounceChequeId').value;
        const reason = document.getElementById('bounceReason').value;
        const bDate = document.getElementById('bounceDate').value;

        const btn = document.getElementById('btnSubmitBounce');
        btn.disabled = true;

        try {
            const res = await fetch('cheque_api.php?action=bounce_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: chequeId, bounced_reason: reason, bounced_date: bDate })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Failed to bounce cheque.');
            btn.disabled = false;
        }
    }

    async function handleCancelSubmit(e) {
        e.preventDefault();
        const chequeId = document.getElementById('cancelChequeId').value;
        const reason = document.getElementById('cancelReason').value;

        const btn = document.getElementById('btnSubmitCancel');
        btn.disabled = true;

        try {
            const res = await fetch('cheque_api.php?action=cancel_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: chequeId, cancellation_reason: reason })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Failed to cancel cheque.');
            btn.disabled = false;
        }
    }

    async function viewChequeDetails(id) {
        try {
            const res = await fetch(`cheque_api.php?action=get_cheque_details&cheque_id=${id}`);
            const data = await res.json();
            if (!data.success) {
                alert(data.message);
                return;
            }
            const c = data.data;
            let statusBadgeClass = 'badge-pending';
            if (c.status === 'cleared') statusBadgeClass = 'badge-cleared';
            else if (c.status === 'bounced') statusBadgeClass = 'badge-bounced';
            else if (c.status === 'cancelled') statusBadgeClass = 'badge-cancelled';

            let html = `
                <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.85rem;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px; border-radius: 12px;">
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Cheque Number</span>
                            <div style="font-weight: 800; color: #0f172a; margin-top: 2px;" class="inv-code-pill">${c.cheque_number}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Status</span>
                            <div style="margin-top: 2px;">
                                <span class="prod-badge ${statusBadgeClass}">
                                    <span class="prod-badge-dot"></span> ${c.status}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Supplier</span>
                            <div style="font-weight: 700; color: #0f172a; margin-top: 2px;">${c.supplier_name}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Amount</span>
                            <div style="font-weight: 800; color: #166534; font-size: 1.05rem; margin-top: 2px;">LKR ${parseFloat(c.amount).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Banking Date</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.cheque_date}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Issuing Bank</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.bank_name}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Payment Voucher</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.payment_no || '-'}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Created By</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.created_by_name || '-'}</div>
                        </div>
                    </div>
            `;

            if (c.status === 'cleared') {
                html += `
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 10px; color: #166534;">
                        <strong>Cleared Info:</strong> Cleared by ${c.cleared_by_name || '-'} on ${c.cleared_at || '-'}<br>
                        <strong>Bank:</strong> ${c.bank_account_name || '-'} (${c.bank_account_number || ''})<br>
                        ${c.bank_ref ? '<strong>Bank Ref:</strong> ' + c.bank_ref : ''}
                    </div>
                `;
            } else if (c.status === 'bounced') {
                html += `
                    <div style="background: #fef2f2; border: 1px solid #fecdd3; padding: 12px; border-radius: 10px; color: #991b1b;">
                        <strong>Bounced Info:</strong> Bounced by ${c.bounced_by_name || '-'} on ${c.bounced_at || '-'}<br>
                        <strong>Reason:</strong> ${c.bounced_reason || '-'}
                    </div>
                `;
            } else if (c.status === 'cancelled') {
                html += `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 10px; color: #475569;">
                        <strong>Cancelled Info:</strong> Cancelled by ${c.cancelled_by_name || '-'} on ${c.cancelled_at || '-'}<br>
                        <strong>Reason:</strong> ${c.cancellation_reason || '-'}
                    </div>
                `;
            }

            html += `
                <div>
                    <h4 style="margin: 8px 0; font-weight: 800; color: #0f172a; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">GRN Allocation History</h4>
                    <div class="prod-table-scroll" style="max-height: 180px; overflow-y: auto;">
                        <table class="prod-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>GRN #</th>
                                    <th class="col-right">GRN Total</th>
                                    <th class="col-right">Allocated</th>
                                    <th class="col-center">Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>`;
            if (c.allocations && c.allocations.length > 0) {
                c.allocations.forEach(a => {
                    html += `
                        <tr class="inv-row">
                            <td style="font-weight: 700;">GRN #${a.grn_number}</td>
                            <td class="col-right" style="color: #64748b;">${parseFloat(a.actual_grn_total).toFixed(2)}</td>
                            <td class="col-right" style="font-weight: 800; color: #0f172a;">${parseFloat(a.allocated_amount).toFixed(2)}</td>
                            <td class="col-center" style="text-transform: uppercase; font-weight: 700; font-size: 0.72rem; color: #475569;">${a.payment_status}</td>
                        </tr>
                    `;
                });
            } else {
                html += `<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 12px;">No direct GRN allocations found.</td></tr>`;
            }
            html += `</tbody></table></div></div></div>`;
            document.getElementById('detailsBody').innerHTML = html;
            document.getElementById('detailsModal').style.display = 'flex';
        } catch (e) {
            console.error(e);
            alert('Error fetching details.');
        }
    }

    async function deleteCheque(id) {
        if (!confirm('Move this cheque to Trash Bin?')) return;
        try {
            const res = await fetch('cheque_api.php?action=delete_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: id })
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Failed to delete cheque.');
        }
    }

    async function restoreCheque(id) {
        if (!confirm('Restore this cheque from Trash Bin?')) return;
        try {
            const res = await fetch('cheque_api.php?action=restore_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: id })
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Failed to restore cheque.');
        }
    }

    /* ── Customer Cheque Modal Triggers & Submissions ── */
    function openDepositCustomerModal(c) {
        document.getElementById('depCustChequeId').value = c.id;
        document.getElementById('depCustChequeNum').innerText = `Cheque #: ${c.cheque_no} (${c.customer_name})`;
        document.getElementById('depCustChequeAmount').innerText = `Amount: LKR ${parseFloat(c.amount).toLocaleString('en-US',{minimumFractionDigits:2})}`;
        document.getElementById('depositCustModal').style.display = 'flex';
        setTimeout(initSearchableSelects, 50);
    }

    function openClearCustomerModal(c) {
        document.getElementById('clearCustChequeId').value = c.id;
        document.getElementById('clearCustChequeNum').innerText = `Cheque #: ${c.cheque_no} (${c.customer_name})`;
        document.getElementById('clearCustChequeAmount').innerText = `Amount: LKR ${parseFloat(c.amount).toLocaleString('en-US',{minimumFractionDigits:2})}`;
        if (c.bank_account_id) {
            const sel = document.getElementById('clearCustBankAccountId');
            if (sel) sel.value = c.bank_account_id;
        }
        document.getElementById('clearCustModal').style.display = 'flex';
        setTimeout(initSearchableSelects, 50);
    }

    function openReturnCustomerModal(c) {
        document.getElementById('returnCustChequeId').value = c.id;
        document.getElementById('returnCustModal').style.display = 'flex';
    }

    function openCancelCustomerModal(c) {
        document.getElementById('cancelCustChequeId').value = c.id;
        document.getElementById('cancelCustModal').style.display = 'flex';
    }

    async function handleCustomerDepositSubmit(e) {
        e.preventDefault();
        const chequeId = document.getElementById('depCustChequeId').value;
        const bankAccId = document.getElementById('depCustBankAccountId').value;
        const depDate = document.getElementById('depCustDate').value;
        const depRef = document.getElementById('depCustRef').value;

        const btn = document.getElementById('btnSubmitCustDeposit');
        btn.disabled = true;

        try {
            const res = await fetch('cheque_api.php?action=deposit_customer_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: chequeId, bank_account_id: bankAccId, deposit_date: depDate, deposit_ref: depRef })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Failed to deposit customer cheque.');
            btn.disabled = false;
        }
    }

    async function handleCustomerClearSubmit(e) {
        e.preventDefault();
        const chequeId = document.getElementById('clearCustChequeId').value;
        const bankAccId = document.getElementById('clearCustBankAccountId').value;
        const clrDate = document.getElementById('clearCustDate').value;
        const bankRef = document.getElementById('clearCustBankRef').value;

        const btn = document.getElementById('btnSubmitCustClear');
        btn.disabled = true;

        try {
            const res = await fetch('cheque_api.php?action=clear_customer_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: chequeId, bank_account_id: bankAccId, cleared_date: clrDate, bank_ref: bankRef })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Failed to clear customer cheque.');
            btn.disabled = false;
        }
    }

    async function handleCustomerReturnSubmit(e) {
        e.preventDefault();
        const chequeId = document.getElementById('returnCustChequeId').value;
        const reason = document.getElementById('returnCustReason').value;

        const btn = document.getElementById('btnSubmitCustReturn');
        btn.disabled = true;

        try {
            const res = await fetch('cheque_api.php?action=return_customer_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: chequeId, reason: reason })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Failed to return customer cheque.');
            btn.disabled = false;
        }
    }

    async function handleCustomerCancelSubmit(e) {
        e.preventDefault();
        const chequeId = document.getElementById('cancelCustChequeId').value;
        const reason = document.getElementById('cancelCustReason').value;

        const btn = document.getElementById('btnSubmitCustCancel');
        btn.disabled = true;

        try {
            const res = await fetch('cheque_api.php?action=cancel_customer_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: chequeId, cancellation_reason: reason })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Failed to cancel customer cheque.');
            btn.disabled = false;
        }
    }

    async function deleteCustomerCheque(id) {
        if (!confirm('Move this customer cheque to Trash Bin?')) return;
        try {
            const res = await fetch('cheque_api.php?action=delete_customer_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: id })
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Failed to delete customer cheque.');
        }
    }

    async function restoreCustomerCheque(id) {
        if (!confirm('Restore this customer cheque from Trash Bin?')) return;
        try {
            const res = await fetch('cheque_api.php?action=restore_customer_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: id })
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Failed to restore customer cheque.');
        }
    }

    async function viewCustomerChequeDetails(id) {
        try {
            const res = await fetch(`cheque_api.php?action=get_customer_cheque_details&cheque_id=${id}`);
            const data = await res.json();
            if (!data.success) {
                alert(data.message);
                return;
            }
            const c = data.data;
            let statusBadgeClass = 'badge-in_hand';
            if (c.status === 'deposited') statusBadgeClass = 'badge-deposited';
            else if (c.status === 'cleared') statusBadgeClass = 'badge-cleared';
            else if (c.status === 'returned') statusBadgeClass = 'badge-returned';
            else if (c.status === 'cancelled') statusBadgeClass = 'badge-cancelled';

            let html = `
                <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.85rem;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px; border-radius: 12px;">
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Cheque Number</span>
                            <div style="font-weight: 800; color: #0f172a; margin-top: 2px;" class="inv-code-pill">${c.cheque_no}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Status</span>
                            <div style="margin-top: 2px;">
                                <span class="prod-badge ${statusBadgeClass}">
                                    <span class="prod-badge-dot"></span> ${c.status}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Customer</span>
                            <div style="font-weight: 700; color: #0f172a; margin-top: 2px;">${c.customer_name} (${c.customer_code || '#' + c.customer_id})</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Amount</span>
                            <div style="font-weight: 800; color: #166534; font-size: 1.05rem; margin-top: 2px;">LKR ${parseFloat(c.amount).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Banking Date</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.cheque_date}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Cheque Bank</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.bank_name}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Received Date</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.received_date || '-'}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Payment Voucher</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.payment_no || '-'}</div>
                        </div>
                    </div>
            `;

            if (c.status === 'deposited') {
                html += `
                    <div style="background: #f0f9ff; border: 1px solid #bae6fd; padding: 12px; border-radius: 10px; color: #0369a1;">
                        <strong>Deposit Info:</strong> Deposited by ${c.deposited_by_name || '-'} on ${c.deposited_at || '-'}<br>
                        <strong>Bank:</strong> ${c.destination_bank_name || c.bank_account_name || '-'} (${c.bank_account_number || ''})<br>
                        ${c.deposit_ref ? '<strong>Deposit Ref:</strong> ' + c.deposit_ref : ''}
                    </div>
                `;
            } else if (c.status === 'cleared') {
                html += `
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 10px; color: #166534;">
                        <strong>Cleared Info:</strong> Cleared by ${c.cleared_by_name || '-'} on ${c.cleared_at || '-'}<br>
                        <strong>Bank:</strong> ${c.destination_bank_name || c.bank_account_name || '-'} (${c.bank_account_number || ''})<br>
                        ${c.bank_ref ? '<strong>Bank Ref:</strong> ' + c.bank_ref : ''}
                    </div>
                `;
            } else if (c.status === 'returned') {
                html += `
                    <div style="background: #fef2f2; border: 1px solid #fecdd3; padding: 12px; border-radius: 10px; color: #991b1b;">
                        <strong>Returned Info:</strong> Returned by ${c.returned_by_name || '-'} on ${c.returned_at || '-'}<br>
                        <strong>Reason:</strong> ${c.return_reason || '-'}
                    </div>
                `;
            } else if (c.status === 'cancelled') {
                html += `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 10px; color: #475569;">
                        <strong>Cancelled Info:</strong> Cancelled by ${c.cancelled_by_name || '-'} on ${c.cancelled_at || '-'}<br>
                        <strong>Reason:</strong> ${c.cancellation_reason || '-'}
                    </div>
                `;
            }

            html += `</div>`;
            document.getElementById('detailsCustBody').innerHTML = html;
            document.getElementById('detailsCustModal').style.display = 'flex';
        } catch (e) {
            console.error(e);
            alert('Error fetching customer cheque details.');
        }
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>
