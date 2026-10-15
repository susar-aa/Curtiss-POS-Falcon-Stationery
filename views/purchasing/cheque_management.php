<?php
/**
 * Curtiss POS - Central Cheque Management View (Redesigned Modern Theme)
 * Pure White / Glassmorphism layout for clearing, bouncing, and cancelling supplier cheques.
 * Follows exact design rules: Searchable Select Combobox, Top-Right Floating Header Filter Dropdown & Bottom Nav Search.
 */
$hasActiveFilters = !empty($statusFilter) || !empty($supplierFilter) || !empty($searchFilter);
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header (Contains Top-Right Filter Pill Dropdown & Actions) -->
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
                <div class="filter-dropdown-header">
                    <span>Filter Cheques</span>
                    <?php if ($hasActiveFilters): ?>
                        <a href="cheque_management.php" class="filter-reset-link">Reset Filters</a>
                    <?php endif; ?>
                </div>
                <div class="filter-dropdown-body" style="display: flex; flex-direction: column; gap: 12px;">
                    <div class="inv-field">
                        <label class="inv-label">Status Filter</label>
                        <select name="status" class="inv-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="cleared" <?= $statusFilter === 'cleared' ? 'selected' : '' ?>>Cleared</option>
                            <option value="bounced" <?= $statusFilter === 'bounced' ? 'selected' : '' ?>>Bounced</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="inv-field">
                        <label class="inv-label">Supplier (Search & Select)</label>
                        <select name="supplier_id" id="supplierSelectFilter" class="inv-select curtiss-select-enhance">
                            <option value="">All Suppliers</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= $sup['id'] ?>" <?= $supplierFilter == $sup['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sup['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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

    <a href="cheque_management.php?status=<?= ($statusFilter === 'pending') ? 'all' : 'pending' ?>" class="top-bubble-pill top-action-btn" title="Toggle Cheque History View">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
        <?= ($statusFilter === 'pending') ? 'Cheque History' : 'View Pending' ?>
    </a>

    <a href="cheque_management.php" class="top-bubble-pill top-action-btn" title="Refresh Data">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
        Refresh
    </a>
</div>

<style>
/* Modern Glassmorphism Theme Styles */
.inv-header {
    margin-bottom: 1.25rem;
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
}
.inv-subtitle {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 4px;
    font-weight: 500;
}

/* Buttons */
.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
    font-family: inherit;
}
.inv-btn-sm {
    padding: 5px 12px;
    font-size: 11px;
}
.inv-btn-ghost {
    background: rgba(255,255,255,0.9);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    backdrop-filter: blur(8px);
}
.inv-btn-ghost:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.inv-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15,23,42,0.25);
}
.inv-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
}
.inv-btn-success {
    background: #166534;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(22,101,52,0.2);
}
.inv-btn-success:hover {
    background: #14532d;
    transform: translateY(-1px);
}
.inv-btn-danger {
    background: #dc2626;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(220,38,38,0.2);
}
.inv-btn-danger:hover {
    background: #b91c1c;
    transform: translateY(-1px);
}

/* Glass Cards */
.inv-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
    margin-bottom: 1.25rem;
    padding: 1.25rem;
    width: 100%;
    box-sizing: border-box;
}

/* Stat KPI Cards Grid */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 1.25rem;
}
.stat-card-enhanced {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px -5px rgba(0,0,0,0.08);
}
.stat-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.stat-card-title {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
}
.stat-card-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.stat-card-amount {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
}
.stat-card-count {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 600;
}

/* Table Styling */
.inv-table-wrap {
    width: 100%;
    overflow-x: auto;
    border-radius: 12px;
}
.inv-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.83rem;
}
.inv-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}
.inv-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
    transition: background 0.15s ease;
}
.inv-row:hover td {
    background: #f8fafc;
}
.inv-row:last-child td {
    border-bottom: none;
}

