<?php
/**
 * Curtiss POS - Stock Adjustments Master View
 * Adheres strictly to Curtiss Master Rules:
 * Fixed Viewport, Zebra Striping (48px rows), Sticky Headers,
 * Inline 34px Filter Select, Floating Bottom Nav Realtime Search,
 * Server-Side Pagination (25, 50, 75, 100, 500) & Slide-in Detail Drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header (Action Controls Only - Rule 19) -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (Auth::hasPermission('manage_stock_adjustments') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateAdjModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Post Stock Adjustment
    </button>
    <?php endif; ?>

    <button type="button" class="inv-btn inv-btn-ghost" onclick="exportAdjustmentsCsv()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </button>

    <a href="inventory.php" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Branch Inventory
    </a>
</div>

<style>
/* ── Typography & Standard Buttons ── */
.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin: 0;
}
.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 16px;
    height: 34px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
    font-family: inherit;
    box-sizing: border-box;
}
.inv-btn-ghost {
    background: rgba(255,255,255,0.94);
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

/* ── Standard Fixed Viewport & Zebra Striped Table ── */
.prod-table-panel {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
}
.prod-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    table-layout: auto;
}
.prod-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    white-space: nowrap;
}
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.12s ease;
    cursor: pointer;
}
.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:hover            { background: #f1f5f9 !important; }
.prod-table td {
    height: 48px;
    padding: 0 14px;
    color: #1e293b;
    vertical-align: middle;
    border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    box-sizing: border-box;
}

/* Explicit Matching Alignment Rules */
.prod-table th.col-left, .prod-table td.col-left   { text-align: left; }
.prod-table th.col-right, .prod-table td.col-right { text-align: right; }
.prod-table th.col-center, .prod-table td.col-center { text-align: center; }

/* In-Cell Badges & Pills */
.inv-code-pill {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    background: #f1f5f9;
    color: #334155;
    padding: 2px 6px;
    border-radius: 6px;
    font-weight: 600;
    white-space: nowrap;
    border: 1px solid #e2e8f0;
}
.adj-type-badge {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    text-transform: uppercase;
    box-sizing: border-box;
    vertical-align: middle;
}
.adj-type-increase { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.adj-type-decrease { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

/* ── Slide-in Detail Drawer ── */
.inv-drawer-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 9990; opacity: 0; visibility: hidden; transition: all 0.25s ease;
}
.inv-drawer-backdrop.active { opacity: 1; visibility: visible; }
.inv-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 480px; max-width: 95vw;
    background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px);
    border-left: 1px solid rgba(226, 232, 240, 0.9); box-shadow: -10px 0 40px rgba(0,0,0,0.15);
    z-index: 9991; transform: translateX(100%); transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex; flex-direction: column;
}
.inv-drawer-backdrop.active .inv-drawer { transform: translateX(0); }
.inv-drawer-header {
    padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #ffffff;
}
.inv-drawer-body { padding: 18px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 14px; }
.inv-drawer-footer {
    padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: flex-end; gap: 8px;
}

/* Modals */
.inv-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px); z-index: 9995; display: none;
    align-items: center; justify-content: center;
}
.inv-modal-backdrop.active { display: flex; }
.inv-modal-box {
    background: #ffffff; border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh;
    box-shadow: 0 20px 40px rgba(0,0,0,0.18); overflow: hidden; display: flex; flex-direction: column;
    animation: modalSlideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.inv-modal-header {
    padding: 16px 20px; background: #ffffff; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;
}
.inv-modal-body {
    padding: 18px 20px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 12px;
}
.inv-modal-footer {
    padding: 14px 20px; background: #ffffff; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;
}
.inv-field { display: flex; flex-direction: column; gap: 4px; }
.inv-label { font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; }
.inv-input, .inv-select {
    width: 100%; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; font-size: 12.5px;
    font-weight: 600; color: #0f172a; outline: none; box-sizing: border-box; transition: all 0.15s ease;
}
.inv-input:focus, .inv-select:focus { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08); }
</style>

