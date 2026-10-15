<?php
/**
 * Curtiss POS - Physical Stock Audit View (Redesign Theme & Design Rules Enforced)
 * Glassmorphism layout, floating bottom nav search, top-right header searchable select filter & detail drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <form method="GET" action="stock_audits.php" id="headerAuditFilterForm" style="margin: 0; padding: 0;">
        <input type="hidden" name="branch_id" value="<?= $branchId ?>">
        <select name="status" id="headerAuditStatusSelect" class="inv-select curtiss-select-enhance" style="height: 38px; padding: 0 16px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.96); border: 1px solid rgba(226,232,240,0.9); color: #0f172a; min-width: 190px;" onchange="this.form.submit()">
            <option value="">All Audit Sessions</option>
            <option value="posted" <?= ($statusFilter === 'posted') ? 'selected' : '' ?>>Posted & Locked Audits</option>
        </select>
    </form>

    <?php if (!$activeAudit && Auth::hasPermission('manage_stock_audits')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openStartAuditModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Start Physical Stock Audit
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
.inv-btn-success {
    background: #166534;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(22,101,52,0.2);
}
.inv-btn-success:hover {
    background: #14532d;
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

.aud-status-badge {
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
.aud-status-counting { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.aud-status-posted { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

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
    max-width: 540px;
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
    <h1 class="inv-title">Physical Stock Audit</h1>
    <p class="inv-subtitle">Perform physical stock counts, calculate snapshot variances, and post immutable stock adjustments for <strong><?= View::escape($activeBranchName) ?></strong>.</p>
</div>

<?php if (!$activeAudit): ?>
<!-- Empty Active Session State -->
<div class="inv-card" style="padding: 3rem 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
    <div style="width: 56px; height: 56px; border-radius: 999px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: #0f172a;">No Active Stock Audit Session</h3>
    <p style="color: #64748b; font-size: 0.85rem; max-width: 440px; margin: 0.5rem 0 1.25rem 0;">Click below to snapshot expected branch stock and start physical counting for variance reconciliation.</p>
    <?php if (Auth::hasPermission('manage_stock_audits')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openStartAuditModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Start New Audit Session
    </button>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Active Audit Session Workspace Card -->
<div class="inv-card" style="border-color: #bae6fd; background: rgba(240, 249, 255, 0.96); margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="inv-code-pill" style="font-size: 0.9rem; color: #0284c7; background: #ffffff; border-color: #bae6fd;"><?= View::escape($activeAudit['audit_number']) ?></span>
                <span class="aud-status-badge aud-status-counting">Counting & Variance Entry</span>
            </div>
            <div style="font-size: 0.78rem; color: #0369a1; margin-top: 4px; font-weight: 600;">
                Started on <strong><?= View::formatDate($activeAudit['audit_date']) ?></strong> | <strong><?= count($auditItems) ?></strong> items snapshotted
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="handleSaveCounts(<?= (int)$activeAudit['id'] ?>)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Count Progress
            </button>
            <button type="button" class="inv-btn inv-btn-success" onclick="handlePostAudit(<?= (int)$activeAudit['id'] ?>)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Post & Lock Audit
            </button>
        </div>
    </div>

    <!-- Count Items Table -->
    <div class="inv-table-wrap" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
        <table class="inv-table" id="activeAuditTable">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Lot Number</th>
                    <th style="text-align: right;">Expected Qty</th>
                    <th style="text-align: right; width: 140px;">Counted Qty *</th>
                    <th style="text-align: right;">Variance</th>
                    <th>Notes / Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditItems as $item): ?>
                    <?php
                    $var = (float)$item['variance_quantity'];
                    $varColor = abs($var) < 0.001 ? '#64748b' : ($var > 0 ? '#166534' : '#dc2626');
                    $searchData = strtolower(($item['product_name'] ?? '') . ' ' . ($item['barcode'] ?? '') . ' ' . ($item['lot_number'] ?? '') . ' ' . ($item['notes'] ?? ''));
                    ?>
                    <tr class="inv-row" data-search="<?= View::escape($searchData) ?>">
                        <td>
                            <div style="font-weight: 700; color: #0f172a;"><?= View::escape($item['product_name']) ?></div>
                            <span class="inv-code-pill" style="margin-top: 2px; font-size: 0.68rem;"><?= View::escape($item['barcode']) ?></span>
                        </td>
                        <td>
                            <span class="inv-code-pill"><?= View::escape($item['lot_number']) ?></span>
                        </td>
                        <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a;">
                            <?= number_format((float)$item['expected_quantity'], 2) ?>
                        </td>
                        <td style="text-align: right;" onclick="event.stopPropagation()">
                            <input type="number" step="0.01" class="inv-input audit-counted-input" data-item-id="<?= (int)$item['id'] ?>" data-expected="<?= (float)$item['expected_quantity'] ?>" value="<?= number_format((float)$item['counted_quantity'], 2, '.', '') ?>" style="height: 32px; width: 110px; text-align: right; font-weight: 800; font-family: ui-monospace, monospace;" oninput="recalcVariance(this)">
                        </td>
                        <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace; color: <?= $varColor ?>;" class="audit-variance-val">
                            <?= ($var > 0 ? '+' : '') . number_format($var, 2) ?>
                        </td>
                        <td onclick="event.stopPropagation()">
                            <input type="text" class="inv-input audit-notes-input" data-item-id="<?= (int)$item['id'] ?>" value="<?= View::escape($item['notes'] ?? '') ?>" placeholder="Reason for diff..." style="height: 32px; font-size: 0.8rem;">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Completed Audit History Table -->
<div class="inv-card">
    <div style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
        <span>Posted Audit History (Locked)</span>
        <span class="aud-status-badge aud-status-posted">Immutable</span>
    </div>

    <div class="inv-table-wrap">
        <table class="inv-table" id="completedAuditsTable">
            <thead>
                <tr>
                    <th>Audit #</th>
                    <th>Audit Date</th>
                    <th>Created By</th>
                    <th>Posted By</th>
                    <th>Posted At</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($completedAudits)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                            No completed or posted stock audit records yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($completedAudits as $ca): ?>
                        <?php
                        $searchData = strtolower(($ca['audit_number'] ?? '') . ' ' . ($ca['audit_date'] ?? '') . ' ' . ($ca['creator_name'] ?? '') . ' ' . ($ca['poster_name'] ?? '') . ' ' . ($ca['notes'] ?? ''));
                        ?>
                        <tr class="inv-row" data-search="<?= View::escape($searchData) ?>" onclick="openAuditDetailDrawer(<?= (int)$ca['id'] ?>)">
                            <td>
                                <span class="inv-code-pill" style="color: #15803d; background: #dcfce7; border-color: #bbf7d0;"><?= View::escape($ca['audit_number']) ?></span>
                            </td>
                            <td style="color: #64748b; font-size: 0.78rem;">
                                <?= View::formatDate($ca['audit_date']) ?>
                            </td>
                            <td style="font-weight: 600; color: #475569; font-size: 0.78rem;">
                                <?= View::escape($ca['creator_name']) ?>
                            </td>
                            <td style="font-weight: 600; color: #475569; font-size: 0.78rem;">
                                <?= View::escape($ca['poster_name'] ?? 'System') ?>
                            </td>
                            <td style="color: #64748b; font-size: 0.78rem;">
                                <?= View::escape($ca['posted_at']) ?>
                            </td>
                            <td>
                                <span class="aud-status-badge aud-status-posted">Posted & Locked</span>
                            </td>
                            <td style="text-align: right;" onclick="event.stopPropagation()">
                                <button type="button" class="inv-btn inv-btn-ghost" style="padding: 4px 10px; font-size: 11px;" onclick="openAuditDetailDrawer(<?= (int)$ca['id'] ?>)">
                                    View Summary
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Slide-in Audit Detail Drawer -->
<div class="inv-drawer-backdrop" id="auditDrawerBackdrop" onclick="closeAuditDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerAuditNumber" style="font-size: 0.85rem; color: #15803d; background: #dcfce7; border-color: #bbf7d0;"></span>
                <span class="aud-status-badge aud-status-posted" style="margin-left: 6px;">Posted & Locked</span>
            </div>
            <button class="inv-drawer-close" onclick="closeAuditDetailDrawer()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="inv-drawer-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">AUDIT DATE</span>
                    <div id="drawerAuditDate" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">CREATED BY</span>
                    <div id="drawerAuditCreator" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">POSTED BY</span>
                    <div id="drawerAuditPoster" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">POSTED TIMESTAMP</span>
                    <div id="drawerAuditPostedAt" style="font-size: 0.78rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div>
                <h4 style="margin: 0 0 10px 0; font-size: 0.88rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.03em;">Audited Items & Variances</h4>
                <div id="drawerAuditItemsContainer" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Rendered via JS -->
                </div>
            </div>
        </div>
        <div class="inv-drawer-footer">
            <button class="inv-btn inv-btn-ghost" onclick="closeAuditDetailDrawer()">Close</button>
        </div>
    </div>
</div>

<!-- Modal: Start Stock Audit -->
<div class="inv-modal-backdrop" id="startAuditModal">
    <div class="inv-modal-box">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Start Physical Stock Audit</h3>
            <button class="inv-drawer-close" onclick="closeStartAuditModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="startAuditForm" onsubmit="handleStartAuditSubmit(event)">
            <input type="hidden" name="branch_id" value="<?= $branchId ?>">
            <div class="inv-modal-body">
                <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 12px; padding: 12px; color: #0369a1; font-size: 0.82rem; line-height: 1.4;">
                    <strong>Audit Session Overview:</strong><br>
                    Starting an audit session will snapshot current expected quantities for all active stock lots in this branch. Physical counting can then be recorded and reconciled.
                </div>

                <div class="inv-field">
                    <label class="inv-label">Audit Notes / Remarks</label>
                    <textarea class="inv-input" name="notes" rows="3" style="height: auto; padding: 8px 12px;" placeholder="e.g. Month-End Physical Audit September 2026"></textarea>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeStartAuditModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="startAuditBtn">Snapshot & Start Audit</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
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
                placeholder: 'Search Audit #, Product, Lot, Status, User...',
                onSearch: function(query) {
                    filterAuditsTableRealtime(query);
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

function filterAuditsTableRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('.inv-row');

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        if (!q || searchAttr.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}

function openStartAuditModal() {
    document.getElementById('startAuditForm').reset();
    document.getElementById('startAuditModal').classList.add('active');
}

function closeStartAuditModal() {
    document.getElementById('startAuditModal').classList.remove('active');
}

function recalcVariance(input) {
    const counted = parseFloat(input.value) || 0;
    const expected = parseFloat(input.dataset.expected) || 0;
    const variance = counted - expected;
    const cell = input.closest('tr').querySelector('.audit-variance-val');
    cell.innerText = (variance > 0 ? '+' : '') + variance.toFixed(2);
    cell.style.color = Math.abs(variance) < 0.001 ? '#64748b' : (variance > 0 ? '#166534' : '#dc2626');
}

async function handleStartAuditSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('startAuditBtn');
    btn.disabled = true;

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('inventory_api.php?action=start_audit', {
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

async function handleSaveCounts(auditId) {
    const countedInputs = document.querySelectorAll('.audit-counted-input');
    const items = [];
    countedInputs.forEach(inp => {
        const itemId = parseInt(inp.dataset.itemId);
        const expected = parseFloat(inp.dataset.expected);
        const counted = parseFloat(inp.value) || 0;
        const noteInput = document.querySelector(`.audit-notes-input[data-item-id="${itemId}"]`);
        items.push({
            item_id: itemId,
            expected_quantity: expected,
            counted_quantity: counted,
            notes: noteInput ? noteInput.value : ''
        });
    });

    try {
        const res = await fetch('inventory_api.php?action=save_audit_counts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ audit_id: auditId, items: items })
        });
        const result = await res.json();
        alert(result.message);
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

async function handlePostAudit(auditId) {
    if (!confirm('Are you sure you want to post and lock this stock audit? Variance adjustments will be created automatically to update inventory and GL.')) return;
    await handleSaveCounts(auditId);
    try {
        const res = await fetch('inventory_api.php?action=post_audit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ audit_id: auditId })
        });
        const result = await res.json();
        alert(result.message);
        location.reload();
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

async function openAuditDetailDrawer(auditId) {
    const backdrop = document.getElementById('auditDrawerBackdrop');
    backdrop.classList.add('active');

    const itemsContainer = document.getElementById('drawerAuditItemsContainer');
    itemsContainer.innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 20px;">Loading audit details...</div>';

    try {
        const res = await fetch(`inventory_api.php?action=get_audit_details&audit_id=${auditId}`);
        const data = await res.json();

        if (!data.success) {
            itemsContainer.innerHTML = `<div style="color: #dc2626; text-align: center; padding: 20px;">${esc(data.message)}</div>`;
            return;
        }

        const aud = data.audit;
        const items = data.items || [];

        document.getElementById('drawerAuditNumber').textContent = aud.audit_number || '';
        document.getElementById('drawerAuditDate').textContent = aud.audit_date || 'N/A';
        document.getElementById('drawerAuditCreator').textContent = aud.creator_name || 'System';
        document.getElementById('drawerAuditPoster').textContent = aud.poster_name || 'System';
        document.getElementById('drawerAuditPostedAt').textContent = aud.posted_at || 'N/A';

        let itemsHtml = '';
        let totalCostVariance = 0;

        items.forEach(item => {
            const exp = parseFloat(item.expected_quantity || 0);
            const cnt = parseFloat(item.counted_quantity || 0);
            const varQty = parseFloat(item.variance_quantity || 0);
            const cost = parseFloat(item.lot_cost || 0);
            const varCostVal = varQty * cost;
            totalCostVariance += varCostVal;

            const varColor = Math.abs(varQty) < 0.001 ? '#64748b' : (varQty > 0 ? '#166534' : '#dc2626');

            itemsHtml += `
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 6px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-weight: 800; color: #0f172a; font-size: 0.88rem;">${esc(item.product_name)}</div>
                            <span class="inv-code-pill" style="font-size: 0.68rem; margin-top: 2px;">${esc(item.barcode)}</span>
                        </div>
                        <span class="inv-code-pill" style="background: #f8fafc; color: #475569;">Lot: ${esc(item.lot_number || 'N/A')}</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 6px; background: #f8fafc; padding: 8px; border-radius: 8px; margin-top: 4px; font-size: 0.75rem; text-align: center;">
                        <div>
                            <span style="color: #64748b; font-size: 0.62rem; font-weight: 700;">EXPECTED</span>
                            <div style="font-weight: 800; color: #0f172a;">${exp.toFixed(2)}</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 0.62rem; font-weight: 700;">COUNTED</span>
                            <div style="font-weight: 800; color: #0f172a;">${cnt.toFixed(2)}</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 0.62rem; font-weight: 700;">VARIANCE</span>
                            <div style="font-weight: 800; font-family: monospace; color: ${varColor};">${(varQty > 0 ? '+' : '') + varQty.toFixed(2)}</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 0.62rem; font-weight: 700;">COST IMPACT</span>
                            <div style="font-weight: 800; font-family: monospace; color: ${varColor};">LKR ${varCostVal.toFixed(2)}</div>
                        </div>
                    </div>
                </div>
            `;
        });

        itemsHtml += `
            <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: #ffffff; padding: 12px 14px; border-radius: 12px; font-size: 0.85rem;">
                <span style="font-weight: 700;">Total Variance Financial Impact:</span>
                <span style="font-weight: 800; font-family: monospace; font-size: 1rem; color: ${totalCostVariance >= 0 ? '#4ade80' : '#f87171'};">
                    LKR ${totalCostVariance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                </span>
            </div>
        `;

        itemsContainer.innerHTML = itemsHtml;
    } catch (e) {
        console.error(e);
        itemsContainer.innerHTML = '<div style="color: #dc2626; text-align: center; padding: 20px;">Failed to load audit details.</div>';
    }
}

function closeAuditDetailDrawer() {
    document.getElementById('auditDrawerBackdrop').classList.remove('active');
}
</script>
