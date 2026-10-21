<?php
/**
 * Curtiss POS - Branch Stock Transfers Master View
 * Adheres strictly to Curtiss Master Rules:
 * Fixed Viewport, Zebra Striping (48px rows), Sticky Headers,
 * Inline 34px Filter Selects, Floating Bottom Nav Realtime Search,
 * Server-Side Pagination (25, 50, 75, 100, 500) & Slide-in Detail Drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header (Action Controls Only - Rule 19) -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (Auth::hasPermission('create_stock_transfer') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
    <a href="stock_transfer_create" class="inv-btn inv-btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Stock Transfer
    </a>
    <?php endif; ?>

    <button type="button" class="inv-btn inv-btn-ghost" onclick="exportTransfersCsv()">
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

/* Explicit Alignment Rules */
.prod-table th.col-left, .prod-table td.col-left   { text-align: left; }
.prod-table th.col-right, .prod-table td.col-right { text-align: right; }
.prod-table th.col-center, .prod-table td.col-center { text-align: center; }

/* In-Cell Badges & Pills */
.inv-code-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    background: #f1f5f9;
    color: #334155;
    padding: 2px 6px;
    border-radius: 6px;
    font-weight: 600;
    white-space: nowrap;
}
.trf-status-badge {
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
.trf-status-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.trf-status-sent { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.trf-status-received { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.trf-status-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* ── Slide-in Detail Drawer ── */
.inv-drawer-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 9990; opacity: 0; visibility: hidden; transition: all 0.25s ease;
}
.inv-drawer-backdrop.active { opacity: 1; visibility: visible; }
.inv-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 520px; max-width: 95vw;
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
</style>

<!-- Fixed Screen Viewport Container -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only + Inline 34px Filter Select - Rule 3 & 5) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Branch Stock Transfers</h1>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-left: auto;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterTransferStatus" class="curtiss-select-enhance" onchange="applyTransferFilter()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 190px;">
                    <option value="">All Transfer Statuses</option>
                    <option value="draft" <?= ($statusFilter === 'draft') ? 'selected' : '' ?>>Draft Transfers</option>
                    <option value="sent" <?= ($statusFilter === 'sent') ? 'selected' : '' ?>>Sent / In-Transit</option>
                    <option value="received" <?= ($statusFilter === 'received') ? 'selected' : '' ?>>Received / Completed</option>
                    <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <?php if (!empty($statusFilter) || !empty($search)): ?>
                <a href="stock_transfers.php" class="inv-btn" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-decoration: none; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Stock Transfers Data Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table" id="transfersTable">
                <thead>
                    <tr>
                        <th class="col-left" style="width: 140px;">TRANSFER #</th>
                        <th class="col-left" style="width: 130px;">DATE</th>
                        <th class="col-left">FROM BRANCH</th>
                        <th class="col-left">TO BRANCH</th>
                        <th class="col-center" style="width: 150px;">STATUS</th>
                        <th class="col-left" style="width: 140px;">CREATED BY</th>
                        <th class="col-right" style="width: 200px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transfers)): ?>
                        <tr id="noRecordsRow">
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 48px 16px;">
                                No stock transfers found matching your filters for <strong><?= View::escape($activeBranchName) ?></strong>.
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
                            $searchBlob = implode(' ', [
                                $trf['transfer_number'],
                                $trf['transfer_date'],
                                $trf['from_branch_name'],
                                $trf['to_branch_name'],
                                $st,
                                $statusLabel,
                                $trf['creator_name'],
                                $trf['notes'] ?? ''
                            ]);
                            ?>
                            <tr class="inv-row" data-search="<?= htmlspecialchars(strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" onclick="openTransferDetailDrawer(<?= (int)$trf['id'] ?>)">
                                <td class="col-left">
                                    <span class="inv-code-pill" style="color: #0284c7; background: #e0f2fe; border: 1px solid #bae6fd;"><?= View::escape($trf['transfer_number']) ?></span>
                                </td>
                                <td class="col-left" style="color: #64748b; font-size: 11.5px; font-family: ui-monospace, monospace;">
                                    <?= View::formatDate($trf['transfer_date']) ?>
                                </td>
                                <td class="col-left">
                                    <strong style="color: #0f172a;"><?= View::escape($trf['from_branch_name']) ?></strong>
                                </td>
                                <td class="col-left">
                                    <strong style="color: #0f172a;"><?= View::escape($trf['to_branch_name']) ?></strong>
                                </td>
                                <td class="col-center">
                                    <span class="trf-status-badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td class="col-left" style="color: #475569; font-weight: 600; font-size: 12px;">
                                    <?= View::escape($trf['creator_name']) ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation()">
                                    <?php if ($st === 'draft' && $isSender && (Auth::hasPermission('create_stock_transfer') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER))): ?>
                                        <a href="stock_transfer_create?id=<?= (int)$trf['id'] ?>" class="inv-btn inv-btn-ghost" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;">
                                            Edit
                                        </a>
                                        <button type="button" class="inv-btn inv-btn-warning" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick="handleSendTransfer(<?= (int)$trf['id'] ?>)">
                                            Send
                                        </button>
                                        <button type="button" class="inv-btn" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;" onclick="handleCancelTransfer(<?= (int)$trf['id'] ?>)">
                                            Cancel
                                        </button>
                                    <?php endif; ?>

                                    <?php 
                                    $canReceive = $isReceiver && (Auth::hasPermission('receive_stock_transfer') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER));
                                    if ($st === 'sent' && $canReceive): ?>
                                        <button type="button" class="inv-btn inv-btn-success" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick="handleReceiveTransfer(<?= (int)$trf['id'] ?>)">
                                            Confirm Receive
                                        </button>
                                    <?php endif; ?>

                                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick="openTransferDetailDrawer(<?= (int)$trf['id'] ?>)">
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
                    <select id="tableLimitSelect" onchange="changeTransfersLimit(this.value)" style="height: 28px; padding: 0 8px; border-radius: 6px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff;">
                        <?php foreach ([25, 50, 75, 100, 500] as $lim): ?>
                            <option value="<?= $lim ?>" <?= ($limit === $lim) ? 'selected' : '' ?>><?= $lim ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 4px;">
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page <= 1) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToTransfersPage(' . ($page - 1) . ')"' ?>>← Prev</button>
                    <span style="font-size: 12px; font-weight: 700; padding: 4px 8px; color: #0f172a;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page >= $totalPages) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToTransfersPage(' . ($page + 1) . ')"' ?>>Next →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide-in Transfer Detail Drawer -->