<!-- Fixed Screen Viewport Container -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only + Inline 34px Filter Select - Rules 3 & 5) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Stock Adjustments</h1>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-left: auto;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Type:</span>
                <select id="filterAdjType" class="curtiss-select-enhance" onchange="applyAdjFilter()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 190px;">
                    <option value="">All Adjustment Types</option>
                    <option value="increase" <?= ($typeFilter === 'increase') ? 'selected' : '' ?>>Increase (+ Stock)</option>
                    <option value="decrease" <?= ($typeFilter === 'decrease') ? 'selected' : '' ?>>Decrease (- Stock)</option>
                </select>
            </div>
            <?php if (!empty($typeFilter) || !empty($search)): ?>
                <a href="stock_adjustments.php" class="inv-btn" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-decoration: none; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Stock Adjustments Data Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table" id="adjustmentsTable">
                <thead>
                    <tr>
                        <th class="col-left" style="width: 140px;">ADJUSTMENT #</th>
                        <th class="col-left" style="width: 130px;">DATE</th>
                        <th class="col-left">PRODUCT</th>
                        <th class="col-left" style="width: 130px;">LOT #</th>
                        <th class="col-center" style="width: 140px;">TYPE</th>
                        <th class="col-right" style="width: 110px;">QTY</th>
                        <th class="col-right" style="width: 130px;">COST (LKR)</th>
                        <th class="col-right" style="width: 140px;">TOTAL (LKR)</th>
                        <th class="col-left">REASON</th>
                        <th class="col-left" style="width: 130px;">POSTED BY</th>
                        <th class="col-right" style="width: 100px;">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($adjustments)): ?>
                        <tr id="noRecordsRow">
                            <td colspan="11" style="text-align: center; color: #94a3b8; padding: 48px 16px;">
                                No stock adjustments recorded for <strong><?= View::escape($activeBranchName) ?></strong> matching your filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($adjustments as $adj): ?>
                            <?php
                            $type = strtolower($adj['adjustment_type']);
                            $isIncrease = ($type === 'increase');
                            $badgeClass = $isIncrease ? 'adj-type-increase' : 'adj-type-decrease';
                            $badgeLabel = $isIncrease ? '+ Increase' : '- Decrease';
                            $qtyClass = $isIncrease ? 'color: #166534;' : 'color: #dc2626;';
                            $qty = (float)$adj['quantity'];
                            $cost = (float)$adj['cost'];
                            $totalVal = round($qty * $cost, 2);

                            $searchBlob = implode(' ', [
                                $adj['adjustment_number'],
                                substr($adj['created_at'], 0, 10),
                                $adj['product_name'],
                                $adj['barcode'] ?? '',
                                $adj['sku'] ?? '',
                                $adj['lot_number'],
                                $type,
                                $badgeLabel,
                                ($isIncrease ? '+' : '-') . number_format($qty, 2),
                                number_format($qty, 2),
                                number_format($cost, 2),
                                'LKR ' . number_format($cost, 2),
                                'Rs. ' . number_format($cost, 2),
                                number_format($totalVal, 2),
                                'LKR ' . number_format($totalVal, 2),
                                'Rs. ' . number_format($totalVal, 2),
                                $adj['reason'],
                                $adj['notes'] ?? '',
                                $adj['creator_name']
                            ]);
                            ?>
                            <tr class="inv-row" data-search="<?= htmlspecialchars(strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" onclick="openAdjDetailDrawer(<?= (int)$adj['id'] ?>)">
                                <td class="col-left">
                                    <span class="inv-code-pill" style="color: #0284c7; background: #e0f2fe; border: 1px solid #bae6fd;"><?= View::escape($adj['adjustment_number']) ?></span>
                                </td>
                                <td class="col-left" style="color: #64748b; font-size: 11.5px; font-family: ui-monospace, monospace;">
                                    <?= View::formatDate(substr($adj['created_at'], 0, 10)) ?>
                                </td>
                                <td class="col-left">
                                    <strong style="color: #0f172a;"><?= View::escape($adj['product_name']) ?></strong>
                                    <span class="inv-code-pill" style="margin-left: 4px; font-size: 10px;"><?= View::escape($adj['barcode']) ?></span>
                                </td>
                                <td class="col-left">
                                    <span class="inv-code-pill"><?= View::escape($adj['lot_number']) ?></span>
                                </td>
                                <td class="col-center">
                                    <span class="adj-type-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                                </td>
                                <td class="col-right" style="font-weight: 800; font-family: ui-monospace, monospace; <?= $qtyClass ?>">
                                    <?= $isIncrease ? '+' : '-' ?><?= number_format($qty, 2) ?>
                                </td>
                                <td class="col-right" style="font-weight: 700; font-family: ui-monospace, monospace; color: #0f172a;">
                                    <?= number_format($cost, 2) ?>
                                </td>
                                <td class="col-right" style="font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a;">
                                    <?= number_format($totalVal, 2) ?>
                                </td>
                                <td class="col-left" style="color: #475569; font-size: 12px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= View::escape($adj['reason']) ?>
                                </td>
                                <td class="col-left" style="color: #64748b; font-weight: 600; font-size: 12px;">
                                    <?= View::escape($adj['creator_name']) ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation()">
                                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick="openAdjDetailDrawer(<?= (int)$adj['id'] ?>)">
                                        Details →
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Standard Table Pagination Footer (Limits: 25, 50, 75, 100, 500) -->
        <div class="prod-table-footer" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; background: #ffffff; border-top: 1px solid #e2e8f0;">
            <div style="font-size: 12px; color: #64748b; font-weight: 600;" id="tableEntriesInfo">
                Showing <?= $totalItems > 0 ? (($page - 1) * $limit + 1) : 0 ?> to <?= min($page * $limit, $totalItems) ?> of <?= $totalItems ?> entries
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b;">Per Page:</span>
                    <select id="tableLimitSelect" onchange="changeAdjLimit(this.value)" style="height: 28px; padding: 0 8px; border-radius: 6px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff;">
                        <?php foreach ([25, 50, 75, 100, 500] as $lim): ?>
                            <option value="<?= $lim ?>" <?= ($limit === $lim) ? 'selected' : '' ?>><?= $lim ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 4px;">
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page <= 1) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToAdjPage(' . ($page - 1) . ')"' ?>>← Prev</button>
                    <span style="font-size: 12px; font-weight: 700; padding: 4px 8px; color: #0f172a;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page >= $totalPages) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToAdjPage(' . ($page + 1) . ')"' ?>>Next →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide-in Adjustment Detail Drawer -->
