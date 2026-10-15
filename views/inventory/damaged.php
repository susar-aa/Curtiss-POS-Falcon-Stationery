<?php
/**
 * Curtiss POS - Damaged & Wastage Stock View (Redesign Theme & Design Rules Enforced)
 * Glassmorphism layout, floating bottom nav search, top-right header searchable select filter & detail drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <form method="GET" action="damaged_stock.php" id="headerDmgFilterForm" style="margin: 0; padding: 0;">
        <input type="hidden" name="branch_id" value="<?= $branchId ?>">
        <select name="status" id="headerDmgStatusSelect" class="inv-select curtiss-select-enhance" style="height: 38px; padding: 0 16px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.96); border: 1px solid rgba(226,232,240,0.9); color: #0f172a; min-width: 190px;" onchange="this.form.submit()">
            <option value="">All Damaged Statuses</option>
            <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active Damaged</option>
            <option value="disposed" <?= ($statusFilter === 'disposed') ? 'selected' : '' ?>>Disposed / Scrapped</option>
        </select>
    </form>

    <?php if (Auth::hasPermission('manage_damaged_stock')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateDmgModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Move Stock to Damaged
    </button>
    <?php endif; ?>

    <a href="inventory.php" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Branch Inventory
    </a>
</div>

<style>
/* ── Page Layout & Typography ── */
.inv-header {
    margin-bottom: 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
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
.inv-btn-danger {
    background: #dc2626;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(220,38,38,0.25);
}
.inv-btn-danger:hover {
    background: #b91c1c;
    transform: translateY(-1px);
}

/* Glassmorphism Container */
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
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}
.inv-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
    transition: background 0.15s ease;
}
.inv-row {
    cursor: pointer;
}
.inv-row:hover td {
    background: #f8fafc;
}
.inv-row:last-child td {
    border-bottom: none;
}

/* Code Pills & Badges */
.inv-code-pill {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.75rem;
    font-weight: 800;
    background: #f1f5f9;
    color: #0f172a;
    padding: 3px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.dmg-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    text-transform: uppercase;
}
.dmg-status-active { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
.dmg-status-disposed { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

.dmg-type-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.dmg-type-damaged { background: #fee2e2; color: #991b1b; }
.dmg-type-wastage { background: #fef3c7; color: #92400e; }

/* Slide-in Drawer */
.inv-drawer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 2000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}
.inv-drawer-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}
.inv-drawer {
    position: fixed;
    top: 0;
    right: -480px;
    width: 440px;
    max-width: 92vw;
    height: 100vh;
    background: #ffffff;
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.15);
    z-index: 2001;
    transition: right 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
}
.inv-drawer-backdrop.active .inv-drawer {
    right: 0;
}
.inv-drawer-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}
.inv-drawer-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.inv-drawer-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.inv-drawer-close {
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
}
.inv-drawer-close:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* Modals */
.inv-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    z-index: 2100;
    display: none;
    align-items: center;
    justify-content: center;
}
.inv-modal-backdrop.active {
    display: flex;
}
.inv-modal-box {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.18);
    overflow: hidden;
    animation: modalSlideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.inv-modal-header {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.inv-modal-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}
