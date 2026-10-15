<?php
/**
 * Curtiss POS - Branch Stock Transfers Master View (Redesign Theme & Design Rules Enforced)
 * Glassmorphism layout, floating bottom nav search, top-right header searchable select filter & detail drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <form method="GET" action="stock_transfers.php" id="headerTransferFilterForm" style="margin: 0; padding: 0;">
        <input type="hidden" name="branch_id" value="<?= $branchId ?>">
        <select name="status" id="headerTransferStatusSelect" class="inv-select curtiss-select-enhance" style="height: 38px; padding: 0 16px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.96); border: 1px solid rgba(226,232,240,0.9); color: #0f172a; min-width: 190px;" onchange="this.form.submit()">
            <option value="">All Transfer Statuses</option>
            <option value="draft" <?= ($statusFilter === 'draft') ? 'selected' : '' ?>>Draft Transfers</option>
            <option value="sent" <?= ($statusFilter === 'sent') ? 'selected' : '' ?>>Sent / In-Transit</option>
            <option value="received" <?= ($statusFilter === 'received') ? 'selected' : '' ?>>Received / Completed</option>
            <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </form>

    <?php if (Auth::hasPermission('create_stock_transfer')): ?>
    <a href="stock_transfer_create" class="inv-btn inv-btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Stock Transfer
    </a>
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
.inv-btn-success {
    background: #166534;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(22,101,52,0.2);
}
.inv-btn-success:hover {
    background: #14532d;
    transform: translateY(-1px);
}
.inv-btn-warning {
    background: #d97706;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(217,119,6,0.2);
}
.inv-btn-warning:hover {
    background: #b45309;
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

.trf-status-badge {
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
.trf-status-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.trf-status-sent { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.trf-status-received { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.trf-status-cancelled { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }

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
    right: -520px;
    width: 480px;
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
    max-width: 620px;
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
    <h1 class="inv-title">Branch Stock Transfers</h1>
    <p class="inv-subtitle">Transfer inventory between branches with in-transit tracking and exact cost preservation.</p>
</div>

<!-- Main Table Card -->
<div class="inv-card">
    <div class="inv-table-wrap">
        <table class="inv-table" id="transfersTable">
            <thead>
                <tr>
                    <th>Transfer #</th>
                    <th>Date</th>
                    <th>From Branch</th>
                    <th>To Branch</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                            No stock transfers recorded for this branch yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transfers as $trf): ?>
                        <?php
                        $st = strtolower($trf['status']);
                        $badgeClass = match($st) {
                            'draft'     => 'trf-status-draft',
                            'sent'      => 'trf-status-sent',
                            'received'  => 'trf-status-received',
                            'cancelled' => 'trf-status-cancelled',
                            default     => 'trf-status-draft'
                        };
                        $statusLabel = match($st) {
                            'draft'     => 'Draft',
                            'sent'      => 'Sent (In-Transit)',
                            'received'  => 'Received',
                            'cancelled' => 'Cancelled',
                            default     => ucfirst($st)
                        };
                        $isSender = (int)$trf['from_branch_id'] === $branchId;
                        $isReceiver = (int)$trf['to_branch_id'] === $branchId;
                        $searchData = strtolower(($trf['transfer_number'] ?? '') . ' ' . ($trf['from_branch_name'] ?? '') . ' ' . ($trf['to_branch_name'] ?? '') . ' ' . ($trf['creator_name'] ?? '') . ' ' . ($trf['notes'] ?? ''));
                        ?>
                        <tr class="inv-row" data-search="<?= View::escape($searchData) ?>" onclick="openTransferDetailDrawer(<?= (int)$trf['id'] ?>)">
                            <td>
                                <span class="inv-code-pill" style="color: #0284c7; background: #e0f2fe; border-color: #bae6fd;"><?= View::escape($trf['transfer_number']) ?></span>
                            </td>
                            <td style="color: #64748b; font-size: 0.78rem;">
                                <?= View::formatDate($trf['transfer_date']) ?>
                            </td>
                            <td>
                                <strong style="color: #0f172a;"><?= View::escape($trf['from_branch_name']) ?></strong>
                            </td>
                            <td>
                                <strong style="color: #0f172a;"><?= View::escape($trf['to_branch_name']) ?></strong>
                            </td>
                            <td>
                                <span class="trf-status-badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td style="color: #475569; font-weight: 600; font-size: 0.78rem;">
                                <?= View::escape($trf['creator_name']) ?>
                            </td>
                            <td style="text-align: right;" onclick="event.stopPropagation()">
                                <?php if ($st === 'draft' && $isSender && Auth::hasPermission('create_stock_transfer')): ?>
                                    <a href="stock_transfer_create?id=<?= (int)$trf['id'] ?>" class="inv-btn inv-btn-ghost" style="padding: 4px 10px; font-size: 11px;">
                                        Edit
                                    </a>
                                    <button type="button" class="inv-btn inv-btn-warning" style="padding: 4px 10px; font-size: 11px;" onclick="handleSendTransfer(<?= (int)$trf['id'] ?>)">
                                        Send (In-Transit)
                                    </button>
                                <?php endif; ?>

                                <?php 
                                $canReceive = ($isReceiver || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) && Auth::hasPermission('receive_stock_transfer');
                                if ($st === 'sent' && $canReceive): ?>
                                    <button type="button" class="inv-btn inv-btn-success" style="padding: 4px 10px; font-size: 11px;" onclick="handleReceiveTransfer(<?= (int)$trf['id'] ?>)">
                                        Confirm Receive
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="inv-btn inv-btn-ghost" style="padding: 4px 10px; font-size: 11px;" onclick="openTransferDetailDrawer(<?= (int)$trf['id'] ?>)">
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

<!-- Slide-in Transfer Detail Drawer -->
<div class="inv-drawer-backdrop" id="transferDrawerBackdrop" onclick="closeTransferDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerTrfNumber" style="font-size: 0.85rem;"></span>
                <span id="drawerTrfStatusBadge" style="margin-left: 6px;"></span>
            </div>
            <button class="inv-drawer-close" onclick="closeTransferDetailDrawer()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="inv-drawer-body">
            <!-- Branch Flow Card -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: grid; grid-template-columns: 1fr auto 1fr; gap: 12px; align-items: center; text-align: center;">
                <div>
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">FROM BRANCH</span>
                    <div id="drawerFromBranch" style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="color: #94a3b8;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div>
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">TO BRANCH</span>
                    <div id="drawerToBranch" style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <!-- Transfer Meta Details -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">DATE</span>
                    <div id="drawerDate" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">CREATED BY</span>
                    <div id="drawerCreator" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <!-- Transferred Items List -->
            <div>
                <h4 style="margin: 0 0 10px 0; font-size: 0.88rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.03em;">Transferred Items & Lots</h4>
                <div id="drawerItemsContainer" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Rendered via JS -->
                </div>
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

<!-- Modal: Create Stock Transfer -->
<div class="inv-modal-backdrop" id="createTransferModal">
    <div class="inv-modal-box">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Create Branch Stock Transfer</h3>
            <button class="inv-drawer-close" onclick="closeCreateTransferModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="createTransferForm" onsubmit="handleCreateTransferSubmit(event)">
            <input type="hidden" name="from_branch_id" value="<?= $branchId ?>">
            <div class="inv-modal-body">
                <div class="inv-field">
                    <label class="inv-label">Destination Branch *</label>
                    <select class="inv-input curtiss-select-enhance" name="to_branch_id" id="trf_to_branch_id" required>
                        <option value="">-- Select Destination Branch --</option>
                        <?php foreach ($branches as $b): if ((int)$b['id'] !== $branchId): ?>
                        <option value="<?= (int)$b['id'] ?>"><?= View::escape($b['name']) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>

                <div class="inv-field">
                    <label class="inv-label">1. Select Product *</label>
                    <select class="inv-input curtiss-select-enhance" id="trf_product_id" onchange="onTransferProductChanged()" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($uniqueProducts as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= View::escape($p['name']) ?> (<?= View::escape($p['barcode']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="inv-field">
                    <label class="inv-label">2. Select Source Lot *</label>
                    <select class="inv-input curtiss-select-enhance" id="trf_lot_id" onchange="onTransferLotChanged()" disabled required>
                        <option value="">-- Select Product First --</option>
                    </select>
                </div>

                <!-- Selected Lot Summary Card (Cost, Selling Price, Avail Qty) -->
                <div id="trf_lot_summary_box" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 8px;">
                        <span class="inv-code-pill" id="summaryLotNumber" style="font-size: 0.78rem;"></span>
                        <span style="font-size: 0.72rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 999px; border: 1px solid #bbf7d0;">Active Lot</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; text-align: center;">
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">COST PRICE</div>
                            <div id="summaryCostPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">SELLING PRICE</div>
                            <div id="summarySellingPrice" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px;">
                            <div style="font-size: 0.62rem; font-weight: 800; color: #64748b; text-transform: uppercase;">AVAIL STOCK</div>
                            <div id="summaryAvailQty" style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #166534; margin-top: 2px;">0.00</div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="inv-field">
                        <label class="inv-label">Quantity to Transfer *</label>
                        <input type="number" step="0.01" min="0.01" class="inv-input" id="trf_quantity" placeholder="0.00" required>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">Submit Action</label>
                        <select class="inv-input" name="send_now">
                            <option value="1">Send Immediately (In-Transit)</option>
                            <option value="0">Save as Draft</option>
                        </select>
                    </div>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Transfer Notes</label>
                    <textarea class="inv-input" name="notes" rows="2" style="height: auto; padding: 8px 12px;" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeCreateTransferModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="saveTransferBtn">Submit Transfer</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
const allProductsWithLots = <?= json_encode($productsWithLots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let selectedTransferLot = null;

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
                placeholder: 'Search Transfer #, Branch, Status, User...',
                onSearch: function(query) {
                    filterTransfersTableRealtime(query);
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

function filterTransfersTableRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#transfersTable tbody tr.inv-row');

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        if (!q || searchAttr.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}

function openCreateTransferModal() {
    document.getElementById('createTransferForm').reset();
    selectedTransferLot = null;

    const lotSelect = document.getElementById('trf_lot_id');
    lotSelect.innerHTML = '<option value="">-- Select Product First --</option>';
    lotSelect.disabled = true;
    if (lotSelect._curtissSelect) lotSelect._curtissSelect.refreshOptions();

    const prodSelect = document.getElementById('trf_product_id');
    if (prodSelect && prodSelect._curtissSelect) prodSelect._curtissSelect.refreshOptions();

    document.getElementById('trf_lot_summary_box').style.display = 'none';
    document.getElementById('createTransferModal').classList.add('active');
}

function closeCreateTransferModal() {
    document.getElementById('createTransferModal').classList.remove('active');
}

function onTransferProductChanged() {
    const prodId = parseInt(document.getElementById('trf_product_id').value || 0);
    const lotSelect = document.getElementById('trf_lot_id');
    const summaryBox = document.getElementById('trf_lot_summary_box');

    selectedTransferLot = null;
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

function onTransferLotChanged() {
    const lotSelect = document.getElementById('trf_lot_id');
    const val = lotSelect.value;
    const summaryBox = document.getElementById('trf_lot_summary_box');

    if (!val) {
        selectedTransferLot = null;
        summaryBox.style.display = 'none';
        return;
    }

    try {
        selectedTransferLot = JSON.parse(val);
        const cost = parseFloat(selectedTransferLot.cost || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const price = parseFloat(selectedTransferLot.selling_price || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const qty = parseFloat(selectedTransferLot.remaining_quantity || 0).toFixed(2);

        document.getElementById('summaryLotNumber').textContent = 'Lot: ' + (selectedTransferLot.lot_number || '');
        document.getElementById('summaryCostPrice').textContent = 'LKR ' + cost;
        document.getElementById('summarySellingPrice').textContent = 'LKR ' + price;
        document.getElementById('summaryAvailQty').textContent = qty + ' units';

        summaryBox.style.display = 'block';
    } catch (e) {
        console.error(e);
        selectedTransferLot = null;
        summaryBox.style.display = 'none';
    }
}

async function handleCreateTransferSubmit(e) {
    e.preventDefault();
    if (!selectedTransferLot) {
        alert('Please select a valid product & lot.');
        return;
    }
    const qty = parseFloat(document.getElementById('trf_quantity').value);
    if (!qty || qty <= 0) {
        alert('Please enter a valid transfer quantity greater than 0.');
        return;
    }

    const btn = document.getElementById('saveTransferBtn');
    btn.disabled = true;

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.items = [{
        product_id: selectedTransferLot.product_id,
        lot_id: selectedTransferLot.lot_id,
        quantity: qty
    }];

    try {
        const res = await fetch('inventory_api.php?action=create_transfer', {
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

async function openTransferDetailDrawer(transferId) {
    const backdrop = document.getElementById('transferDrawerBackdrop');
    backdrop.classList.add('active');

    const itemsContainer = document.getElementById('drawerItemsContainer');
    itemsContainer.innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 20px;">Loading transfer details...</div>';

    try {
        const res = await fetch(`inventory_api.php?action=get_transfer_details&transfer_id=${transferId}`);
        const data = await res.json();

        if (!data.success) {
            itemsContainer.innerHTML = `<div style="color: #dc2626; text-align: center; padding: 20px;">${esc(data.message)}</div>`;
            return;
        }

        const trf = data.transfer;
        const items = data.items || [];

        document.getElementById('drawerTrfNumber').textContent = trf.transfer_number || '';
        document.getElementById('drawerFromBranch').textContent = trf.from_branch_name || 'N/A';
        document.getElementById('drawerToBranch').textContent = trf.to_branch_name || 'N/A';
        document.getElementById('drawerDate').textContent = trf.transfer_date || 'N/A';
        document.getElementById('drawerCreator').textContent = trf.creator_name || 'System';

        const st = (trf.status || 'draft').toLowerCase();
        const badgeClass = {
            'draft': 'trf-status-draft',
            'sent': 'trf-status-sent',
            'received': 'trf-status-received',
            'cancelled': 'trf-status-cancelled'
        }[st] || 'trf-status-draft';

        const statusLabel = {
            'draft': 'Draft',
            'sent': 'Sent (In-Transit)',
            'received': 'Received',
            'cancelled': 'Cancelled'
        }[st] || st.toUpperCase();

        document.getElementById('drawerTrfStatusBadge').className = 'trf-status-badge ' + badgeClass;
        document.getElementById('drawerTrfStatusBadge').textContent = statusLabel;

        const notesBox = document.getElementById('drawerNotesBox');
        if (trf.notes) {
            notesBox.style.display = 'block';
            document.getElementById('drawerNotes').textContent = trf.notes;
        } else {
            notesBox.style.display = 'none';
        }

        let itemsHtml = '';
        let totalCostVal = 0;

        items.forEach(item => {
            const qty = parseFloat(item.quantity || 0);
            const cost = parseFloat(item.cost || 0);
            const price = parseFloat(item.selling_price || 0);
            const subtotal = qty * cost;
            totalCostVal += subtotal;

            itemsHtml += `
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 6px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-weight: 800; color: #0f172a; font-size: 0.88rem;">${esc(item.product_name)}</div>
                            <span class="inv-code-pill" style="font-size: 0.68rem; margin-top: 2px;">${esc(item.barcode)}</span>
                        </div>
                        <span class="inv-code-pill" style="background: #f8fafc; color: #475569;">Lot: ${esc(item.lot_number || 'N/A')}</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; background: #f8fafc; padding: 8px; border-radius: 8px; margin-top: 4px; font-size: 0.75rem;">
                        <div>
                            <span style="color: #64748b; font-size: 0.65rem; font-weight: 700;">QTY</span>
                            <div style="font-weight: 800; color: #0f172a;">${qty.toFixed(2)}</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 0.65rem; font-weight: 700;">COST</span>
                            <div style="font-weight: 800; font-family: monospace; color: #0f172a;">LKR ${cost.toFixed(2)}</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 0.65rem; font-weight: 700;">SELLING</span>
                            <div style="font-weight: 800; font-family: monospace; color: #0f172a;">LKR ${price.toFixed(2)}</div>
                        </div>
                    </div>
                </div>
            `;
        });

        itemsHtml += `
            <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: #ffffff; padding: 12px 14px; border-radius: 12px; font-size: 0.85rem;">
                <span style="font-weight: 700;">Total Inventory Cost Value:</span>
                <span style="font-weight: 800; font-family: monospace; font-size: 1rem;">LKR ${totalCostVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </div>
        `;

        itemsContainer.innerHTML = itemsHtml;

        // Render Action Buttons
        const footerActions = document.getElementById('drawerFooterActions');
        const currentBranchId = <?= $branchId ?>;
        let actionButtonsHtml = '<button class="inv-btn inv-btn-ghost" onclick="closeTransferDetailDrawer()">Close</button>';

        const canReceiveTransfer = <?= (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN) || Auth::hasPermission('receive_stock_transfer')) ? 'true' : 'false' ?>;

        if (st === 'draft' && parseInt(trf.from_branch_id) === currentBranchId) {
            actionButtonsHtml += `
                <button class="inv-btn inv-btn-warning" onclick="handleSendTransfer(${trf.id})">
                    Send (In-Transit)
                </button>
            `;
        } else if (st === 'sent' && (parseInt(trf.to_branch_id) === currentBranchId || canReceiveTransfer)) {
            actionButtonsHtml += `
                <button class="inv-btn inv-btn-success" onclick="handleReceiveTransfer(${trf.id})">
                    Confirm Receive
                </button>
            `;
        }

        footerActions.innerHTML = actionButtonsHtml;
    } catch (e) {
        console.error(e);
        itemsContainer.innerHTML = '<div style="color: #dc2626; text-align: center; padding: 20px;">Failed to load transfer details.</div>';
    }
}

function closeTransferDetailDrawer() {
    document.getElementById('transferDrawerBackdrop').classList.remove('active');
}

async function handleSendTransfer(transferId) {
    if (!confirm('Send this stock transfer? Inventory will be deducted from source branch and marked In-Transit.')) return;
    try {
        const res = await fetch('inventory_api.php?action=send_transfer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ transfer_id: transferId })
        });
        const result = await res.json();
        alert(result.message);
        location.reload();
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

async function handleReceiveTransfer(transferId) {
    if (!confirm('Confirm receiving this stock transfer into destination branch inventory?')) return;
    try {
        const res = await fetch('inventory_api.php?action=receive_transfer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ transfer_id: transferId })
        });
        const result = await res.json();
        alert(result.message);
        location.reload();
    } catch (err) {
        alert('Error: ' + err.message);
    }
}
</script>