/* Table Footer Pagination */
.prod-table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    border-radius: 0 0 16px 16px;
    flex-wrap: wrap;
    gap: 12px;
}
.prod-footer-info {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 500;
}
.prod-footer-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}
.prod-per-page {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
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
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    text-transform: uppercase;
}
.prod-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.badge-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-pending .prod-badge-dot { background: #d97706; }
.badge-cleared { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.badge-cleared .prod-badge-dot { background: #166534; }
.badge-bounced { background: #fee2e2; color: #b91c1c; border: 1px solid #fecdd3; }
.badge-bounced .prod-badge-dot { background: #dc2626; }
.badge-cancelled { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.badge-cancelled .prod-badge-dot { background: #64748b; }

/* Form Inputs */
.inv-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.inv-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.inv-input, .inv-select {
    width: 100%;
    height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0 12px;
    font-size: 0.85rem;
    color: #0f172a;
    outline: none;
    box-sizing: border-box;
    background: #ffffff;
    transition: all 0.15s ease;
}
.inv-input:focus, .inv-select:focus {
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}

/* Modal Overlays */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(6px);
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}
.modal-content {
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 580px;
    max-height: calc(100vh - 40px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalSlideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}
.modal-header {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.modal-close-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
    padding: 4px;
    border-radius: 6px;
    transition: color 0.15s ease;
}
.modal-close-btn:hover {
    color: #0f172a;
}
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
</style>

<!-- PAGE HEADER -->
<div class="inv-header">
    <div>
        <h1 class="inv-title">Supplier Cheque Management</h1>
        <p class="inv-subtitle">Central control register for clearing, bouncing, and cancelling issued supplier cheques</p>
    </div>
</div>

<!-- CHEQUES REGISTER TABLE CONTAINER -->
<div class="inv-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em;">
                <?= ($statusFilter === 'pending') ? 'Pending Cheques' : ($statusFilter === 'all' ? 'All Cheques History' : ucfirst($statusFilter) . ' Cheques') ?>
            </h2>
        </div>
        <div style="font-size: 0.85rem; font-weight: 700; color: #334155; background: #f8fafc; padding: 7px 18px; border-radius: 999px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
            <span><span style="color: #d97706; font-size: 1.1rem; vertical-align: middle;">•</span> Total Cheques : <strong style="color: #0f172a;"><?= number_format($countPending) ?></strong></span>
            <span><span style="color: #d97706; font-size: 1.1rem; vertical-align: middle;">•</span> Total Pending Amount : <strong style="color: #d97706; font-family: ui-monospace, SFMono-Regular, monospace;">Rs: <?= number_format($totPending, 2) ?></strong></span>
        </div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Cheque #</th>
                    <th>Cheque Date</th>
                    <th>Supplier</th>
                    <th>Bank / Account</th>
                    <th style="text-align: right;">Amount (LKR)</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cheques)): ?>
                    <tr>
                        <td colspan="7" style="padding: 32px 16px; text-align: center; color: #94a3b8; font-weight: 500;">
                            No supplier cheques found matching your search criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cheques as $c): ?>
                        <tr class="inv-row">
                            <td>
                                <span class="inv-code-pill"><?= htmlspecialchars($c['cheque_number']) ?></span>
                            </td>
                            <td style="font-weight: 600; color: #475569;"><?= htmlspecialchars($c['cheque_date']) ?></td>
                            <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($c['supplier_name']) ?></td>
                            <td>
                                <div style="font-weight: 600; color: #334155;"><?= htmlspecialchars($c['bank_name']) ?></div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;"><?= htmlspecialchars($c['bank_account_name'] ?? 'Pending Account') ?></div>
                            </td>
                            <td style="text-align: right; font-weight: 800; color: #0f172a; font-size: 0.9rem;">
                                LKR <?= number_format($c['amount'], 2) ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($c['status'] === 'pending'): ?>
                                    <span class="prod-badge badge-pending">
                                        <span class="prod-badge-dot"></span> Pending
                                    </span>
                                <?php elseif ($c['status'] === 'cleared'): ?>
                                    <span class="prod-badge badge-cleared">
                                        <span class="prod-badge-dot"></span> Cleared
                                    </span>
                                <?php elseif ($c['status'] === 'bounced'): ?>
                                    <span class="prod-badge badge-bounced">
                                        <span class="prod-badge-dot"></span> Bounced
                                    </span>
                                <?php else: ?>
                                    <span class="prod-badge badge-cancelled">
                                        <span class="prod-badge-dot"></span> Cancelled
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                    <button type="button" onclick="viewChequeDetails(<?= $c['id'] ?>)" class="inv-btn inv-btn-sm inv-btn-ghost" title="View Full Details">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        View
                                    </button>

                                    <?php if ($c['status'] === 'pending'): ?>
                                        <?php if (Auth::hasPermission('clear_cheque')): ?>
                                            <button type="button" onclick='openClearModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-success" title="Clear Cheque">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                Clear
                                            </button>
                                        <?php endif; ?>
                                        <?php if (Auth::hasPermission('bounce_cheque')): ?>
                                            <button type="button" onclick='openBounceModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-danger" title="Mark as Bounced">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                                Bounce
                                            </button>
                                        <?php endif; ?>
                                        <?php if (Auth::hasPermission('cancel_cheque')): ?>
                                            <button type="button" onclick='openCancelModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-ghost" style="color: #64748b;" title="Cancel Cheque">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                                Cancel
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif ($c['status'] === 'cleared' && Auth::hasPermission('bounce_cheque')): ?>
                                        <button type="button" onclick='openBounceModal(<?= json_encode($c) ?>)' class="inv-btn inv-btn-sm inv-btn-danger" title="Mark as Bounced">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                            Bounce
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer with Pagination Controls -->
    <?php
    $paginationQueryParams = array_filter([
        'status'      => $statusFilter,
        'supplier_id' => $supplierFilter,
        'search'      => $searchFilter
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
                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                    <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
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

<!-- CLEAR MODAL -->
<div id="clearModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Clear Cheque to Real Bank Account</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('clearModal')">&times;</button>
        </div>
        <form onsubmit="handleClearSubmit(event)">
            <input type="hidden" id="clearChequeId">
            <div class="modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px 16px; border-radius: 12px;">
                    <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;" id="clearChequeNum">Cheque: -</div>
                    <div style="font-size: 0.85rem; color: #166534; font-weight: 700; margin-top: 4px;" id="clearChequeAmount">Amount: LKR 0.00</div>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Select Company Bank Account *</label>
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

<!-- BOUNCE MODAL -->
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

<!-- CANCEL MODAL -->
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

<!-- DETAILS MODAL -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cheque Audit & Allocation Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('detailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailsBody"></div>
        <div class="modal-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('detailsModal')">Close</button>
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

    /* ── Realtime Search in Floating Bottom Nav Bar ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search cheque number or supplier...',
                onSearch: function(query) {
                    filterTableRealtime(query);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    function filterTableRealtime(query) {
        const q = query.toLowerCase().trim();
        const rows = document.querySelectorAll('.inv-table tbody tr');
        rows.forEach(tr => {
            const text = tr.textContent.toLowerCase();
            if (!q || text.includes(q)) {
                tr.style.display = '';
            } else {
                tr.style.display = 'none';
            }
        });
    }

    function openClearModal(c) {
        document.getElementById('clearChequeId').value = c.id;
        document.getElementById('clearChequeNum').innerText = `Cheque #: ${c.cheque_number} (${c.supplier_name})`;
        document.getElementById('clearChequeAmount').innerText = `Amount: LKR ${parseFloat(c.amount).toLocaleString('en-US',{minimumFractionDigits:2})}`;
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
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Cheque Date</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.cheque_date}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Bank Name</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.bank_name}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Payment Voucher</span>
                            <div style="font-weight: 600; color: #334155; margin-top: 2px;">${c.payment_no}</div>
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
                        <strong>Cleared Info:</strong> Cleared by ${c.cleared_by_name || '-'} on ${c.cleared_at || '-'} (Bank: ${c.bank_account_name || '-'})
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
                    <div class="inv-table-wrap">
                        <table class="inv-table">
                            <thead>
                                <tr>
                                    <th>GRN #</th>
                                    <th style="text-align: right;">GRN Total</th>
                                    <th style="text-align: right;">Allocated</th>
                                    <th style="text-align: center;">GRN Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>`;
            if (c.allocations && c.allocations.length > 0) {
                c.allocations.forEach(a => {
                    html += `
                        <tr class="inv-row">
                            <td style="font-weight: 700;">GRN #${a.grn_number}</td>
                            <td style="text-align: right; color: #64748b;">LKR ${parseFloat(a.actual_grn_total).toFixed(2)}</td>
                            <td style="text-align: right; font-weight: 800; color: #0f172a;">LKR ${parseFloat(a.allocated_amount).toFixed(2)}</td>
                            <td style="text-align: center; text-transform: uppercase; font-weight: 700; font-size: 0.72rem; color: #475569;">${a.payment_status}</td>
                        </tr>
                    `;
                });
            } else {
                html += `<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 12px;">No direct GRN allocations found.</td></tr>`;
            }
            html += `</tbody>mtable></div></div></div>`;
            document.getElementById('detailsBody').innerHTML = html;
            document.getElementById('detailsModal').style.display = 'flex';
        } catch (e) {
            console.error(e);
            alert('Error fetching details.');
        }
    }

    async function handleClearSubmit(e) {
        e.preventDefault();
        const chequeId = document.getElementById('clearChequeId').value;
        const bankAccId = document.getElementById('clearBankAccountId').value;
        const cDate = document.getElementById('clearDate').value;

        const btn = document.getElementById('btnSubmitClear');
        btn.disabled = true;

        try {
            const res = await fetch('cheque_api.php?action=clear_cheque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cheque_id: chequeId, bank_account_id: bankAccId, cleared_date: cDate })
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

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>