<div class="inv-drawer-backdrop" id="adjDrawerBackdrop" onclick="closeAdjDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerAdjNumber" style="font-size: 0.85rem; color: #0284c7; background: #e0f2fe; border: 1px solid #bae6fd;"></span>
                <span id="drawerAdjTypeBadge" style="margin-left: 6px;"></span>
            </div>
            <button type="button" onclick="closeAdjDetailDrawer()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <div class="inv-drawer-body">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">PRODUCT</div>
                <div id="drawerProductName" style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                <div id="drawerBarcode" class="inv-code-pill" style="margin-top: 6px;"></div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">QUANTITY ADJUSTED</span>
                    <div id="drawerQty" style="font-size: 1.15rem; font-weight: 800; font-family: ui-monospace, monospace; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">LOT NUMBER</span>
                    <div id="drawerLotNumber" style="font-size: 0.92rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">UNIT COST (LKR)</span>
                    <div id="drawerUnitCost" style="font-size: 0.95rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">TOTAL VALUE (LKR)</span>
                    <div id="drawerTotalValue" style="font-size: 0.95rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">DATE & TIME</span>
                    <div id="drawerDate" style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">POSTED BY</span>
                    <div id="drawerUser" style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">REASON</span>
                <div id="drawerReason" style="font-size: 12.5px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
            </div>

            <div id="drawerNotesBox" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px; display: none;">
                <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">NOTES</span>
                <div id="drawerNotes" style="font-size: 12px; color: #334155; margin-top: 2px; white-space: pre-wrap;"></div>
            </div>
        </div>
        <div class="inv-drawer-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeAdjDetailDrawer()">Close</button>
        </div>
    </div>
</div>

