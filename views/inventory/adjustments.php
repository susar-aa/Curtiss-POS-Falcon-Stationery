<?php
/**
 * Curtiss POS - Stock Adjustments View (Redesign Theme & Design Rules Enforced)
 * Glassmorphism layout, floating bottom nav search, top-right header searchable select filter & detail drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <form method="GET" action="stock_adjustments.php" id="headerAdjFilterForm" style="margin: 0; padding: 0;">
        <input type="hidden" name="branch_id" value="<?= $branchId ?>">
        <select name="adjustment_type" id="headerAdjTypeSelect" class="inv-select curtiss-select-enhance" style="height: 38px; padding: 0 16px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.96); border: 1px solid rgba(226,232,240,0.9); color: #0f172a; min-width: 190px;" onchange="this.form.submit()">
            <option value="">All Adjustment Types</option>
            <option value="increase" <?= ($typeFilter === 'increase') ? 'selected' : '' ?>>Increase (+ Stock)</option>
            <option value="decrease" <?= ($typeFilter === 'decrease') ? 'selected' : '' ?>>Decrease (- Stock)</option>
        </select>
    </form>

    <?php if (Auth::hasPermission('manage_stock_adjustments')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateAdjModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Post Stock Adjustment
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

.adj-type-badge {
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
.adj-type-increase { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.adj-type-decrease { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }

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

<!-- Header Title -->
<div class="inv-header">
    <h1 class="inv-title">Stock Adjustments</h1>
    <p class="inv-subtitle">Post authorized inventory increases or decreases with double-entry accounting for <strong><?= View::escape($activeBranchName) ?></strong>.</p>
</div>

<!-- Main Table Card -->
<div class="inv-card">
    <div class="inv-table-wrap">
        <table class="inv-table" id="adjustmentsTable">
            <thead>
                <tr>
                    <th>Adjustment #</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Lot #</th>
                    <th>Type</th>
                    <th style="text-align: right;">Quantity</th>
                    <th style="text-align: right;">Unit Cost</th>
                    <th>Reason</th>
                    <th>Posted By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adjustments)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                            No stock adjustments recorded for this branch yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($adjustments as $adj): ?>
                        <?php
                        $type = strtolower($adj['adjustment_type']);
                        $isIncrease = $type === 'increase';
                        $badgeClass = $isIncrease ? 'adj-type-increase' : 'adj-type-decrease';
                        $badgeLabel = $isIncrease ? '+ Increase' : '- Decrease';
                        $qtyClass = $isIncrease ? 'color: #166534;' : 'color: #dc2626;';
                        $searchData = strtolower(($adj['adjustment_number'] ?? '') . ' ' . ($adj['product_name'] ?? '') . ' ' . ($adj['barcode'] ?? '') . ' ' . ($adj['lot_number'] ?? '') . ' ' . ($adj['reason'] ?? '') . ' ' . ($adj['creator_name'] ?? ''));
                        ?>
                        <tr class="inv-row" data-search="<?= View::escape($searchData) ?>" onclick="openAdjDetailDrawer(<?= (int)$adj['id'] ?>)">
                            <td>
                                <span class="inv-code-pill" style="color: #0284c7; background: #e0f2fe; border-color: #bae6fd;"><?= View::escape($adj['adjustment_number']) ?></span>
                            </td>
                            <td style="color: #64748b; font-size: 0.78rem;">
                                <?= View::formatDate(substr($adj['created_at'], 0, 10)) ?>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a;"><?= View::escape($adj['product_name']) ?></div>
                                <span class="inv-code-pill" style="margin-top: 2px; font-size: 0.68rem;"><?= View::escape($adj['barcode']) ?></span>
                            </td>
                            <td>
                                <span class="inv-code-pill"><?= View::escape($adj['lot_number']) ?></span>
                            </td>
                            <td>
                                <span class="adj-type-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                            </td>
                            <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace; <?= $qtyClass ?>">
                                <?= $isIncrease ? '+' : '-' ?><?= number_format((float)$adj['quantity'], 2) ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-family: ui-monospace, monospace; color: #0f172a;">
                                LKR <?= number_format((float)$adj['cost'], 2) ?>
                            </td>
                            <td style="color: #475569; font-size: 0.8rem; max-width: 190px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= View::escape($adj['reason']) ?>
                            </td>
                            <td style="color: #64748b; font-weight: 600; font-size: 0.78rem;">
                                <?= View::escape($adj['creator_name']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Slide-in Adjustment Detail Drawer -->
<div class="inv-drawer-backdrop" id="adjDrawerBackdrop" onclick="closeAdjDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerAdjNumber" style="font-size: 0.85rem;"></span>
                <span id="drawerAdjTypeBadge" style="margin-left: 6px;"></span>
            </div>
            <button class="inv-drawer-close" onclick="closeAdjDetailDrawer()">
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
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">QUANTITY ADJUSTED</span>
                    <div id="drawerQty" style="font-size: 1.1rem; font-weight: 800; font-family: ui-monospace, monospace; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">LOT NUMBER</span>
                    <div id="drawerLotNumber" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">UNIT COST</span>
                    <div id="drawerUnitCost" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">TOTAL COST VALUE</span>
                    <div id="drawerTotalValue" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">DATE & TIME</span>
                    <div id="drawerDate" style="font-size: 0.8rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">POSTED BY</span>
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
    </div>
</div>

<!-- Modal: Post Stock Adjustment -->
<div class="inv-modal-backdrop" id="createAdjModal">
    <div class="inv-modal-box">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Post Stock Adjustment</h3>
            <button class="inv-drawer-close" onclick="closeCreateAdjModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
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
                        <span style="font-size: 0.72rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 999px; border: 1px solid #bbf7d0;">Active Lot</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; text-align: center;">
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">COST PRICE</div>
                            <div id="adjSummaryCostPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">SELLING PRICE</div>
                            <div id="adjSummarySellingPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">CURRENT STOCK</div>
                            <div id="adjSummaryAvailQty" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #166534; margin-top: 2px;">0.00</div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="inv-field">
                        <label class="inv-label">Adjustment Type *</label>
                        <select class="inv-input" name="adjustment_type" required>
                            <option value="increase">Increase (+ Stock)</option>
                            <option value="decrease">Decrease (- Stock)</option>
                        </select>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">Quantity *</label>
                        <input type="number" step="0.01" min="0.01" class="inv-input" name="quantity" placeholder="0.00" required>
                    </div>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Reason *</label>
                    <input type="text" class="inv-input" name="reason" placeholder="e.g. Stock count variance, Damaged write-off" required>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Notes</label>
                    <textarea class="inv-input" name="notes" rows="2" style="height: auto; padding: 8px 12px;" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeCreateAdjModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="saveAdjBtn">Post Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
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
    // Register Real-time Search Hook on Floating Bottom Nav Bar
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search Adjustment #, Product, Lot, Reason...',
                onSearch: function(query) {
                    filterAdjustmentsTableRealtime(query);
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

function filterAdjustmentsTableRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#adjustmentsTable tbody tr.inv-row');

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        if (!q || searchAttr.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
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

        optionsHtml += `<option value='${JSON.stringify(lot).replace(/'/g, "&#39;")}'>Lot: ${esc(lot.lot_number)} | Cost: LKR ${cost} | Selling: LKR ${price} | Avail: ${qty}</option>`;
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

        document.getElementById('drawerUnitCost').textContent = 'LKR ' + cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('drawerTotalValue').textContent = 'LKR ' + totalVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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