<div class="inv-drawer-backdrop" id="transferDrawerBackdrop" onclick="closeTransferDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerTrfNumber" style="font-size: 0.85rem; color: #0284c7; background: #e0f2fe; border: 1px solid #bae6fd;"></span>
                <span id="drawerTrfStatusBadge" style="margin-left: 6px;"></span>
            </div>
            <button type="button" onclick="closeTransferDetailDrawer()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <div class="inv-drawer-body">
            <!-- Branch Flow Card -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: grid; grid-template-columns: 1fr auto 1fr; gap: 12px; align-items: center; text-align: center;">
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">FROM BRANCH</span>
                    <div id="drawerFromBranch" style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="color: #94a3b8;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">TO BRANCH</span>
                    <div id="drawerToBranch" style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <!-- Transfer Meta Details -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">DATE</span>
                    <div id="drawerDate" style="font-size: 12.5px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">CREATED BY</span>
                    <div id="drawerCreator" style="font-size: 12.5px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <!-- Transferred Items List -->
            <div>
                <h4 style="margin: 0 0 10px 0; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Transferred Items & Lots</h4>
                <div id="drawerItemsContainer" style="display: flex; flex-direction: column; gap: 8px;">
                    <!-- Rendered via JS -->
                </div>
            </div>

            <div id="drawerNotesBox" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px; display: none;">
                <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">NOTES</span>
                <div id="drawerNotes" style="font-size: 12px; color: #334155; margin-top: 2px; white-space: pre-wrap;"></div>
            </div>
        </div>
        <div class="inv-drawer-footer" id="drawerFooterActions">
            <!-- Dynamic Action Buttons rendered via JS -->
        </div>
    </div>
</div>

<script>
function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    // Enhance filter select with CurtissSearchableSelect
    document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
        if (window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(el);
        }
    });

    // Register Real-time Search Hook on Floating Bottom Nav Bar
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search Transfer #, branch, creator, notes...',
                initialValue: <?= json_encode($search) ?>,
                onSearch: function(query) {
                    filterTransfersTableRealtime(query);
                },
                onEnter: function(query) {
                    const params = new URLSearchParams(window.location.search);
                    if (query && query.trim()) {
                        params.set('search', query.trim());
                    } else {
                        params.delete('search');
                    }
                    params.set('page', '1');
                    window.location.href = 'stock_transfers.php?' + params.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();
});

function applyTransferFilter() {
    const st = document.getElementById('filterTransferStatus').value;
    const params = new URLSearchParams(window.location.search);
    if (st) {
        params.set('status', st);
    } else {
        params.delete('status');
    }
    params.set('page', '1');
    window.location.href = 'stock_transfers.php?' + params.toString();
}

function changeTransfersLimit(limitVal) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', limitVal);
    params.set('page', '1');
    window.location.href = 'stock_transfers.php?' + params.toString();
}

function goToTransfersPage(targetPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', targetPage);
    window.location.href = 'stock_transfers.php?' + params.toString();
}

function exportTransfersCsv() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'stock_transfers_export.php?' + params.toString();
}

function filterTransfersTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#transfersTable tbody tr.inv-row');
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

