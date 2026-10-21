<?php
/**
 * Curtiss POS - Sales History Master View
 * Adheres strictly to Curtiss Fixed Viewport, Sticky Header, Zebra Striping, and Drawer Details.
 */

$hasActiveFilters = !empty($filters['status']) || !empty($filters['date_preset']) || !empty($filters['search']);
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Controls -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="prod-btn prod-btn-ghost" onclick="startExportCSV(event)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Export CSV
    </button>
    <?php if (Auth::hasPermission('pos.access')): ?>
    <a href="pos" class="prod-btn prod-btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h12M6 12h8M6 16h4"/></svg>
        POS Terminal
    </a>
    <?php endif; ?>
</div>

<style>
/* ── Fixed Viewport Layout & Internal Table Scroll ── */
.sales-container {
    width: 100%;
    max-width: 100%;
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    overflow: hidden;
    padding-bottom: 8px;
}

.inv-header {
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 0.85rem;
}

.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin: 0;
}

/* ── Button Styles ── */
.prod-btn {
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
.prod-btn-ghost {
    background: rgba(255,255,255,0.92);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    backdrop-filter: blur(8px);
}
.prod-btn-ghost:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.prod-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15,23,42,0.25);
}
.prod-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(15,23,42,0.35);
}

/* ── Table Panel & Scroll ── */
.prod-table-panel {
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 14px;
    box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05);
    overflow: hidden;
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
.prod-table-scroll {
    overflow-y: auto;
    overflow-x: auto;
    flex: 1;
    min-height: 0;
}
.prod-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.83rem;
    text-align: left;
}
.prod-table th {
    background: #f8fafc;
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
    text-align: left;
}
.prod-table td {
    height: 48px;
    padding: 0 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
    box-sizing: border-box;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
}

/* Column Alignment Rules */
.prod-table .col-right {
    text-align: right !important;
}
.prod-table .col-center {
    text-align: center !important;
}

/* ── Alternating Zebra Striping ── */
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.15s ease;
}
.prod-table tbody tr:nth-child(even) { 
    background: rgba(248, 250, 252, 0.57); 
}
.prod-table tbody tr:nth-child(odd) { 
    background: #ffffff; 
}
.prod-table tbody tr:hover { 
    background: #f1f5f9 !important; 
}