.inv-modal-footer {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.inv-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.inv-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.inv-input {
    width: 100%;
    height: 38px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0 12px;
    font-size: 0.85rem;
    color: #0f172a;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.15s ease;
}
.inv-input:focus {
    border-color: #0f172a;
}
</style>

<!-- Header Title & Active Damaged Qty Card -->
<div class="inv-header">
    <div>
        <h1 class="inv-title">Damaged & Wastage Stock</h1>
        <p class="inv-subtitle">Manage damaged items, customer returns, wastage logging, and disposal write-offs for <strong><?= View::escape($activeBranchName) ?></strong>.</p>
    </div>
    <div style="background: rgba(254, 242, 242, 0.95); border: 1px solid #fecdd3; border-radius: 12px; padding: 8px 14px; text-align: right; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.05);">
        <span style="font-size: 0.65rem; font-weight: 800; color: #be123c; text-transform: uppercase; letter-spacing: 0.04em;">ACTIVE DAMAGED STOCK</span>
        <div style="font-size: 1.05rem; font-weight: 800; color: #dc2626; font-family: ui-monospace, monospace; margin-top: 2px;">
            <?= number_format($totalActiveDamagedQty, 2) ?> <span style="font-size: 0.75rem; font-weight: 700;">units</span>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="inv-card">
    <div class="inv-table-wrap">
        <table class="inv-table" id="damagedTable">
            <thead>
                <tr>
                    <th>ID #</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Lot #</th>
                    <th>Type</th>
                    <th style="text-align: right;">Quantity</th>
                    <th>Source / Customer Return</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($damagedList)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                            No damaged or wastage stock entries recorded for this branch yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($damagedList as $d): ?>
                        <?php
                        $st = strtolower($d['status']);
                        $isDisposed = $st === 'disposed';
                        $statusBadgeClass = $isDisposed ? 'dmg-status-disposed' : 'dmg-status-active';
                        $statusLabel = $isDisposed ? 'Disposed' : 'Active';

                        $stockType = strtolower($d['stock_type'] ?? 'damaged');
                        $typePillClass = ($stockType === 'wastage') ? 'dmg-type-wastage' : 'dmg-type-damaged';
                        $typeLabel = strtoupper($stockType);

                        $searchData = strtolower(('dmg-' . $d['id']) . ' ' . ($d['product_name'] ?? '') . ' ' . ($d['barcode'] ?? '') . ' ' . ($d['lot_number'] ?? '') . ' ' . ($d['reason'] ?? '') . ' ' . ($d['return_no'] ? 'return #' . $d['return_no'] : 'internal move') . ' ' . ($d['logger_name'] ?? ''));
                        ?>
                        <tr class="inv-row" data-search="<?= View::escape($searchData) ?>" onclick="openDmgDetailDrawer(<?= (int)$d['id'] ?>)">
                            <td>
                                <span class="inv-code-pill" style="color: #dc2626; background: #ffe4e6; border-color: #fecdd3;">DMG-<?= (int)$d['id'] ?></span>
                            </td>
                            <td style="color: #64748b; font-size: 0.78rem;">
                                <?= View::formatDate(substr($d['created_at'], 0, 10)) ?>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a;"><?= View::escape($d['product_name']) ?></div>
                                <span class="inv-code-pill" style="margin-top: 2px; font-size: 0.68rem;"><?= View::escape($d['barcode']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($d['lot_number'])): ?>
                                    <span class="inv-code-pill"><?= View::escape($d['lot_number']) ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="dmg-type-pill <?= $typePillClass ?>"><?= View::escape($typeLabel) ?></span>
                            </td>
                            <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace; color: #dc2626;">
                                <?= number_format((float)$d['quantity'], 2) ?>
                            </td>
                            <td>
                                <?php if (!empty($d['return_no'])): ?>
                                    <span class="inv-code-pill" style="color: #2563eb; background: #eff6ff; border-color: #bfdbfe;">Return #<?= View::escape($d['return_no']) ?></span>
                                <?php else: ?>
                                    <span class="inv-code-pill" style="color: #475569; background: #f8fafc;">Internal Move</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #475569; font-size: 0.8rem; max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= View::escape($d['reason']) ?>
                            </td>
                            <td>
                                <span class="dmg-status-badge <?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td style="text-align: right;" onclick="event.stopPropagation()">
                                <?php if (!$isDisposed && Auth::hasPermission('manage_damaged_stock')): ?>
                                    <button type="button" class="inv-btn inv-btn-danger" style="padding: 4px 10px; font-size: 11px;" onclick="handleDispose(<?= (int)$d['id'] ?>)">
                                        Dispose / Scrap
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="inv-btn inv-btn-ghost" style="padding: 4px 10px; font-size: 11px;" onclick="openDmgDetailDrawer(<?= (int)$d['id'] ?>)">
                                    Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Slide-in Damaged Stock Detail Drawer -->
<div class="inv-drawer-backdrop" id="dmgDrawerBackdrop" onclick="closeDmgDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerDmgNumber" style="font-size: 0.85rem; color: #dc2626; background: #ffe4e6; border-color: #fecdd3;"></span>
                <span id="drawerDmgStatusBadge" style="margin-left: 6px;"></span>
            </div>
            <button class="inv-drawer-close" onclick="closeDmgDetailDrawer()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="inv-drawer-body">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase;">PRODUCT NAME</div>
                <div id="drawerProductName" style="font-size: 1rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                <div id="drawerBarcode" class="inv-code-pill" style="margin-top: 6px;"></div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">QUANTITY DAMAGED</span>
                    <div id="drawerQty" style="font-size: 1.1rem; font-weight: 800; font-family: ui-monospace, monospace; color: #dc2626; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">STOCK TYPE</span>
                    <div id="drawerStockType" style="margin-top: 4px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">LOT NUMBER</span>
                    <div id="drawerLotNumber" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">SOURCE / RETURN</span>
                    <div id="drawerSource" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">DATE & TIME</span>
                    <div id="drawerDate" style="font-size: 0.8rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">LOGGED BY</span>
                    <div id="drawerUser" style="font-size: 0.8rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">REASON</span>
                <div id="drawerReason" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
            </div>

            <div id="drawerNotesBox" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px; display: none;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">NOTES</span>
                <div id="drawerNotes" style="font-size: 0.85rem; color: #334155; margin-top: 2px; white-space: pre-wrap;"></div>
            </div>
        </div>
        <div class="inv-drawer-footer" id="drawerFooterActions">
            <!-- Dynamic Action Buttons rendered via JS -->
        </div>
    </div>
</div>

<!-- Modal: Post Damaged Stock -->
<div class="inv-modal-backdrop" id="createDmgModal">
    <div class="inv-modal-box">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Move Stock to Damaged / Wastage</h3>
            <button class="inv-drawer-close" onclick="closeCreateDmgModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
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
                        <span style="font-size: 0.72rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 999px; border: 1px solid #bbf7d0;">Active Lot</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; text-align: center;">
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">COST PRICE</div>
                            <div id="dmgSummaryCostPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">SELLING PRICE</div>
                            <div id="dmgSummarySellingPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">CURRENT STOCK</div>
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
                    <input type="text" class="inv-input" name="reason" placeholder="e.g. Broken packaging, Water damage, Expiration" required>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeCreateDmgModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-danger" id="saveDmgBtn">Deduct & Move Stock</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
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
    // Register Real-time Search Hook on Floating Bottom Nav Bar
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search ID #, Product, Lot, Reason, Return #...',
                onSearch: function(query) {
                    filterDamagedTableRealtime(query);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 50);
        }
    }
    registerBottomSearch();

    document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
        if (window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(el);
        }
    });
});

function filterDamagedTableRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#damagedTable tbody tr.inv-row');

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        if (!q || searchAttr.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
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
            alert(result.message);
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
        const typePillClass = (stockType === 'wastage') ? 'dmg-type-wastage' : 'dmg-type-damaged';
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

        const footerActions = document.getElementById('drawerFooterActions');
        let footerHtml = '<button class="inv-btn inv-btn-ghost" onclick="closeDmgDetailDrawer()">Close</button>';

        if (!isDisposed) {
            footerHtml += `
                <button class="inv-btn inv-btn-danger" onclick="handleDispose(${dmg.id})">
                    Dispose / Scrap
                </button>
            `;
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
        alert(result.message);
        location.reload();
    } catch (err) {
        alert('Error: ' + err.message);
    }
}
</script>