async function openTransferDetailDrawer(transferId) {
    try {
        const res = await fetch(`inventory_api.php?action=get_transfer_details&transfer_id=${transferId}`);
        const data = await res.json();
        if (!data.success) {
            alert(data.message || 'Failed to fetch transfer details');
            return;
        }

        const t = data.transfer;
        const items = data.items || [];

        document.getElementById('drawerTrfNumber').textContent = t.transfer_number || ('TRF-#' + t.id);
        
        const st = (t.status || '').toLowerCase();
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

        const badgeBox = document.getElementById('drawerTrfStatusBadge');
        badgeBox.className = 'trf-status-badge ' + badgeClass;
        badgeBox.textContent = statusLabel;

        document.getElementById('drawerFromBranch').textContent = t.from_branch_name || 'N/A';
        document.getElementById('drawerToBranch').textContent = t.to_branch_name || 'N/A';
        document.getElementById('drawerDate').textContent = t.transfer_date || 'N/A';
        document.getElementById('drawerCreator').textContent = t.creator_name || 'System';

        const notesBox = document.getElementById('drawerNotesBox');
        if (t.notes) {
            document.getElementById('drawerNotes').textContent = t.notes;
            notesBox.style.display = 'block';
        } else {
            notesBox.style.display = 'none';
        }

        const itemsContainer = document.getElementById('drawerItemsContainer');
        itemsContainer.innerHTML = '';
        if (!items.length) {
            itemsContainer.innerHTML = '<div style="color: #94a3b8; font-size: 12px;">No items attached to this transfer.</div>';
        } else {
            items.forEach(it => {
                const itemDiv = document.createElement('div');
                itemDiv.style.cssText = 'background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center;';
                itemDiv.innerHTML = `
                    <div>
                        <strong style="color: #0f172a; font-size: 13px;">${esc(it.product_name)}</strong>
                        <div style="display: flex; gap: 6px; margin-top: 4px;">
                            <span class="inv-code-pill">${esc(it.barcode || 'N/A')}</span>
                            ${it.lot_number ? `<span class="inv-code-pill" style="background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe;">${esc(it.lot_number)}</span>` : ''}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">QUANTITY</span>
                        <div style="font-size: 1.05rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a;">${parseFloat(it.quantity || 0).toFixed(2)}</div>
                    </div>
                `;
                itemsContainer.appendChild(itemDiv);
            });
        }

        // Render dynamic drawer footer actions
        const footerBox = document.getElementById('drawerFooterActions');
        footerBox.innerHTML = '';

        const currentBranchId = <?= (int)$branchId ?>;
        const isSender = (parseInt(t.from_branch_id) === currentBranchId);
        const isReceiver = (parseInt(t.to_branch_id) === currentBranchId);

        if (st === 'draft' && isSender) {
            footerBox.innerHTML += `
                <a href="stock_transfer_create?id=${t.id}" class="inv-btn inv-btn-ghost">Edit Transfer</a>
                <button type="button" class="inv-btn inv-btn-warning" onclick="handleSendTransfer(${t.id})">Send (In-Transit)</button>
                <button type="button" class="inv-btn" style="color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;" onclick="handleCancelTransfer(${t.id})">Cancel</button>
            `;
        }

        const canReceivePermission = <?= (Auth::hasPermission('receive_stock_transfer') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) ? 'true' : 'false' ?>;
        if (st === 'sent' && isReceiver && canReceivePermission) {
            footerBox.innerHTML += `
                <button type="button" class="inv-btn inv-btn-success" onclick="handleReceiveTransfer(${t.id})">Confirm Receive</button>
            `;
        }

        footerBox.innerHTML += `<button type="button" class="inv-btn inv-btn-ghost" onclick="closeTransferDetailDrawer()">Close</button>`;

        document.getElementById('transferDrawerBackdrop').classList.add('active');
    } catch (e) {
        console.error(e);
        alert('Failed to load transfer details.');
    }
}

function closeTransferDetailDrawer() {
    document.getElementById('transferDrawerBackdrop').classList.remove('active');
}

async function handleCancelTransfer(transferId) {
    if (!confirm('Are you sure you want to cancel this stock transfer?')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'cancel_transfer');
        formData.append('transfer_id', transferId);

        const res = await fetch('inventory_api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message || 'Transfer cancelled successfully');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to cancel transfer');
        }
    } catch (e) {
        console.error(e);
        alert('An error occurred while cancelling transfer.');
    }
}

async function handleSendTransfer(transferId) {
    if (!confirm('Are you sure you want to send this stock transfer? Stock will be marked In-Transit.')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'send_transfer');
        formData.append('transfer_id', transferId);

        const res = await fetch('inventory_api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message || 'Transfer sent successfully');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to send transfer');
        }
    } catch (e) {
        console.error(e);
        alert('An error occurred while sending transfer.');
    }
}

async function handleReceiveTransfer(transferId) {
    if (!confirm('Confirm receiving this stock transfer? Stock will be added to your branch inventory.')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'receive_transfer');
        formData.append('transfer_id', transferId);

        const res = await fetch('inventory_api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message || 'Transfer received successfully');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to receive transfer');
        }
    } catch (e) {
        console.error(e);
        alert('An error occurred while receiving transfer.');
    }
}
</script>
