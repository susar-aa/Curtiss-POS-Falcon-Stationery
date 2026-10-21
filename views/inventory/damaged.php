<?php
/**
 * Curtiss POS - Damaged & Wastage Stock Master View
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
    <?php if (Auth::hasPermission('manage_damaged_stock') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateDmgModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Move Stock to Damaged
    </button>
    <?php endif; ?>

    <button type="button" class="inv-btn inv-btn-ghost" onclick="exportDamagedCsv()">
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
.inv-btn-danger {
    background: #dc2626;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(220,38,38,0.2);
}
.inv-btn-danger:hover {
    background: #b91c1c;
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
.dmg-status-badge {
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
.dmg-status-active { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
.dmg-status-disposed { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

.dmg-type-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.dmg-type-damaged { background: #fee2e2; color: #991b1b; }
.dmg-type-wastage { background: #fef3c7; color: #92400e; }
.dmg-type-expired { background: #f3e8ff; color: #6b21a8; }

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

    <!-- Page Header (Headline Only + Inline 34px Filter Select + Active Damaged Summary Badge - Rules 3 & 5) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Damaged & Wastage Stock</h1>
            <span style="font-size: 11px; font-weight: 800; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; padding: 3px 10px; border-radius: 999px;">
                Active: <?= number_format($totalActiveDamagedQty, 2) ?> units
            </span>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-left: auto;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterDmgStatus" class="curtiss-select-enhance" onchange="applyDmgFilter()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 190px;">
                    <option value="">All Damaged Statuses</option>
                    <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active Damaged Stock</option>
                    <option value="disposed" <?= ($statusFilter === 'disposed') ? 'selected' : '' ?>>Disposed / Scrapped</option>
                </select>
            </div>
            <?php if (!empty($statusFilter) || !empty($search)): ?>
                <a href="damaged_stock.php" class="inv-btn" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-decoration: none; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Damaged Stock Data Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table" id="damagedTable">
                <thead>
                    <tr>
                        <th class="col-left" style="width: 110px;">ID #</th>
                        <th class="col-left" style="width: 130px;">DATE</th>
                        <th class="col-left">PRODUCT</th>
                        <th class="col-left" style="width: 130px;">LOT #</th>
                        <th class="col-center" style="width: 110px;">TYPE</th>
                        <th class="col-right" style="width: 110px;">QTY</th>
                        <th class="col-left" style="width: 150px;">SOURCE</th>
                        <th class="col-left">REASON</th>
                        <th class="col-center" style="width: 120px;">STATUS</th>
                        <th class="col-right" style="width: 170px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($damagedList)): ?>
                        <tr id="noRecordsRow">
                            <td colspan="10" style="text-align: center; color: #94a3b8; padding: 48px 16px;">
                                No damaged or wastage stock entries recorded for <strong><?= View::escape($activeBranchName) ?></strong> matching your filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($damagedList as $d): ?>
                            <?php
                            $st = strtolower($d['status']);
                            $isDisposed = ($st === 'disposed');
                            $statusBadgeClass = $isDisposed ? 'dmg-status-disposed' : 'dmg-status-active';
                            $statusLabel = $isDisposed ? 'Disposed' : 'Active';

                            $stockType = strtolower($d['stock_type'] ?? 'damaged');
                            $typePillClass = match($stockType) {
                                'wastage' => 'dmg-type-wastage',
                                'expired' => 'dmg-type-expired',
                                default   => 'dmg-type-damaged'
                            };
                            $typeLabel = strtoupper($stockType);

                            $qty = (float)$d['quantity'];
                            $cost = (float)($d['cost'] ?? $d['lot_cost'] ?? 0);
                            $totalLoss = round($qty * $cost, 2);
                            $sourceLabel = !empty($d['return_no']) ? ('Return #' . $d['return_no']) : 'Internal Move';

                            $searchBlob = implode(' ', [
                                'dmg-' . $d['id'],
                                substr($d['created_at'], 0, 10),
                                $d['product_name'],
                                $d['barcode'] ?? '',
                                $d['sku'] ?? '',
                                $d['lot_number'] ?? '',
                                $stockType,
                                $typeLabel,
                                number_format($qty, 2),
                                number_format($cost, 2),
                                'LKR ' . number_format($cost, 2),
                                number_format($totalLoss, 2),
                                'LKR ' . number_format($totalLoss, 2),
                                $sourceLabel,
                                $d['reason'] ?? '',
                                $st,
                                $statusLabel,
                                $d['logger_name'] ?? ''
                            ]);
                            ?>
                            <tr class="inv-row" data-search="<?= htmlspecialchars(strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" onclick="openDmgDetailDrawer(<?= (int)$d['id'] ?>)">
                                <td class="col-left">
                                    <span class="inv-code-pill" style="color: #dc2626; background: #ffe4e6; border-color: #fecdd3;">DMG-<?= (int)$d['id'] ?></span>
                                </td>
                                <td class="col-left" style="color: #64748b; font-size: 11.5px; font-family: ui-monospace, monospace;">
                                    <?= View::formatDate(substr($d['created_at'], 0, 10)) ?>
                                </td>
                                <td class="col-left">
                                    <strong style="color: #0f172a;"><?= View::escape($d['product_name']) ?></strong>
                                    <span class="inv-code-pill" style="margin-left: 4px; font-size: 10px;"><?= View::escape($d['barcode']) ?></span>
                                </td>
                                <td class="col-left">
                                    <?php if (!empty($d['lot_number'])): ?>
                                        <span class="inv-code-pill"><?= View::escape($d['lot_number']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-center">
                                    <span class="dmg-type-pill <?= $typePillClass ?>"><?= View::escape($typeLabel) ?></span>
                                </td>
                                <td class="col-right" style="font-weight: 800; font-family: ui-monospace, monospace; color: #dc2626;">
                                    <?= number_format($qty, 2) ?>
                                </td>
                                <td class="col-left">
                                    <?php if (!empty($d['return_no'])): ?>
                                        <span class="inv-code-pill" style="color: #2563eb; background: #eff6ff; border-color: #bfdbfe;">Return #<?= View::escape($d['return_no']) ?></span>
                                    <?php else: ?>
                                        <span class="inv-code-pill" style="color: #475569; background: #f8fafc;">Internal Move</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-left" style="color: #475569; font-size: 12px; max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= View::escape($d['reason']) ?>
                                </td>
                                <td class="col-center">
                                    <span class="dmg-status-badge <?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation()">
                                    <?php if (!$isDisposed && (Auth::hasPermission('manage_damaged_stock') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER))): ?>
                                        <button type="button" class="inv-btn inv-btn-danger" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick="handleDispose(<?= (int)$d['id'] ?>)">
                                            Dispose / Scrap
                                        </button>
                                    <?php endif; ?>

                                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick="openDmgDetailDrawer(<?= (int)$d['id'] ?>)">
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
                    <select id="tableLimitSelect" onchange="changeDmgLimit(this.value)" style="height: 28px; padding: 0 8px; border-radius: 6px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff;">
                        <?php foreach ([25, 50, 75, 100, 500] as $lim): ?>
                            <option value="<?= $lim ?>" <?= ($limit === $lim) ? 'selected' : '' ?>><?= $lim ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 4px;">
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page <= 1) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToDmgPage(' . ($page - 1) . ')"' ?>>← Prev</button>
                    <span style="font-size: 12px; font-weight: 700; padding: 4px 8px; color: #0f172a;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page >= $totalPages) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToDmgPage(' . ($page + 1) . ')"' ?>>Next →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide-in Damaged Stock Detail Drawer -->
<div class="inv-drawer-backdrop" id="dmgDrawerBackdrop" onclick="closeDmgDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerDmgNumber" style="font-size: 0.85rem; color: #dc2626; background: #ffe4e6; border: 1px solid #fecdd3;"></span>
                <span id="drawerDmgStatusBadge" style="margin-left: 6px;"></span>
            </div>
            <button type="button" onclick="closeDmgDetailDrawer()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <div class="inv-drawer-body">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">PRODUCT</div>
                <div id="drawerProductName" style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                <div id="drawerBarcode" class="inv-code-pill" style="margin-top: 6px;"></div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">QUANTITY DAMAGED</span>
                    <div id="drawerQty" style="font-size: 1.15rem; font-weight: 800; font-family: ui-monospace, monospace; color: #dc2626; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">STOCK TYPE</span>
                    <div id="drawerStockType" style="margin-top: 4px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">LOT NUMBER</span>
                    <div id="drawerLotNumber" style="font-size: 0.92rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">SOURCE / RETURN</span>
                    <div id="drawerSource" style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">DATE & TIME</span>
                    <div id="drawerDate" style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">LOGGED BY</span>
                    <div id="drawerUser" style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">REASON</span>
                <div id="drawerReason" style="font-size: 12.5px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
            </div>

            <div id="drawerDisposalInfoBox" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px; display: none;">
                <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">DISPOSAL DETAILS</span>
                <div id="drawerDisposalText" style="font-size: 12px; color: #334155; margin-top: 2px;"></div>
            </div>
        </div>
        <div class="inv-drawer-footer" id="drawerFooterActions">
            <!-- Rendered dynamically via JS -->
        </div>
    </div>
</div>

<!-- Modal: Post Damaged Stock -->
<div class="inv-modal-backdrop" id="createDmgModal">
    <div class="inv-modal-box">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Move Stock to Damaged / Wastage</h3>
            <button type="button" onclick="closeCreateDmgModal()" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:#64748b;">✕</button>
        </div>
        <form id="createDmgForm" onsubmit="handleCreateDmgSubmit(event)">
            <input type="hidden" name="branch_id" value="<?= $branchId ?>">
            <div class="inv-modal-body">
                <div class="inv-field">
                    <label class="inv-label">1. Select Product *</label>
                    <select class="inv-input curtiss-select-enhance" id="dmg_product_id" onchange="onDmgProductChanged()" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($uniqueProducts as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= View::escape($p['name']) ?> (<?= View::escape($p['barcode']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="inv-field">
                    <label class="inv-label">2. Select Source Stock Lot *</label>
                    <select class="inv-input curtiss-select-enhance" id="dmg_lot_id" onchange="onDmgLotChanged()" disabled required>
                        <option value="">-- Select Product First --</option>
                    </select>
                </div>

                <!-- Selected Lot Summary Card (Cost, Selling Price, Avail Qty) -->
                <div id="dmg_lot_summary_box" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 8px;">
                        <span class="inv-code-pill" id="dmgSummaryLotNumber" style="font-size: 0.78rem;"></span>
                        <span style="font-size: 10px; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 999px; border: 1px solid #bbf7d0;">Active Lot</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; text-align: center;">
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">COST PRICE</div>
                            <div id="dmgSummaryCostPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">SELLING PRICE</div>
                            <div id="dmgSummarySellingPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">CURRENT STOCK</div>
                            <div id="dmgSummaryAvailQty" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #166534; margin-top: 2px;">0.00</div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="inv-field">
                        <label class="inv-label">Stock Type *</label>
                        <select class="inv-input" name="stock_type" required>
                            <option value="damaged">Damaged Stock</option>
                            <option value="wastage">Wastage / Expired</option>
                        </select>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">Quantity *</label>
                        <input type="number" step="0.01" min="0.01" class="inv-input" name="quantity" placeholder="0.00" required>
                    </div>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Reason *</label>
                    <input type="text" class="inv-input" name="reason" placeholder="e.g. Broken packaging, Water damage, Expired goods" required>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeCreateDmgModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-danger" id="saveDmgBtn">Deduct & Move Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
const allProductsWithLots = <?= json_encode($productsWithLots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let selectedDmgLotObj = null;

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
                placeholder: 'Search DMG #, product, lot, reason, logger...',
                initialValue: <?= json_encode($search) ?>,
                onSearch: function(query) {
                    filterDamagedTableRealtime(query);
                },
                onEnter: function(query) {
                    const params = new URLSearchParams(window.location.search);
                    if (query && query.trim()) {
                        params.set('search', query.trim());
                    } else {
                        params.delete('search');
                    }
                    params.set('page', '1');
                    window.location.href = 'damaged_stock.php?' + params.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    // Keyboard navigation: Enter key advances focus
    const modalForm = document.getElementById('createDmgForm');
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
                    document.getElementById('saveDmgBtn').click();
                }
            }
        });
    }
});

function applyDmgFilter() {
    const st = document.getElementById('filterDmgStatus').value;
    const params = new URLSearchParams(window.location.search);
    if (st) {
        params.set('status', st);
    } else {
        params.delete('status');
    }
    params.set('page', '1');
    window.location.href = 'damaged_stock.php?' + params.toString();
}

function changeDmgLimit(limitVal) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', limitVal);
    params.set('page', '1');
    window.location.href = 'damaged_stock.php?' + params.toString();
}

function goToDmgPage(targetPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', targetPage);
    window.location.href = 'damaged_stock.php?' + params.toString();
}

function exportDamagedCsv() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'damaged_stock_export.php?' + params.toString();
}

function filterDamagedTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#damagedTable tbody tr.inv-row');
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

function openCreateDmgModal() {
    document.getElementById('createDmgForm').reset();
    selectedDmgLotObj = null;

    const lotSelect = document.getElementById('dmg_lot_id');
    lotSelect.innerHTML = '<option value="">-- Select Product First --</option>';
    lotSelect.disabled = true;
    if (lotSelect._curtissSelect) lotSelect._curtissSelect.refreshOptions();

    const prodSelect = document.getElementById('dmg_product_id');
    if (prodSelect && prodSelect._curtissSelect) prodSelect._curtissSelect.refreshOptions();

    document.getElementById('dmg_lot_summary_box').style.display = 'none';
    document.getElementById('createDmgModal').classList.add('active');
}

function closeCreateDmgModal() {
    document.getElementById('createDmgModal').classList.remove('active');
}

function onDmgProductChanged() {
    const prodId = parseInt(document.getElementById('dmg_product_id').value || 0);
    const lotSelect = document.getElementById('dmg_lot_id');
    const summaryBox = document.getElementById('dmg_lot_summary_box');

    selectedDmgLotObj = null;
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

    let optionsHtml = '<option value="">-- Select Source Lot --</option>';
    availableLots.forEach(lot => {
        const cost = parseFloat(lot.cost || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const price = parseFloat(lot.selling_price || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const qty = parseFloat(lot.remaining_quantity || 0).toFixed(2);

        optionsHtml += `<option value='${JSON.stringify(lot).replace(/'/g, "&#39;")}'>Lot: ${esc(lot.lot_number)} | Cost: LKR ${cost} | Selling: LKR ${price} | Avail: ${qty}</option>`;
    });

    lotSelect.innerHTML = optionsHtml;
    lotSelect.disabled = false;

    if (lotSelect._curtissSelect) {
        lotSelect._curtissSelect.refreshOptions();
    }
}

function onDmgLotChanged() {
    const lotSelect = document.getElementById('dmg_lot_id');
    const val = lotSelect.value;
    const summaryBox = document.getElementById('dmg_lot_summary_box');

    if (!val) {
        selectedDmgLotObj = null;
        summaryBox.style.display = 'none';
        return;
    }

    try {
        selectedDmgLotObj = JSON.parse(val);
        const cost = parseFloat(selectedDmgLotObj.cost || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const price = parseFloat(selectedDmgLotObj.selling_price || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const qty = parseFloat(selectedDmgLotObj.remaining_quantity || 0).toFixed(2);

        document.getElementById('dmgSummaryLotNumber').textContent = 'Lot: ' + (selectedDmgLotObj.lot_number || '');
        document.getElementById('dmgSummaryCostPrice').textContent = 'LKR ' + cost;
        document.getElementById('dmgSummarySellingPrice').textContent = 'LKR ' + price;
        document.getElementById('dmgSummaryAvailQty').textContent = qty + ' units';

        summaryBox.style.display = 'block';
    } catch (e) {
        console.error(e);
        selectedDmgLotObj = null;
        summaryBox.style.display = 'none';
    }
}

async function handleCreateDmgSubmit(e) {
    e.preventDefault();
    if (!selectedDmgLotObj) {
        alert('Please select a valid product & lot.');
        return;
    }

    const btn = document.getElementById('saveDmgBtn');
    btn.disabled = true;

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.product_id = selectedDmgLotObj.product_id;
    data.lot_id = selectedDmgLotObj.lot_id;

    try {
        const res = await fetch('inventory_api.php?action=record_damaged', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            alert(result.message || 'Stock moved to damaged successfully.');
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

async function openDmgDetailDrawer(dmgId) {
    const backdrop = document.getElementById('dmgDrawerBackdrop');
    backdrop.classList.add('active');

    document.getElementById('drawerProductName').textContent = 'Loading...';

    try {
        const res = await fetch(`inventory_api.php?action=get_damaged_details&damaged_id=${dmgId}`);
        const data = await res.json();

        if (!data.success) {
            alert(data.message || 'Failed to load damaged stock details.');
            closeDmgDetailDrawer();
            return;
        }

        const dmg = data.damaged;
        document.getElementById('drawerDmgNumber').textContent = 'DMG-' + dmg.id;
        document.getElementById('drawerProductName').textContent = dmg.product_name || 'N/A';
        document.getElementById('drawerBarcode').textContent = dmg.barcode || 'N/A';
        document.getElementById('drawerLotNumber').textContent = dmg.lot_number || 'N/A';

        const qty = parseFloat(dmg.quantity || 0);
        document.getElementById('drawerQty').textContent = qty.toFixed(2) + ' units';

        const stockType = (dmg.stock_type || 'damaged').toLowerCase();
        const typePillClass = (stockType === 'wastage') ? 'dmg-type-wastage' : (stockType === 'expired' ? 'dmg-type-expired' : 'dmg-type-damaged');
        document.getElementById('drawerStockType').innerHTML = `<span class="dmg-type-pill ${typePillClass}">${esc(stockType.toUpperCase())}</span>`;

        if (dmg.return_no) {
            document.getElementById('drawerSource').innerHTML = `<span class="inv-code-pill" style="color: #2563eb; background: #eff6ff; border-color: #bfdbfe;">Return #${esc(dmg.return_no)}</span>`;
        } else {
            document.getElementById('drawerSource').innerHTML = `<span class="inv-code-pill" style="color: #475569; background: #f8fafc;">Internal Move</span>`;
        }

        document.getElementById('drawerDate').textContent = dmg.created_at || 'N/A';
        document.getElementById('drawerUser').textContent = dmg.logger_name || 'System';
        document.getElementById('drawerReason').textContent = dmg.reason || 'N/A';

        const isDisposed = (dmg.status || 'active').toLowerCase() === 'disposed';
        document.getElementById('drawerDmgStatusBadge').className = 'dmg-status-badge ' + (isDisposed ? 'dmg-status-disposed' : 'dmg-status-active');
        document.getElementById('drawerDmgStatusBadge').textContent = isDisposed ? 'Disposed' : 'Active';

        const disposalBox = document.getElementById('drawerDisposalInfoBox');
        if (isDisposed) {
            disposalBox.style.display = 'block';
            document.getElementById('drawerDisposalText').textContent = `Disposed on ${dmg.disposed_at || 'N/A'} by ${dmg.disposer_name || 'System'}. Notes: ${dmg.disposal_notes || 'None'}`;
        } else {
            disposalBox.style.display = 'none';
        }

        const footerActions = document.getElementById('drawerFooterActions');
        let footerHtml = '<button type="button" class="inv-btn inv-btn-ghost" onclick="closeDmgDetailDrawer()">Close</button>';

        if (!isDisposed && (<?= Auth::hasPermission('manage_damaged_stock') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER) ? 'true' : 'false' ?>)) {
            footerHtml = `
                <button type="button" class="inv-btn inv-btn-danger" onclick="handleDispose(${dmg.id})">
                    Dispose / Scrap
                </button>
            ` + footerHtml;
        }

        footerActions.innerHTML = footerHtml;
    } catch (e) {
        console.error(e);
        alert('Error loading damaged stock details.');
        closeDmgDetailDrawer();
    }
}

function closeDmgDetailDrawer() {
    document.getElementById('dmgDrawerBackdrop').classList.remove('active');
}

async function handleDispose(damagedStockId) {
    if (!confirm('Mark this damaged stock as disposed/scrapped? (Note: Sellable stock was already deducted when logged and will not be reduced again).')) return;
    try {
        const res = await fetch('inventory_api.php?action=dispose_damaged', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ damaged_stock_id: damagedStockId, notes: 'Physical scrap disposal' })
        });
        const result = await res.json();
        alert(result.message || 'Damaged stock marked as disposed.');
        location.reload();
    } catch (err) {
        alert('Error: ' + err.message);
    }
}
</script>
