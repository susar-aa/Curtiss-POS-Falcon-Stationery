<?php
/**
 * Curtiss POS - Goods Received Notes List View (Inventory Theme Alignment & Top-Right Floating Header)
 * Fixed Viewport Layout with Internal Table Scroll, Zebra Striping & Slide-in Details Drawer
 */

$hasActiveFilters = !empty($filters['supplier_id']) || !empty($filters['status']) || !empty($filters['search']);
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="prod-btn prod-btn-ghost" onclick="startExportCSV(event, 'grn_export')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Export CSV
    </button>
    <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
    <a href="grn_create" class="prod-btn prod-btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        + Create GRN
    </a>
    <?php endif; ?>
</div>

<style>
/* ── Shared Theme & Layout Styles ── */
.prod-header {
    margin-bottom: 0.75rem;
}
.prod-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
}

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
    position: relative;
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

/* Table Panel & Viewport */
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
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
}
.prod-table td {
    height: 48px;
    padding: 0 14px;
    font-size: 12.5px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
    text-align: left;
    box-sizing: border-box;
}
.prod-table .col-right {
    text-align: right !important;
}
.prod-table .col-center {
    text-align: center !important;
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

/* Badges */
.prod-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    box-sizing: border-box;
    white-space: nowrap;
}
.prod-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.prod-badge-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}
.prod-badge-warning .dot { background: #f59e0b; }

.prod-badge-active {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.prod-badge-active .dot { background: #22c55e; }

.prod-badge-info {
    background: #f0f9ff;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.prod-badge-info .dot { background: #0284c7; }

.prod-badge-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.prod-badge-danger .dot { background: #ef4444; }

/* Action Buttons */
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
    font-size: 11.5px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    font-family: inherit;
    box-sizing: border-box;
    white-space: nowrap;
}
.prod-act-success {
    background: #f0fdf4;
    color: #166534;
    border-color: #bbf7d0;
}
.prod-act-success:hover {
    background: #dcfce7;
    border-color: #86efac;
}
.prod-act-danger {
    background: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
}
.prod-act-danger:hover {
    background: #fee2e2;
    border-color: #fca5a5;
}
.prod-act-primary {
    background: #0f172a;
    color: #ffffff;
}
.prod-act-primary:hover {
    background: #1e293b;
}

/* Table Footer Pagination */
.prod-table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
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
    height: 30px;
    padding: 0 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.78rem;
    background: #fff;
    color: #0f172a;
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
    min-width: 30px;
    height: 30px;
    padding: 0 8px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    font-size: 0.78rem;
    font-weight: 600;
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
    color: #fff;
    border-color: #0f172a;
}
.prod-pg-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Modals */
.curtiss-modal-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(6px);
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
}
.curtiss-modal-backdrop.active {
    opacity: 1;
    visibility: visible;
}
.curtiss-modal {
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 460px;
    padding: 24px;
    transform: scale(0.95);
    transition: transform 0.2s ease;
}
.curtiss-modal-backdrop.active .curtiss-modal {
    transform: scale(1);
}

.prog-bar-bg {
    width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin: 16px 0 8px;
}
.prog-bar-fill {
    height: 100%; width: 0%; background: linear-gradient(90deg, #0f172a, #38bdf8); transition: width 0.2s ease;
}
</style>

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Header Title (Headline Only) & Inline Top-Right Filter Controls (Height: 34px) -->
    <div class="prod-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="prod-title" style="margin: 0;">Goods Received Notes (GRN)</h1>
        </div>

        <!-- Inline Filter Selects (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Supplier:</span>
                <select id="inlineFilterSupplier" onchange="applyInlineGrnFilters()" class="curtiss-select-enhance" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff;">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ((int)$filters['supplier_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                            <?= View::escape($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="inlineFilterStatus" onchange="applyInlineGrnFilters()" class="curtiss-select-enhance" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff;">
                    <option value="">All Statuses</option>
                    <option value="Draft" <?= ($filters['status'] === 'Draft') ? 'selected' : '' ?>>Draft</option>
                    <option value="Approved" <?= ($filters['status'] === 'Approved') ? 'selected' : '' ?>>Approved (Posted)</option>
                    <option value="Cancelled" <?= ($filters['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <?php if ($hasActiveFilters): ?>
                <a href="grns" class="prod-btn prod-btn-ghost" style="height: 34px; min-height: 34px; padding: 0 12px; font-size: 11px; border-radius: 999px;" title="Reset Filters">✕ Clear</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET['created'])): ?>
        <div style="flex-shrink: 0; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; color: #166534; font-size: 0.83rem; font-weight: 600; margin-bottom: 0.75rem;">
            ✓ Goods Received Note created in Draft status. Stock lots will post to inventory upon Approval.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div style="flex-shrink: 0; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; color: #166534; font-size: 0.83rem; font-weight: 600; margin-bottom: 0.75rem;">
            ✓ Goods Received Note updated successfully.
        </div>
    <?php endif; ?>

    <?php if (!empty($successMsg)): ?>
        <div style="flex-shrink: 0; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; color: #166534; font-size: 0.83rem; font-weight: 600; margin-bottom: 0.75rem;">
            ✓ <?= View::escape($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div style="flex-shrink: 0; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; color: #991b1b; font-size: 0.83rem; font-weight: 600; margin-bottom: 0.75rem;">
            ✕ <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Table Panel -->
    <div class="prod-table-panel">
        <div class="prod-table-scroll">
            <table class="prod-table" id="grnTable">
                <thead>
                    <tr>
                        <th>GRN Number</th>
                        <th>Linked PO</th>
                        <th>Supplier</th>
                        <th>Invoice No</th>
                        <th>Received Date</th>
                        <th class="col-right">Actual Total (LKR)</th>
                        <th class="col-right">Stated Total (LKR)</th>
                        <th class="col-center">Variance</th>
                        <th class="col-center">Status</th>
                        <th class="col-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="grnTableBody">
                    <?php if (empty($grns)): ?>
                        <tr id="noDataRow">
                            <td colspan="10" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                                No Goods Received Notes found matching the selected criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grns as $g): ?>
                            <?php 
                            $diff = (float)$g['difference_amount']; 
                            $hasDiff = abs($diff) > 0.001;
                            $rawActual = (float)$g['actual_grn_total'];
                            $fmtActual = number_format($rawActual, 2);
                            $unfmtActual = str_replace(',', '', $fmtActual);
                            $rawInvoice = (float)$g['supplier_invoice_amount'];
                            $fmtInvoice = number_format($rawInvoice, 2);
                            $unfmtInvoice = str_replace(',', '', $fmtInvoice);
                            $searchTokens = [
                                $g['grn_number'],
                                $g['po_number'] ?? '',
                                $g['supplier_name'],
                                $g['supplier_invoice_no'],
                                $g['received_date'],
                                $g['status'],
                                $g['difference_reason'] ?? '',
                                $g['creator_name'] ?? '',
                                $rawActual,
                                $fmtActual,
                                $unfmtActual,
                                'lkr ' . $fmtActual,
                                'rs. ' . $fmtActual,
                                'rs ' . $fmtActual,
                                'lkr ' . $unfmtActual,
                                'rs. ' . $unfmtActual,
                                $rawInvoice,
                                $fmtInvoice,
                                $unfmtInvoice,
                                $diff,
                                number_format($diff, 2)
                            ];
                            ?>
                            <tr class="grn-row" onclick="viewGrnDetails(<?= $g['id'] ?>)" style="cursor: pointer;" data-search="<?= strtolower(View::escape(implode(' ', $searchTokens))) ?>">
                                <td><code style="font-size: 0.82rem; font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 3px 6px; border-radius: 6px;"><?= View::escape($g['grn_number']) ?></code></td>
                                <td><?= !empty($g['po_number']) ? '<code style="font-size: 0.78rem; color: #0284c7; background: #f0f9ff; padding: 2px 5px; border-radius: 4px;">' . View::escape($g['po_number']) . '</code>' : '<span style="color: #94a3b8; font-size: 0.75rem;">Direct GRN</span>' ?></td>
                                <td><strong style="color: #0f172a; font-size: 0.85rem;"><?= View::escape($g['supplier_name']) ?></strong></td>
                                <td><code style="font-size: 0.8rem; color: #475569;"><?= View::escape($g['supplier_invoice_no']) ?></code></td>
                                <td style="color: #64748b;"><?= View::formatDate($g['received_date'], 'Y-m-d') ?></td>
                                <td class="col-right" style="font-weight: 700; color: #0f172a; font-family: monospace;"><?= number_format((float)$g['actual_grn_total'], 2) ?></td>
                                <td class="col-right" style="color: #64748b; font-family: monospace;"><?= number_format((float)$g['supplier_invoice_amount'], 2) ?></td>
                                <td class="col-center">
                                    <?php if ($hasDiff): ?>
                                        <span class="prod-badge prod-badge-warning" title="Variance Reason: <?= View::escape($g['difference_reason']) ?>">
                                            <span class="dot"></span><?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="prod-badge prod-badge-active"><span class="dot"></span>Matched</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-center">
                                    <?php if ($g['status'] === 'Approved'): ?>
                                        <span class="prod-badge prod-badge-active"><span class="dot"></span>Approved</span>
                                    <?php elseif ($g['status'] === 'Cancelled'): ?>
                                        <span class="prod-badge prod-badge-danger"><span class="dot"></span>Cancelled</span>
                                    <?php else: ?>
                                        <span class="prod-badge prod-badge-warning"><span class="dot"></span>Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation()">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <a href="grn_print?id=<?= $g['id'] ?>" target="_blank" class="prod-act-btn" style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1;" title="Print / PDF Document" onclick="event.stopPropagation()">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                            Print
                                        </a>

                                        <?php if ($g['status'] === 'Draft' && Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
                                            <a href="grn_edit?id=<?= $g['id'] ?>" class="prod-act-btn prod-act-primary" title="Edit Draft GRN" onclick="event.stopPropagation()">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit
                                            </a>
                                            <form method="POST" action="grns" style="display: inline;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                                <button type="submit" class="prod-act-btn prod-act-success" onclick="return confirm('Approve & Post GRN <?= View::escape($g['grn_number']) ?> to Inventory Lots?')">Post Stock</button>
                                            </form>
                                            <form method="POST" action="grns" style="display: inline;">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                                <button type="submit" class="prod-act-btn prod-act-danger" onclick="return confirm('Cancel GRN <?= View::escape($g['grn_number']) ?>?')">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer with Pagination -->
        <div class="prod-table-footer">
            <div class="prod-footer-info" id="grnPaginationInfo">
                Showing <?= count($grns) > 0 ? (($page - 1) * $limit + 1) : 0 ?> to <?= min($page * $limit, $totalItems) ?> of <?= $totalItems ?> entries
            </div>

            <div class="prod-footer-controls">
                <div class="prod-per-page">
                    <span>Rows per page:</span>
                    <select id="grnRowsPerPage" onchange="changeGrnRowsPerPage(this.value)">
                        <option value="25" <?= ((int)$limit === 25) ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= ((int)$limit === 50) ? 'selected' : '' ?>>50</option>
                        <option value="75" <?= ((int)$limit === 75) ? 'selected' : '' ?>>75</option>
                        <option value="100" <?= ((int)$limit === 100) ? 'selected' : '' ?>>100</option>
                        <option value="500" <?= ((int)$limit === 500) ? 'selected' : '' ?>>500</option>
                    </select>
                </div>

                <div class="prod-pagination">
                    <?php if ($page > 1): ?>
                        <a href="grns?<?= http_build_query(array_merge($filters, ['page' => $page - 1, 'limit' => $limit])) ?>" class="prod-pg-btn">← Prev</a>
                    <?php else: ?>
                        <span class="prod-pg-btn disabled">← Prev</span>
                    <?php endif; ?>

                    <?php 
                    $startPg = max(1, $page - 2);
                    $endPg = min($totalPages, $page + 2);
                    for ($p = $startPg; $p <= $endPg; $p++): 
                    ?>
                        <a href="grns?<?= http_build_query(array_merge($filters, ['page' => $p, 'limit' => $limit])) ?>" class="prod-pg-btn <?= ($p === (int)$page) ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="grns?<?= http_build_query(array_merge($filters, ['page' => $page + 1, 'limit' => $limit])) ?>" class="prod-pg-btn">Next →</a>
                    <?php else: ?>
                        <span class="prod-pg-btn disabled">Next →</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GRN Details Slide-In Drawer -->
<div id="grnDetailsBackdrop" onclick="closeGrnDetailsDrawer()" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.45); z-index: 1090; backdrop-filter: blur(4px);"></div>
<div id="grnDetailsDrawer" style="position: fixed; top: 0; right: 0; bottom: 0; width: 100%; max-width: 650px; background: #ffffff; z-index: 1100; box-shadow: -10px 0 30px rgba(0,0,0,0.15); transform: translateX(100%); transition: transform 0.25s cubic-bezier(0.16,1,0.3,1); display: flex; flex-direction: column;">
    <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #ffffff;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h3 id="grnDrawerTitle" style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Goods Received Note Details</h3>
        </div>
        <button type="button" onclick="closeGrnDetailsDrawer()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b; padding: 4px;">✕</button>
    </div>
    <div id="grnDrawerContent" style="padding: 20px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 16px;">
        <!-- Injected via AJAX -->
    </div>
</div>

<!-- Export Progress Modal -->
<div id="expModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <div style="width: 38px; height: 38px; border-radius: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; display: flex; align-items: center; justify-content: center; color: #166534;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0;">Exporting GRN Records</h3>
                <p style="font-size: 0.78rem; color: #64748b; margin: 2px 0 0 0;">Generating CSV file, please wait...</p>
            </div>
        </div>
        <div class="prog-bar-bg">
            <div id="expBarFill" class="prog-bar-fill"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 700; color: #64748b;">
            <span id="expStatusText">Initializing...</span>
            <span id="expPercentText">0%</span>
        </div>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
// Attach CurtissSearchableSelect to inline filter dropdowns
document.addEventListener('DOMContentLoaded', () => {
    const suppSelect = document.getElementById('inlineFilterSupplier');
    if (suppSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(suppSelect);
    }
});

function applyInlineGrnFilters() {
    const supp = document.getElementById('inlineFilterSupplier').value;
    const status = document.getElementById('inlineFilterStatus').value;
    const url = new URL(window.location.href);

    if (supp) url.searchParams.set('supplier_id', supp);
    else url.searchParams.delete('supplier_id');

    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');

    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Register Realtime Search Hook with Bottom Nav
const _currentGrnSearch = <?= json_encode($filters['search'] ?? '') ?>;

function registerBottomSearch() {
    if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search GRNs by number, PO #, supplier, invoice #, total, date...',
            onSearch: function(query) {
                filterTableRealtime(query);
            }
        });

        const searchInput = document.getElementById('dock-search-input');
        if (searchInput && _currentGrnSearch) {
            searchInput.value = _currentGrnSearch;
        }

        // Enter key initiates server-side search across entire dataset
        if (searchInput && !searchInput.dataset.enterBound) {
            searchInput.dataset.enterBound = 'true';
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    executeGrnServerSearch(searchInput.value.trim());
                }
            });
        }
    } else {
        setTimeout(registerBottomSearch, 40);
    }
}
registerBottomSearch();