<!-- Modal: Post Stock Adjustment -->
<div class="inv-modal-backdrop" id="createAdjModal">
    <div class="inv-modal-box">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Post Stock Adjustment</h3>
            <button type="button" onclick="closeCreateAdjModal()" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:#64748b;">✕</button>
        </div>
        <form id="createAdjForm" onsubmit="handleCreateAdjSubmit(event)">
            <input type="hidden" name="branch_id" value="<?= $branchId ?>">
            <div class="inv-modal-body">
                <div class="inv-field">
                    <label class="inv-label">1. Select Product *</label>
                    <select class="inv-input curtiss-select-enhance" id="adj_product_id" onchange="onAdjProductChanged()" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($uniqueProducts as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= View::escape($p['name']) ?> (<?= View::escape($p['barcode']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="inv-field">
                    <label class="inv-label">2. Select Target Stock Lot *</label>
                    <select class="inv-input curtiss-select-enhance" id="adj_lot_id" onchange="onAdjLotChanged()" disabled required>
                        <option value="">-- Select Product First --</option>
                    </select>
                </div>

                <!-- Selected Lot Summary Card (Cost, Selling Price, Avail Qty) -->
                <div id="adj_lot_summary_box" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 8px;">
                        <span class="inv-code-pill" id="adjSummaryLotNumber" style="font-size: 0.78rem;"></span>
                        <span style="font-size: 10px; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 999px; border: 1px solid #bbf7d0;">Active Lot</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; text-align: center;">
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">COST PRICE</div>
                            <div id="adjSummaryCostPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">SELLING PRICE</div>
                            <div id="adjSummarySellingPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">CURRENT STOCK</div>
                            <div id="adjSummaryAvailQty" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #166534; margin-top: 2px;">0.00</div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="inv-field">
                        <label class="inv-label">Adjustment Type *</label>
                        <select class="inv-input" name="adjustment_type" id="adj_input_type" required>
                            <option value="increase">Increase (+ Stock)</option>
                            <option value="decrease">Decrease (- Stock)</option>
                        </select>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">Quantity *</label>
                        <input type="number" step="0.01" min="0.01" class="inv-input" name="quantity" id="adj_input_qty" placeholder="0.00" required>
                    </div>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Reason *</label>
                    <input type="text" class="inv-input" name="reason" id="adj_input_reason" placeholder="e.g. Physical audit count variance, Damaged write-off" required>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Notes</label>
                    <textarea class="inv-input" name="notes" id="adj_input_notes" rows="2" style="height: auto; padding: 8px 12px;" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeCreateAdjModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="saveAdjBtn">Post Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script>
const allProductsWithLots = <?= json_encode($productsWithLots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let selectedAdjLotObj = null;

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    // Enhance filter selects with CurtissSearchableSelect
    document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
        if (window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(el);
        }
    });

    // Register Real-time Search Hook on Floating Bottom Nav Bar
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search Adjustment #, product, lot, reason, poster...',
                initialValue: <?= json_encode($search) ?>,
                onSearch: function(query) {
                    filterAdjustmentsTableRealtime(query);
                },
                onEnter: function(query) {
                    const params = new URLSearchParams(window.location.search);
                    if (query && query.trim()) {
                        params.set('search', query.trim());
                    } else {
                        params.delete('search');
                    }
                    params.set('page', '1');
                    window.location.href = 'stock_adjustments.php?' + params.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    // Keyboard navigation: Enter key advances focus
    const modalForm = document.getElementById('createAdjForm');
    if (modalForm) {
        modalForm.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const inputs = Array.from(modalForm.querySelectorAll('input:not([type="hidden"]), select, textarea'));
                const index = inputs.indexOf(e.target);
                if (index >= 0 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                    if (inputs[index + 1].select) inputs[index + 1].select();
                } else if (index === inputs.length - 1) {
                    document.getElementById('saveAdjBtn').click();
                }
            }
        });
    }
});

function applyAdjFilter() {
    const atype = document.getElementById('filterAdjType').value;
    const params = new URLSearchParams(window.location.search);
    if (atype) {
        params.set('adjustment_type', atype);
    } else {
        params.delete('adjustment_type');
    }
    params.set('page', '1');
    window.location.href = 'stock_adjustments.php?' + params.toString();
}