/* ── Status Badges ── */
.prod-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    box-sizing: border-box;
}
.prod-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.prod-badge-active {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.prod-badge-active .dot { background: #16a34a; }

.prod-badge-warning {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.prod-badge-warning .dot { background: #f59e0b; }

.prod-badge-danger {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.prod-badge-danger .dot { background: #dc2626; }

.prod-badge-info {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.prod-badge-info .dot { background: #0284c7; }

/* In-cell Action Button */
.prod-act-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.prod-act-btn:hover {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}

/* ── Inline Filters (Height: 34px) ── */
.filter-select {
    height: 34px !important;
    min-height: 34px !important;
    padding: 0 14px !important;
    border-radius: 999px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    border: 1px solid #cbd5e1 !important;
    background: #ffffff !important;
    color: #0f172a !important;
    cursor: pointer;
    outline: none;
}

/* ── Table Footer & Pagination ── */
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    border-top: 1px solid #e2e8f0;
    background: #ffffff;
    flex-shrink: 0;
}
.pagination-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}
.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
}
.page-btn:hover:not(.disabled) {
    background: #f1f5f9;
    border-color: #94a3b8;
}
.page-btn.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}
.page-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ── Slide-in Drawer ── */
.drawer-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 2000;
}
.drawer-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 540px;
    background: #ffffff;
    box-shadow: -10px 0 30px rgba(0,0,0,0.15);
    z-index: 2001;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-open .drawer-backdrop { display: block; }
.drawer-open .drawer-panel { transform: translateX(0); }
</style>

<div class="sales-container">
    <!-- Main Headline & Top-Right Inline Filters -->
    <div class="inv-header">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Sales History</h1>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <!-- Date Preset Filter -->
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Date:</span>
                <select id="filterDatePreset" class="filter-select" onchange="applySalesFilters()">
                    <option value="" <?= empty($filters['date_preset']) ? 'selected' : '' ?>>All Time</option>
                    <option value="today" <?= ($filters['date_preset'] === 'today') ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= ($filters['date_preset'] === 'yesterday') ? 'selected' : '' ?>>Yesterday</option>
                    <option value="week" <?= ($filters['date_preset'] === 'week') ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="month" <?= ($filters['date_preset'] === 'month') ? 'selected' : '' ?>>This Month</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterStatus" class="filter-select" onchange="applySalesFilters()">
                    <option value="" <?= empty($filters['status']) ? 'selected' : '' ?>>All Statuses</option>
                    <option value="completed" <?= ($filters['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                    <option value="held" <?= ($filters['status'] === 'held') ? 'selected' : '' ?>>Held</option>
                    <option value="cancelled" <?= ($filters['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <?php if ($hasActiveFilters): ?>
            <button type="button" onclick="clearSalesFilters()" class="prod-btn prod-btn-ghost" style="height: 34px; padding: 0 12px; font-size: 11px; color: #dc2626; border-color: #fecaca; background: #fef2f2;">
                ✕ Reset
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Data Table Panel with Internal Scroll & Sticky Header -->
    <div class="prod-table-panel">
        <div class="prod-table-scroll">
            <table class="prod-table" id="salesTable">
                <thead>
                    <tr>
                        <th class="col-center" style="width: 50px;">#</th>
                        <th style="width: 100px;">Invoice #</th>
                        <th style="width: 150px;">Date & Time</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Payment Method</th>
                        <th class="col-center" style="width: 80px;">Items</th>
                        <th class="col-right" style="width: 120px;">Subtotal (LKR)</th>
                        <th class="col-right" style="width: 110px;">Discount (LKR)</th>
                        <th class="col-right" style="width: 130px;">Total (LKR)</th>
                        <th class="col-center" style="width: 110px;">Status</th>
                        <th class="col-right" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h12M6 12h8M6 16h4"/></svg>
                                <span style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No sales transactions found.</span>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Sales processed via the POS Billing Terminal will appear here automatically.</span>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($sales as $idx => $s): ?>
                    <tr class="sales-row" onclick="viewSaleDetails(<?= (int)$s['id'] ?>)" style="cursor: pointer;" title="Click row to view full receipt breakdown">
                        <td class="col-center" style="color: #64748b; font-size: 0.76rem; font-weight: 700;">
                            <?= ($page - 1) * $limit + $idx + 1 ?>
                        </td>
                        <td>
                            <span style="font-weight: 800; color: #0f172a; font-family: monospace; font-size: 0.88rem;">
                                #<?= htmlspecialchars($s['invoice_no']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #0f172a; font-size: 0.78rem;">
                                <?= date('Y-m-d', strtotime($s['created_at'])) ?>
                            </div>
                            <div style="font-size: 0.70rem; color: #64748b;">
                                <?= date('h:i A', strtotime($s['created_at'])) ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a; font-size: 0.82rem;">
                                <?= htmlspecialchars($s['customer_name']) ?>
                            </div>
                            <?php if (!empty($s['customer_phone'])): ?>
                            <div style="font-size: 0.70rem; color: #64748b; font-family: monospace;">
                                <?= htmlspecialchars($s['customer_phone']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #334155; font-size: 0.80rem;">
                                <?= htmlspecialchars($s['cashier_name']) ?>
                            </div>
                            <div style="font-size: 0.70rem; color: #94a3b8;">
                                <?= htmlspecialchars($s['branch_name']) ?>
                            </div>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 2px 8px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.72rem; font-weight: 700; color: #334155;">
                                <?= htmlspecialchars($s['payment_methods'] ?? 'Cash') ?>
                            </span>
                        </td>
                        <td class="col-center">
                            <span style="font-weight: 800; font-family: monospace; color: #0f172a; font-size: 0.82rem;">
                                <?= (float)$s['total_items'] ?>
                            </span>
                        </td>
                        <td class="col-right" style="font-family: monospace; font-weight: 700; color: #334155;">
                            <?= number_format((float)$s['subtotal'], 2) ?>
                        </td>
                        <td class="col-right" style="font-family: monospace; font-weight: 700; color: <?= ((float)$s['discount_amount'] > 0) ? '#dc2626' : '#94a3b8' ?>;">
                            <?= number_format((float)$s['discount_amount'], 2) ?>
                        </td>
                        <td class="col-right" style="font-family: monospace; font-weight: 800; color: #0f172a; font-size: 0.88rem;">
                            <?= number_format((float)$s['total_amount'], 2) ?>
                        </td>
                        <td class="col-center">
                            <?php if ($s['status'] === 'completed'): ?>
                                <span class="prod-badge prod-badge-active"><span class="dot"></span>Completed</span>
                            <?php elseif ($s['status'] === 'held'): ?>
                                <span class="prod-badge prod-badge-warning"><span class="dot"></span>Held</span>
                            <?php elseif ($s['status'] === 'cancelled'): ?>
                                <span class="prod-badge prod-badge-danger"><span class="dot"></span>Cancelled</span>
                            <?php else: ?>
                                <span class="prod-badge prod-badge-info"><span class="dot"></span><?= htmlspecialchars(ucfirst($s['status'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="col-right">
                            <button type="button" class="prod-act-btn" onclick="event.stopPropagation(); printReceipt(<?= (int)$s['id'] ?>);" title="Reprint Receipt">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                Print
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="noSearchResultsRow" style="display: none;">
                        <td colspan="12" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <span style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No matching sales transactions found.</span>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Try refining your search term or pressing Enter for full database lookup.</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer & Standard Pagination -->
        <div class="table-footer">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 0.78rem; color: #64748b;">
                    Showing <strong><?= count($sales) ?></strong> of <strong><?= $totalItems ?></strong> records
                </span>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Per Page:</span>
                    <select id="salesPerPage" onchange="changeSalesLimit(this.value)" class="filter-select" style="height: 28px !important; min-height: 28px !important; padding: 0 8px !important; font-size: 11px !important;">
                        <option value="25" <?= ($limit === 25) ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= ($limit === 50) ? 'selected' : '' ?>>50</option>
                        <option value="75" <?= ($limit === 75) ? 'selected' : '' ?>>75</option>
                        <option value="100" <?= ($limit === 100) ? 'selected' : '' ?>>100</option>
                        <option value="500" <?= ($limit === 500) ? 'selected' : '' ?>>500</option>
                    </select>
                </div>
            </div>

            <!-- Page Buttons -->
            <div class="pagination-controls">
                <a href="<?= buildSalesPageUrl(1) ?>" class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>" title="First Page">«</a>
                <a href="<?= buildSalesPageUrl(max(1, $page - 1)) ?>" class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>" title="Previous Page">‹</a>
                
                <span style="padding: 0 8px; font-size: 0.78rem; font-weight: 700; color: #0f172a;">
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <a href="<?= buildSalesPageUrl(min($totalPages, $page + 1)) ?>" class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>" title="Next Page">›</a>
                <a href="<?= buildSalesPageUrl($totalPages) ?>" class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>" title="Last Page">»</a>
            </div>
        </div>
    </div>
</div>

<!-- Slide-in Sale Details Drawer -->
<div id="saleDrawer" class="drawer-container">
    <div class="drawer-backdrop" onclick="closeSaleDrawer()"></div>
    <div class="drawer-panel">
        <!-- Drawer Header -->
        <div style="background: #0f172a; color: #ffffff; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 0.95rem; font-weight: 800; letter-spacing: 0.02em;">SALE DETAILS</span>
                    <span id="drawerInvoiceNo" style="font-family: monospace; font-size: 0.90rem; background: rgba(255,255,255,0.15); padding: 1px 6px; border-radius: 4px;">#0000</span>
                </div>
                <div id="drawerSaleDate" style="font-size: 0.72rem; color: #94a3b8; margin-top: 2px;">—</div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" id="drawerPrintBtn" class="prod-btn prod-btn-primary" style="height: 30px; padding: 0 12px; font-size: 11px;" onclick="printActiveSale()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print Receipt
                </button>
                <button type="button" onclick="closeSaleDrawer()" style="background: transparent; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 2px 6px; line-height: 1;" title="Close (Esc)">✕</button>
            </div>
        </div>

        <!-- Drawer Content Body (Scrollable) -->
        <div style="padding: 16px 20px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 14px;">
            <!-- Customer & Cashier Info Cards -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px;">
                    <div style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Customer</div>
                    <div id="drawerCustomerName" style="font-size: 0.84rem; font-weight: 800; color: #0f172a; margin-top: 2px;">—</div>
                    <div id="drawerCustomerPhone" style="font-size: 0.72rem; color: #64748b; font-family: monospace;">—</div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px;">
                    <div style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Cashier & Branch</div>
                    <div id="drawerCashierName" style="font-size: 0.84rem; font-weight: 800; color: #0f172a; margin-top: 2px;">—</div>
                    <div id="drawerBranchName" style="font-size: 0.72rem; color: #64748b;">—</div>
                </div>
            </div>

            <!-- Items Breakdown Table -->
            <div>
                <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Line Items</div>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 8px 10px; font-size: 0.68rem; font-weight: 800; text-align: left; color: #64748b;">Item</th>
                                <th style="padding: 8px 6px; font-size: 0.68rem; font-weight: 800; text-align: center; color: #64748b; width: 45px;">Qty</th>
                                <th style="padding: 8px 10px; font-size: 0.68rem; font-weight: 800; text-align: right; color: #64748b; width: 85px;">Price (LKR)</th>
                                <th style="padding: 8px 10px; font-size: 0.68rem; font-weight: 800; text-align: right; color: #64748b; width: 90px;">Total (LKR)</th>
                            </tr>
                        </thead>
                        <tbody id="drawerItemsBody">
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 16px; color: #94a3b8;">Loading items...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payments Breakdown -->
            <div>
                <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Payment Breakdown</div>
                <div id="drawerPaymentsContainer" style="display: flex; flex-direction: column; gap: 6px;">
                    <!-- Dynamically populated -->
                </div>
            </div>

            <!-- Financial Totals Summary -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-top: auto;">
                <div style="display: flex; justify-content: space-between; font-size: 0.80rem; color: #64748b; margin-bottom: 4px;">
                    <span>Subtotal:</span>
                    <span id="drawerSubtotal" style="font-family: monospace; font-weight: 700; color: #0f172a;">0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.80rem; color: #64748b; margin-bottom: 4px;">
                    <span>Discounts:</span>
                    <span id="drawerDiscount" style="font-family: monospace; font-weight: 700; color: #dc2626;">0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.95rem; font-weight: 800; color: #0f172a; padding-top: 6px; border-top: 1px dashed #cbd5e1;">
                    <span>Total Amount (LKR):</span>
                    <span id="drawerTotal" style="font-family: monospace; font-size: 1.05rem; color: #0f172a;">0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
let currentActiveSaleId = null;

// Attach CurtissSearchableSelect to filter dropdowns
document.addEventListener('DOMContentLoaded', () => {
    ['filterDatePreset', 'filterStatus'].forEach(id => {
        const el = document.getElementById(id);
        if (el) CurtissSearchableSelect.attach(el);
    });

    // Register Real-time Search Hook on Floating Bottom Nav Bar
    function registerBottomSearch() {
        if (window.CurtissNav && typeof window.CurtissNav.enablePageSearch === 'function') {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search invoice, customer, cashier, amount, payment...',
                initialValue: <?= json_encode($filters['search'] ?? '') ?>,
                onSearch: function(query) {
                    filterSalesTableRealtime(query);
                },
                onEnter: function(query) {
                    const url = new URL(window.location.href);
                    if (query && query.trim()) {
                        url.searchParams.set('search', query.trim());
                    } else {
                        url.searchParams.delete('search');
                    }
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    // Keyboard Esc closes drawer
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSaleDrawer();
        }
    });
});

function filterSalesTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#salesTableBody tr.sales-row');
    let visibleCount = 0;

    rows.forEach(tr => {
        // Exclude action column buttons/text so typing 'print' does not match every row
        const dataCells = Array.from(tr.querySelectorAll('td:not(.col-right:last-child)'));
        const rowText = dataCells.map(td => td.textContent).join(' ').toLowerCase();

        const match = !q || rowText.includes(q);

        if (match) {
            tr.style.display = '';
            visibleCount++;
        } else {
            tr.style.display = 'none';
        }
    });

    const noRow = document.getElementById('noSearchResultsRow');
    if (noRow) {
        noRow.style.display = (visibleCount === 0 && rows.length > 0 && q) ? '' : 'none';
    }
}

function applySalesFilters() {
    const preset = document.getElementById('filterDatePreset').value;
    const status = document.getElementById('filterStatus').value;
    const limit = document.getElementById('salesPerPage').value;

    const url = new URL(window.location.href);
    if (preset) url.searchParams.set('date_preset', preset);
    else url.searchParams.delete('date_preset');

    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');

    url.searchParams.set('limit', limit);
    url.searchParams.set('page', '1');

    window.location.href = url.toString();
}

function clearSalesFilters() {
    const url = new URL(window.location.href);
    url.searchParams.delete('date_preset');
    url.searchParams.delete('status');
    url.searchParams.delete('search');
    url.searchParams.delete('date_from');
    url.searchParams.delete('date_to');
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function changeSalesLimit(limit) {
    const url = new URL(window.location.href);
    url.searchParams.set('limit', limit);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function startExportCSV(e) {
    e.preventDefault();
    const url = new URL('sales_export.php', window.location.href);
    const curr = new URL(window.location.href);
    curr.searchParams.forEach((v, k) => {
        if (k !== 'page' && k !== 'limit') url.searchParams.set(k, v);
    });
    window.location.href = url.toString();
}

function printReceipt(saleId) {
    window.open(`receipt.php?sale_id=${saleId}&reprint=1`, '_blank', 'width=450,height=650,scrollbars=yes');
}

function printActiveSale() {
    if (currentActiveSaleId) {
        printReceipt(currentActiveSaleId);
    }
}

// Open and load Sale Details Drawer
async function viewSaleDetails(saleId) {
    currentActiveSaleId = saleId;
    const drawer = document.getElementById('saleDrawer');
    drawer.classList.add('drawer-open');

    document.getElementById('drawerInvoiceNo').textContent = 'Loading...';
    document.getElementById('drawerItemsBody').innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 16px; color: #94a3b8;">Loading items...</td></tr>';
    document.getElementById('drawerPaymentsContainer').innerHTML = '<div style="color: #94a3b8; font-size: 0.78rem;">Loading payments...</div>';

    try {
        const resp = await fetch(`sales_history.php?action=get_sale_details&id=${saleId}`);
        const res = await resp.json();

        if (!res.success || !res.data) {
            alert('Failed to load sale details.');
            closeSaleDrawer();
            return;
        }

        const { sale, items, payments } = res.data;

        document.getElementById('drawerInvoiceNo').textContent = '#' + sale.invoice_no;
        document.getElementById('drawerSaleDate').textContent = sale.created_at;
        document.getElementById('drawerCustomerName').textContent = sale.customer_name || 'Walk-in Customer';
        document.getElementById('drawerCustomerPhone').textContent = sale.customer_phone || 'No phone recorded';
        document.getElementById('drawerCashierName').textContent = sale.cashier_name || 'System';
        document.getElementById('drawerBranchName').textContent = sale.branch_name || 'Main Branch';

        // Render Items
        let itemsHtml = '';
        if (items && items.length) {
            items.forEach(it => {
                itemsHtml += `
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px 10px;">
                            <div style="font-weight: 700; color: #0f172a;">${escapeHtml(it.product_name)}</div>
                            <div style="font-size: 0.68rem; color: #64748b; font-family: monospace;">SKU: ${escapeHtml(it.sku || '—')}</div>
                        </td>
                        <td style="padding: 8px 6px; text-align: center; font-family: monospace; font-weight: 700;">
                            ${parseFloat(it.quantity)}
                        </td>
                        <td style="padding: 8px 10px; text-align: right; font-family: monospace; color: #334155;">
                            ${parseFloat(it.unit_price).toFixed(2)}
                        </td>
                        <td style="padding: 8px 10px; text-align: right; font-family: monospace; font-weight: 800; color: #0f172a;">
                            ${parseFloat(it.line_total).toFixed(2)}
                        </td>
                    </tr>
                `;
            });
        } else {
            itemsHtml = '<tr><td colspan="4" style="text-align: center; padding: 12px; color: #94a3b8;">No items logged.</td></tr>';
        }
        document.getElementById('drawerItemsBody').innerHTML = itemsHtml;

        // Render Payments
        let pmtsHtml = '';
        if (payments && payments.length) {
            payments.forEach(p => {
                pmtsHtml += `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.78rem;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="font-weight: 700; color: #0f172a;">${escapeHtml(p.payment_method)}</span>
                            ${p.reference_no ? `<span style="font-size: 0.70rem; color: #64748b; font-family: monospace;">Ref: ${escapeHtml(p.reference_no)}</span>` : ''}
                        </div>
                        <span style="font-weight: 800; font-family: monospace; color: #0f172a;">
                            LKR ${parseFloat(p.amount).toFixed(2)}
                        </span>
                    </div>
                `;
            });
        } else {
            pmtsHtml = `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.78rem;">
                    <span style="font-weight: 700; color: #0f172a;">Cash in Hand</span>
                    <span style="font-weight: 800; font-family: monospace; color: #0f172a;">LKR ${parseFloat(sale.total_amount).toFixed(2)}</span>
                </div>
            `;
        }
        document.getElementById('drawerPaymentsContainer').innerHTML = pmtsHtml;

        // Totals
        document.getElementById('drawerSubtotal').textContent = parseFloat(sale.subtotal).toFixed(2);
        document.getElementById('drawerDiscount').textContent = parseFloat(sale.discount_amount).toFixed(2);
        document.getElementById('drawerTotal').textContent = parseFloat(sale.total_amount).toFixed(2);

    } catch (err) {
        console.error('Failed to load sale details:', err);
        alert('Communication error while fetching sale details.');
        closeSaleDrawer();
    }
}

function closeSaleDrawer() {
    const drawer = document.getElementById('saleDrawer');
    if (drawer) drawer.classList.remove('drawer-open');
    currentActiveSaleId = null;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
</script>

<?php
// Helper to preserve URL query params during pagination
function buildSalesPageUrl(int $targetPage): string {
    $params = $_GET;
    $params['page'] = $targetPage;
    return 'sales_history.php?' . http_build_query($params);
}
?>