function executeGrnServerSearch(query) {
    const params = new URLSearchParams(window.location.search);
    if (query) {
        params.set('search', query);
    } else {
        params.delete('search');
    }
    params.set('page', '1');
    window.location.href = 'grns?' + params.toString();
}

// Real-time table filter across all displayed columns with currency tolerance
function filterTableRealtime(query) {
    const tbody = document.getElementById('grnTableBody');
    const existingEmpty = document.getElementById('clientSearchEmptyRow');
    if (existingEmpty) existingEmpty.remove();

    if (!query) {
        document.querySelectorAll('#grnTableBody tr.grn-row').forEach(tr => tr.style.display = '');
        const info = document.getElementById('grnPaginationInfo');
        if (info) {
            info.innerHTML = `Showing <?= count($grns) > 0 ? (($page - 1) * $limit + 1) : 0 ?> to <?= min($page * $limit, $totalItems) ?> of <?= $totalItems ?> entries`;
        }
        return;
    }

    const q = query.toLowerCase().trim();
    const qClean = q.replace(/^(rs\.?|lkr)\s*/i, '').replace(/,/g, '').trim();

    const rows = document.querySelectorAll('#grnTableBody tr.grn-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.getAttribute('data-search') || '';
        if (text.includes(q) || (qClean && text.includes(qClean))) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const info = document.getElementById('grnPaginationInfo');
    if (info) {
        info.innerHTML = `Filtered <strong>${visibleCount}</strong> matching GRNs on this page`;
    }

    if (visibleCount === 0 && tbody) {
        const noMatchTr = document.createElement('tr');
        noMatchTr.id = 'clientSearchEmptyRow';
        noMatchTr.innerHTML = `
            <td colspan="10" style="text-align: center; padding: 36px 16px; color: #64748b;">
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">No Goods Received Notes matching "${query.replace(/"/g, '&quot;')}" on this page</div>
                <div style="font-size: 12px; margin-bottom: 12px;">Press <kbd style="background:#f1f5f9; padding:2px 6px; border-radius:4px; border:1px solid #cbd5e1; font-weight:700;">Enter</kbd> to search across all pages, or click below.</div>
                <button type="button" onclick="executeGrnServerSearch('${encodeURIComponent(query)}')" class="prod-act-btn prod-act-primary" style="background:#0f172a; color:#fff; border-color:#0f172a; height: 32px; padding: 0 16px;">Search All Pages</button>
            </td>
        `;
        tbody.appendChild(noMatchTr);
    }
}

function changeGrnRowsPerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('limit', val);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// GRN Details Slide-in Drawer Logic
function viewGrnDetails(grnId) {
    const backdrop = document.getElementById('grnDetailsBackdrop');
    const drawer = document.getElementById('grnDetailsDrawer');
    const content = document.getElementById('grnDrawerContent');
    const title = document.getElementById('grnDrawerTitle');

    backdrop.style.display = 'block';
    drawer.style.transform = 'translateX(0)';
    content.innerHTML = '<div style="text-align: center; padding: 40px 10px; color: #64748b; font-weight: 600;">Loading GRN details...</div>';

    fetch(`grns?action=get_grn&id=${grnId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.grn) {
                content.innerHTML = `<div style="padding: 20px; color: #dc2626; font-weight: 600;">Failed to load GRN details: ${data.message || 'Unknown error'}</div>`;
                return;
            }

            const grn = data.grn;
            const items = data.items || [];
            title.innerText = `GRN: ${grn.grn_number}`;

            let statusBadge = '<span class="prod-badge prod-badge-warning"><span class="dot"></span>Draft</span>';
            if (grn.status === 'Approved') statusBadge = '<span class="prod-badge prod-badge-active"><span class="dot"></span>Approved (Stock Posted)</span>';
            else if (grn.status === 'Cancelled') statusBadge = '<span class="prod-badge prod-badge-danger"><span class="dot"></span>Cancelled</span>';

            const diff = parseFloat(grn.difference_amount || 0);
            const hasDiff = Math.abs(diff) > 0.001;

            let itemsRows = '';
            items.forEach((it, idx) => {
                const rowTotal = parseFloat(it.line_total || (it.quantity * it.unit_cost)).toFixed(2);
                itemsRows += `
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px 10px; color: #64748b; font-size: 0.8rem;">${idx + 1}</td>
                        <td style="padding: 8px 10px; font-weight: 600; color: #0f172a; font-size: 0.82rem;">
                            ${it.product_name || 'Item #' + it.product_id}
                            <div style="font-size: 0.72rem; color: #64748b; font-family: monospace;">SKU: ${it.sku || '—'}</div>
                        </td>
                        <td style="padding: 8px 10px; text-align: center; font-weight: 700; color: #0f172a; font-size: 0.82rem;">${parseFloat(it.quantity).toFixed(0)}</td>
                        <td style="padding: 8px 10px; text-align: right; color: #0f172a; font-family: monospace; font-size: 0.82rem;">${parseFloat(it.unit_cost).toFixed(2)}</td>
                        <td style="padding: 8px 10px; text-align: right; color: #059669; font-family: monospace; font-size: 0.82rem;">${parseFloat(it.selling_price || 0).toFixed(2)}</td>
                        <td style="padding: 8px 10px; text-align: right; font-weight: 700; color: #0f172a; font-family: monospace; font-size: 0.82rem;">${rowTotal}</td>
                    </tr>
                `;
            });

            content.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">${grn.grn_number}</span>
                            ${statusBadge}
                        </div>
                        <div style="font-size: 0.8rem; color: #64748b;">Received on ${grn.received_date || '—'} by <strong>${grn.creator_name || 'System'}</strong></div>
                        ${grn.po_number ? `<div style="font-size: 0.75rem; color: #0284c7; font-weight: 700; margin-top: 2px;">PO Reference: ${grn.po_number}</div>` : ''}
                    </div>
                    <div style="text-align: right;">
                        <span class="prod-badge prod-badge-info">${grn.branch_name || 'Branch'}</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.8rem;">
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Supplier Details</div>
                        <div style="font-size: 0.9rem; font-weight: 800; color: #0f172a; margin-top: 2px;">${grn.supplier_name || '—'}</div>
                        <div style="color: #475569; margin-top: 2px;">${grn.supplier_phone || ''}</div>
                        <div style="color: #64748b; font-size: 0.75rem;">${grn.supplier_address || ''}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Invoice Details</div>
                        <div style="color: #334155; margin-top: 2px;">Invoice No: <strong>${grn.supplier_invoice_no || '—'}</strong></div>
                        <div style="color: #334155; margin-top: 2px;">Invoice Date: <strong>${grn.supplier_invoice_date || '—'}</strong></div>
                        <div style="color: #334155; margin-top: 2px;">Received Date: <strong>${grn.received_date || '—'}</strong></div>
                        ${grn.payment_due_date ? `<div style="color: #b45309; margin-top: 2px; font-weight: 700;">Payment Due: <strong>${grn.payment_due_date}</strong></div>` : ''}
                    </div>
                </div>

                ${grn.notes ? `
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 14px; font-size: 0.78rem; color: #92400e;">
                        <strong>Remarks:</strong> ${grn.notes}
                    </div>
                ` : ''}

                <div>
                    <div style="font-size: 0.8rem; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 8px;">Received Items (${items.length})</div>
                    <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 0.72rem; color: #475569; text-transform: uppercase;">
                                    <th style="padding: 8px 10px; text-align: left; width: 28px;">#</th>
                                    <th style="padding: 8px 10px; text-align: left;">Product</th>
                                    <th style="padding: 8px 10px; text-align: center;">Qty</th>
                                    <th style="padding: 8px 10px; text-align: right;">Cost (LKR)</th>
                                    <th style="padding: 8px 10px; text-align: right;">Sell (LKR)</th>
                                    <th style="padding: 8px 10px; text-align: right;">Total (LKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsRows || '<tr><td colspan="6" style="text-align: center; padding: 16px; color: #94a3b8;">No items in this GRN</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-top: auto;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #64748b; margin-bottom: 4px;">
                        <span>Actual Received Total:</span>
                        <span style="font-family: monospace; font-weight: 700; color: #0f172a;">LKR ${parseFloat(grn.actual_grn_total).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #64748b; margin-bottom: 6px;">
                        <span>Supplier Stated Invoice:</span>
                        <span style="font-family: monospace; font-weight: 700; color: #475569;">LKR ${parseFloat(grn.supplier_invoice_amount).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem; font-weight: 800; color: #0f172a; padding-top: 6px; border-top: 1px dashed #cbd5e1;">
                        <span>Variance:</span>
                        <span style="font-family: monospace; color: ${hasDiff ? '#dc2626' : '#15803d'};">
                            ${(diff > 0 ? '+' : '') + diff.toFixed(2)} ${hasDiff ? '(' + (grn.difference_reason || 'Discrepancy') + ')' : '(Balanced)'}
                        </span>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center; padding-top: 10px; flex-wrap: wrap;">
                    <a href="grn_print?id=${grn.id}" target="_blank" class="prod-act-btn" style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 8px 16px; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Print / PDF GRN
                    </a>

                    ${grn.status === 'Draft' ? `
                        <a href="grn_edit?id=${grn.id}" class="prod-act-btn prod-act-primary" style="padding: 8px 16px; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Edit GRN
                        </a>
                        <form method="POST" action="grns" style="display: inline;" onsubmit="return confirm('Approve & Post GRN ${grn.grn_number} to Inventory Lots?')">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="id" value="${grn.id}">
                            <button type="submit" class="prod-act-btn prod-act-success" style="padding: 8px 16px; font-size: 0.8rem;">✓ Approve & Post Stock</button>
                        </form>
                        <form method="POST" action="grns" style="display: inline;" onsubmit="return confirm('Cancel GRN ${grn.grn_number}?')">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="id" value="${grn.id}">
                            <button type="submit" class="prod-act-btn prod-act-danger" style="padding: 8px 16px; font-size: 0.8rem;">✕ Cancel GRN</button>
                        </form>
                    ` : ''}
                </div>
            `;
        })
        .catch(err => {
            content.innerHTML = `<div style="padding: 20px; color: #dc2626; font-weight: 600;">Error communicating with server: ${err.message}</div>`;
        });
}