function changeAdjLimit(limitVal) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', limitVal);
    params.set('page', '1');
    window.location.href = 'stock_adjustments.php?' + params.toString();
}

function goToAdjPage(targetPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', targetPage);
    window.location.href = 'stock_adjustments.php?' + params.toString();
}

function exportAdjustmentsCsv() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'stock_adjustments_export.php?' + params.toString();
}

function filterAdjustmentsTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#adjustmentsTable tbody tr.inv-row');
    let visibleCount = 0;

    rows.forEach(tr => {
        const searchAttr = (tr.getAttribute('data-search') || '').toLowerCase();
        // Exclude action column buttons/links
        const cells = Array.from(tr.querySelectorAll('td:not(.col-right:last-child)'));
        const rowText = cells.map(td => td.textContent.toLowerCase()).join(' ');

        const match = !q || searchAttr.includes(q) || rowText.includes(q);

        if (match) {
            tr.style.display = '';
            visibleCount++;
        } else {
            tr.style.display = 'none';
        }
    });

    const noRow = document.getElementById('noRecordsRow');
    if (noRow) {
        noRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }

    const info = document.getElementById('tableEntriesInfo');
    if (info) {
        if (q) {
            info.textContent = `Showing ${visibleCount} filtered results for "${query}"`;
        } else {
            info.textContent = `Showing <?= $totalItems > 0 ? (($page - 1) * $limit + 1) : 0 ?> to <?= min($page * $limit, $totalItems) ?> of <?= $totalItems ?> entries`;
        }
    }
}

function openCreateAdjModal() {
    document.getElementById('createAdjForm').reset();
    selectedAdjLotObj = null;

    const lotSelect = document.getElementById('adj_lot_id');
    lotSelect.innerHTML = '<option value="">-- Select Product First --</option>';
    lotSelect.disabled = true;
    if (lotSelect._curtissSelect) lotSelect._curtissSelect.refreshOptions();

    const prodSelect = document.getElementById('adj_product_id');
    if (prodSelect && prodSelect._curtissSelect) prodSelect._curtissSelect.refreshOptions();

    document.getElementById('adj_lot_summary_box').style.display = 'none';
    document.getElementById('createAdjModal').classList.add('active');
}

function closeCreateAdjModal() {
    document.getElementById('createAdjModal').classList.remove('active');
}