function closeGrnDetailsDrawer() {
    const backdrop = document.getElementById('grnDetailsBackdrop');
    const drawer = document.getElementById('grnDetailsDrawer');
    drawer.style.transform = 'translateX(100%)';
    setTimeout(() => {
        backdrop.style.display = 'none';
    }, 250);
}

function startExportCSV(e, endpoint) {
    e.preventDefault();
    const modal = document.getElementById('expModal');
    const fill = document.getElementById('expBarFill');
    const status = document.getElementById('expStatusText');
    const percent = document.getElementById('expPercentText');

    modal.classList.add('active');
    fill.style.width = '15%';
    status.innerText = 'Preparing GRN export data...';
    percent.innerText = '15%';

    setTimeout(() => {
        fill.style.width = '65%';
        status.innerText = 'Compiling CSV records...';
        percent.innerText = '65%';
    }, 250);

    const xhr = new XMLHttpRequest();
    xhr.open('GET', endpoint, true);
    xhr.responseType = 'blob';

    xhr.onload = function() {
        if (this.status === 200) {
            fill.style.width = '100%';
            status.innerText = 'Download ready!';
            percent.innerText = '100%';

            const blob = this.response;
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = downloadUrl;
            
            const disposition = xhr.getResponseHeader('Content-Disposition');
            let filename = 'grns_export.csv';
            if (disposition && disposition.indexOf('filename=') !== -1) {
                const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                if (matches != null && matches[1]) {
                    filename = matches[1].replace(/['"]/g, '');
                }
            }
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);

            setTimeout(() => {
                modal.classList.remove('active');
            }, 600);
        } else {
            alert('Failed to export GRN records.');
            modal.classList.remove('active');
        }
    };

    xhr.onerror = function() {
        alert('Network error occurred while exporting.');
        modal.classList.remove('active');
    };

    xhr.send();
}
</script>