function onAdjProductChanged() {
    const prodId = parseInt(document.getElementById('adj_product_id').value || 0);
    const lotSelect = document.getElementById('adj_lot_id');
    const summaryBox = document.getElementById('adj_lot_summary_box');

    selectedAdjLotObj = null;
    summaryBox.style.display = 'none';

    lotSelect.innerHTML = '';

    if (!prodId) {
        lotSelect.innerHTML = '<option value="">-- Select Product First --</option>';
        lotSelect.disabled = true;
        if (lotSelect._curtissSelect) lotSelect._curtissSelect.refreshOptions();
        return;
    }

    const availableLots = allProductsWithLots.filter(item => parseInt(item.product_id) === prodId);

    if (availableLots.length === 0) {
        lotSelect.innerHTML = '<option value="">-- No Active Lots Available --</option>';
        lotSelect.disabled = true;
        if (lotSelect._curtissSelect) lotSelect._curtissSelect.refreshOptions();
        return;
    }

    let optionsHtml = '<option value="">-- Select Target Stock Lot --</option>';
    availableLots.forEach(lot => {
        const cost = parseFloat(lot.cost || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const price = parseFloat(lot.selling_price || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const qty = parseFloat(lot.remaining_quantity || 0).toFixed(2);

        optionsHtml += `<option value='${JSON.stringify(lot).replace(/'/g, "&#39;")}'>Lot: ${esc(lot.lot_number)} | Cost: LKR ${cost} | Price: LKR ${price} | Avail: ${qty}</option>`;
    });

    lotSelect.innerHTML = optionsHtml;
    lotSelect.disabled = false;

    if (lotSelect._curtissSelect) {
        lotSelect._curtissSelect.refreshOptions();
    }
}

function onAdjLotChanged() {
    const lotSelect = document.getElementById('adj_lot_id');
    const val = lotSelect.value;
    const summaryBox = document.getElementById('adj_lot_summary_box');

    if (!val) {
        selectedAdjLotObj = null;
        summaryBox.style.display = 'none';
        return;
    }

    try {
        selectedAdjLotObj = JSON.parse(val);
        const cost = parseFloat(selectedAdjLotObj.cost || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const price = parseFloat(selectedAdjLotObj.selling_price || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const qty = parseFloat(selectedAdjLotObj.remaining_quantity || 0).toFixed(2);

        document.getElementById('adjSummaryLotNumber').textContent = 'Lot: ' + (selectedAdjLotObj.lot_number || '');
        document.getElementById('adjSummaryCostPrice').textContent = 'LKR ' + cost;
        document.getElementById('adjSummarySellingPrice').textContent = 'LKR ' + price;
        document.getElementById('adjSummaryAvailQty').textContent = qty + ' units';

        summaryBox.style.display = 'block';
    } catch (e) {
        console.error(e);
        selectedAdjLotObj = null;
        summaryBox.style.display = 'none';
    }
}

async function handleCreateAdjSubmit(e) {
    e.preventDefault();
    if (!selectedAdjLotObj) {
        alert('Please select a valid product & target lot.');
        return;
    }

    const btn = document.getElementById('saveAdjBtn');
    btn.disabled = true;

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.product_id = selectedAdjLotObj.product_id;
    data.lot_id = selectedAdjLotObj.lot_id;

    try {
        const res = await fetch('inventory_api.php?action=create_adjustment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            alert(result.message || 'Stock adjustment posted successfully.');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    } finally {
        btn.disabled = false;
    }
}

async function openAdjDetailDrawer(adjId) {
    const backdrop = document.getElementById('adjDrawerBackdrop');
    backdrop.classList.add('active');

    document.getElementById('drawerProductName').textContent = 'Loading...';

    try {
        const res = await fetch(`inventory_api.php?action=get_adjustment_details&adjustment_id=${adjId}`);
        const data = await res.json();

        if (!data.success) {
            alert(data.message || 'Failed to load adjustment details.');
            closeAdjDetailDrawer();
            return;
        }

        const adj = data.adjustment;
        document.getElementById('drawerAdjNumber').textContent = adj.adjustment_number || '';
        document.getElementById('drawerProductName').textContent = adj.product_name || 'N/A';
        document.getElementById('drawerBarcode').textContent = adj.barcode || 'N/A';
        document.getElementById('drawerLotNumber').textContent = adj.lot_number || 'N/A';

        const type = (adj.adjustment_type || 'increase').toLowerCase();
        const isIncrease = type === 'increase';
        const qty = parseFloat(adj.quantity || 0);
        const cost = parseFloat(adj.cost || 0);
        const totalVal = qty * cost;

        const qtyEl = document.getElementById('drawerQty');
        qtyEl.textContent = (isIncrease ? '+' : '-') + qty.toFixed(2);
        qtyEl.style.color = isIncrease ? '#166534' : '#dc2626';

        document.getElementById('drawerUnitCost').textContent = cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('drawerTotalValue').textContent = totalVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('drawerDate').textContent = adj.created_at || 'N/A';
        document.getElementById('drawerUser').textContent = adj.creator_name || 'System';
        document.getElementById('drawerReason').textContent = adj.reason || 'N/A';

        const notesBox = document.getElementById('drawerNotesBox');
        if (adj.notes) {
            notesBox.style.display = 'block';
            document.getElementById('drawerNotes').textContent = adj.notes;
        } else {
            notesBox.style.display = 'none';
        }

        const badgeClass = isIncrease ? 'adj-type-increase' : 'adj-type-decrease';
        const badgeLabel = isIncrease ? '+ Increase' : '- Decrease';
        document.getElementById('drawerAdjTypeBadge').className = 'adj-type-badge ' + badgeClass;
        document.getElementById('drawerAdjTypeBadge').textContent = badgeLabel;
    } catch (e) {
        console.error(e);
        alert('Error loading adjustment details.');
        closeAdjDetailDrawer();
    }
}

function closeAdjDetailDrawer() {
    document.getElementById('adjDrawerBackdrop').classList.remove('active');
}
</script>
