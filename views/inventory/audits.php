<?php
/**
 * Curtiss POS - Physical Stock Audit View (Master Rules Enforced)
 * Fixed viewport height calc(100vh - 110px), sticky header, zebra striping, 48px rows,
 * 28px badges/buttons, floating bottom nav search, server-side pagination, slide-in detail drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (!$activeAudit && (Auth::hasPermission('manage_stock_audits') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) && !Auth::isSpecialUser()): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openStartAuditModal()" style="height: 34px; padding: 0 14px; border-radius: 999px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>+ Start Audit</span>
    </button>
    <?php endif; ?>

    <a href="stock_audits_export.php?branch_id=<?= (int)$branchId ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>" class="inv-btn inv-btn-ghost" style="height: 34px; padding: 0 14px; border-radius: 999px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Export CSV</span>
    </a>

    <a href="inventory.php" class="inv-btn inv-btn-ghost" style="height: 34px; padding: 0 14px; border-radius: 999px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        <span>Branch Inventory</span>
    </a>
</div>

<style>
/* ── Master Theme & Typography ── */
.inv-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    margin: 0;
}

.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 34px;
    padding: 0 14px;
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
    background: rgba(255,255,255,0.95);
    border-color: #cbd5e1;
    color: #0f172a;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
}
.inv-btn-ghost:hover {
    background: #ffffff;
    border-color: #94a3b8;
    transform: translateY(-1px);
}
.inv-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15,23,42,0.2);
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

/* ── Standard 48px Table & Sticky Header ── */
.prod-table-panel {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 14px;
    box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.prod-table-scroll {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

.prod-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12.5px;
}

.prod-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}

.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.15s ease;
}

.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:hover            { background: #f1f5f9 !important; }

.prod-table tbody td {
    height: 48px;
    padding: 0 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
    box-sizing: border-box;
    border-bottom: 1px solid #f1f5f9;
}

.prod-table .col-right {
    text-align: right;
}
.prod-table .col-center {
    text-align: center;
}

/* 28px in-cell badges & buttons */
.aud-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    text-transform: uppercase;
    box-sizing: border-box;
}
.aud-badge-counting { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.aud-badge-posted   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.aud-badge-review   { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.aud-badge-draft    { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

.aud-act-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #0f172a;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
    white-space: nowrap;
}
.aud-act-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.inv-code-pill {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 800;
    background: #f1f5f9;
    color: #0f172a;
    padding: 2px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

/* Slide-in Detail Drawer */
.inv-drawer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
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
    right: -560px;
    width: 520px;
    max-width: 95vw;
    height: 100vh;
    background: #ffffff;
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.18);
    z-index: 2001;
    transition: right 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
}
.inv-drawer-backdrop.active .inv-drawer {
    right: 0;
}
.inv-drawer-header {
    padding: 1.15rem 1.4rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}
.inv-drawer-body {
    padding: 1.25rem 1.4rem;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}
.inv-drawer-footer {
    padding: 1rem 1.4rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
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
    display: flex;
    align-items: center;
    justify-content: center;
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
    max-width: 520px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.18);
    overflow: hidden;
    animation: modalSlideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.inv-modal-header {
    padding: 1.15rem 1.4rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.inv-modal-body {
    padding: 1.25rem 1.4rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.inv-modal-footer {
    padding: 1rem 1.4rem;
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
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.inv-input {
    width: 100%;
    height: 36px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0 10px;
    font-size: 12.5px;
    color: #0f172a;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.15s ease;
}
.inv-input:focus {
    border-color: #0f172a;
}
</style>

<!-- Main Workarea Container (Fixed Viewport calc(100vh - 110px)) -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline ONLY, No Subtitle Tagline) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Physical Stock Audit</h1>
        </div>

        <!-- Inline Status Filter (34px height) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterAuditStatus" onchange="applyAuditFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #ffffff; border: 1px solid #cbd5e1; color: #0f172a; min-width: 180px;">
                    <option value="" <?= ($statusFilter === '') ? 'selected' : '' ?>>Posted & Locked Audits</option>
                    <option value="posted" <?= ($statusFilter === 'posted') ? 'selected' : '' ?>>Posted Only</option>
                    <option value="review" <?= ($statusFilter === 'review') ? 'selected' : '' ?>>In Review</option>
                    <option value="counting" <?= ($statusFilter === 'counting') ? 'selected' : '' ?>>Counting</option>
                    <option value="draft" <?= ($statusFilter === 'draft') ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
        </div>
    </div>

    <?php if ($activeAudit): ?>
    <!-- Active Audit Workspace Panel -->
    <div class="prod-table-panel" style="margin-bottom: 12px; flex-shrink: 0; max-height: 48vh; display: flex; flex-direction: column; border-color: #bae6fd; background: rgba(240, 249, 255, 0.98);">
        <div style="padding: 10px 14px; border-bottom: 1px solid #bae6fd; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: #f0f9ff;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span class="inv-code-pill" style="font-size: 12px; color: #0284c7; background: #ffffff; border-color: #bae6fd;"><?= View::escape($activeAudit['audit_number']) ?></span>
                <span class="aud-badge aud-badge-counting">Counting & Variance Entry</span>
                <span style="font-size: 11.5px; color: #0369a1; font-weight: 600;">
                    Started: <strong><?= View::formatDate($activeAudit['audit_date']) ?></strong> | <strong><?= count($auditItems) ?></strong> items snapshotted
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="handleSaveCounts(<?= (int)$activeAudit['id'] ?>)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span>Save Count Progress</span>
                </button>
                <?php if (!Auth::isSpecialUser()): ?>
                <button type="button" class="inv-btn inv-btn-success" onclick="handlePostAudit(<?= (int)$activeAudit['id'] ?>)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Post & Lock Audit</span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table" id="activeAuditTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Lot Number</th>
                        <th class="col-right">Expected Qty</th>
                        <th class="col-right" style="width: 140px;">Counted Qty *</th>
                        <th class="col-right">Variance</th>
                        <th class="col-right">Unit Cost (LKR)</th>
                        <th>Notes / Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditItems as $item): ?>
                        <?php
                        $var = (float)$item['variance_quantity'];
                        $varColor = abs($var) < 0.001 ? '#64748b' : ($var > 0 ? '#166534' : '#dc2626');
                        $unitCost = (float)($item['lot_cost'] ?? $item['cost'] ?? 0);
                        $searchData = strtolower(($item['product_name'] ?? '') . ' ' . ($item['barcode'] ?? '') . ' ' . ($item['lot_number'] ?? '') . ' ' . ($item['notes'] ?? '') . ' ' . $item['expected_quantity'] . ' ' . $item['counted_quantity']);
                        ?>
                        <tr class="inv-row" data-search="<?= View::escape($searchData) ?>">
                            <td>
                                <div style="font-weight: 700; color: #0f172a; line-height: 1.2;"><?= View::escape($item['product_name']) ?></div>
                                <span class="inv-code-pill" style="margin-top: 2px; font-size: 10px;"><?= View::escape($item['barcode']) ?></span>
                            </td>
                            <td>
                                <span class="inv-code-pill"><?= View::escape($item['lot_number']) ?></span>
                            </td>
                            <td class="col-right" style="font-weight: 800; font-family: ui-monospace, monospace;">
                                <?= number_format((float)$item['expected_quantity'], 2) ?>
                            </td>
                            <td class="col-right" onclick="event.stopPropagation()">
                                <input type="number" step="0.01" class="inv-input audit-counted-input" data-item-id="<?= (int)$item['id'] ?>" data-expected="<?= (float)$item['expected_quantity'] ?>" value="<?= number_format((float)$item['counted_quantity'], 2, '.', '') ?>" style="height: 30px; width: 110px; text-align: right; font-weight: 800; font-family: ui-monospace, monospace; margin-left: auto;" oninput="recalcVariance(this)">
                            </td>
                            <td class="col-right audit-variance-val" style="font-weight: 800; font-family: ui-monospace, monospace; color: <?= $varColor ?>;">
                                <?= ($var > 0 ? '+' : '') . number_format($var, 2) ?>
                            </td>
                            <td class="col-right" style="font-family: ui-monospace, monospace; font-weight: 600; color: #475569;">
                                <?= number_format($unitCost, 2) ?>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <input type="text" class="inv-input audit-notes-input" data-item-id="<?= (int)$item['id'] ?>" value="<?= View::escape($item['notes'] ?? '') ?>" placeholder="Diff notes..." style="height: 30px; font-size: 11.5px;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Completed & Posted Audit History Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; flex-shrink: 0;">
            <div style="font-size: 12.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <span>Posted Audit History (Locked & Immutable)</span>
                <span class="aud-badge aud-badge-posted">History</span>
            </div>
            <div style="font-size: 11px; color: #64748b; font-weight: 600;">
                Click any row to inspect itemized audit details & variances
            </div>
        </div>

        <div class="prod-table-scroll">
            <table class="prod-table" id="completedAuditsTable">
                <thead>
                    <tr>
                        <th style="width: 140px;">Audit #</th>
                        <th style="width: 120px;">Audit Date</th>
                        <th>Created By</th>
                        <th>Posted By</th>
                        <th>Posted At</th>
                        <th class="col-center" style="width: 130px;">Status</th>
                        <th class="col-right" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="completedAuditsTableBody">
                    <?php if (empty($completedAudits)): ?>
                        <tr>
                            <td colspan="7" class="col-center" style="color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                                No completed or posted stock audit records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($completedAudits as $ca): ?>
                            <?php
                            $searchData = strtolower(($ca['audit_number'] ?? '') . ' ' . ($ca['audit_date'] ?? '') . ' ' . ($ca['creator_name'] ?? '') . ' ' . ($ca['poster_name'] ?? '') . ' ' . ($ca['notes'] ?? '') . ' ' . ($ca['posted_at'] ?? ''));
                            ?>
                            <tr class="inv-row" data-search="<?= View::escape($searchData) ?>" onclick="openAuditDetailDrawer(<?= (int)$ca['id'] ?>)" style="cursor: pointer;">
                                <td>
                                    <span class="inv-code-pill" style="color: #15803d; background: #dcfce7; border-color: #bbf7d0;"><?= View::escape($ca['audit_number']) ?></span>
                                </td>
                                <td style="color: #475569; font-weight: 600;">
                                    <?= View::formatDate($ca['audit_date']) ?>
                                </td>
                                <td style="font-weight: 600; color: #334155;">
                                    <?= View::escape($ca['creator_name']) ?>
                                </td>
                                <td style="font-weight: 600; color: #334155;">
                                    <?= View::escape($ca['poster_name'] ?? 'System') ?>
                                </td>
                                <td style="color: #64748b; font-size: 11.5px;">
                                    <?= View::escape($ca['posted_at'] ?? '-') ?>
                                </td>
                                <td class="col-center">
                                    <span class="aud-badge aud-badge-posted">Posted</span>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation()">
                                    <button type="button" class="aud-act-btn" onclick="openAuditDetailDrawer(<?= (int)$ca['id'] ?>)">
                                        Summary
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Counter Footer -->
        <div style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; border-top: 1px solid #e2e8f0; background: #f8fafc; font-size: 11.5px; color: #64748b; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>
                    <?php
                    $startEntry = $totalRecords > 0 ? (($page - 1) * $limit + 1) : 0;
                    $endEntry   = min($totalRecords, $page * $limit);
                    ?>
                    Showing <strong><?= $startEntry ?></strong> to <strong><?= $endEntry ?></strong> of <strong><?= $totalRecords ?></strong> entries
                </span>
                <div style="display: flex; align-items: center; gap: 4px;">
                    <span style="font-size: 11px;">Per page:</span>
                    <select id="auditPageLimit" onchange="changeAuditLimit(this.value)" style="height: 28px; padding: 0 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 11px; background: #ffffff; color: #0f172a;">
                        <?php foreach ([25, 50, 75, 100, 500] as $limOpt): ?>
                            <option value="<?= $limOpt ?>" <?= ($limit === $limOpt) ? 'selected' : '' ?>><?= $limOpt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
                <?php if ($page > 1): ?>
                    <a href="stock_audits.php?branch_id=<?= (int)$branchId ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>&page=<?= ($page - 1) ?>" class="aud-act-btn" style="height: 28px; padding: 0 10px;">&laquo; Prev</a>
                <?php else: ?>
                    <span class="aud-act-btn" style="opacity: 0.5; pointer-events: none; height: 28px; padding: 0 10px;">&laquo; Prev</span>
                <?php endif; ?>

                <span style="font-weight: 700; color: #0f172a; padding: 0 6px;">Page <?= $page ?> of <?= $totalPages ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="stock_audits.php?branch_id=<?= (int)$branchId ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>&page=<?= ($page + 1) ?>" class="aud-act-btn" style="height: 28px; padding: 0 10px;">Next &raquo;</a>
                <?php else: ?>
                    <span class="aud-act-btn" style="opacity: 0.5; pointer-events: none; height: 28px; padding: 0 10px;">Next &raquo;</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Slide-in Audit Detail Drawer -->
<div class="inv-drawer-backdrop" id="auditDrawerBackdrop" onclick="closeAuditDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerAuditNumber" style="font-size: 13px; color: #15803d; background: #dcfce7; border-color: #bbf7d0;"></span>
                <span class="aud-badge aud-badge-posted" style="margin-left: 6px;">Posted & Locked</span>
            </div>
            <button class="inv-drawer-close" onclick="closeAuditDetailDrawer()" title="Close (Esc)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="inv-drawer-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">AUDIT DATE</span>
                    <div id="drawerAuditDate" style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">CREATED BY</span>
                    <div id="drawerAuditCreator" style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">POSTED BY</span>
                    <div id="drawerAuditPoster" style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">POSTED TIMESTAMP</span>
                    <div id="drawerAuditPostedAt" style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div id="drawerAuditNotesWrap" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px; display: none;">
                <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">NOTES</span>
                <div id="drawerAuditNotes" style="font-size: 12px; color: #334155; margin-top: 2px;"></div>
            </div>

            <div>
                <h4 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em;">Audited Items & Variances</h4>
                <div id="drawerAuditItemsContainer" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Rendered dynamically -->
                </div>
            </div>
        </div>

        <div class="inv-drawer-footer">
            <button class="inv-btn inv-btn-ghost" onclick="closeAuditDetailDrawer()">Close</button>
        </div>
    </div>
</div>

<!-- Modal: Start Stock Audit -->
<div class="inv-modal-backdrop" id="startAuditModal" onclick="closeStartAuditModal()">
    <div class="inv-modal-box" onclick="event.stopPropagation()">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 14px; font-weight: 800; color: #0f172a;">Start Physical Stock Audit</h3>
            <button class="inv-drawer-close" onclick="closeStartAuditModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="startAuditForm" onsubmit="handleStartAuditSubmit(event)">
            <input type="hidden" name="branch_id" value="<?= (int)$branchId ?>">
            <div class="inv-modal-body">
                <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 10px; padding: 12px; color: #0369a1; font-size: 12px; line-height: 1.45;">
                    <strong>Audit Session Overview:</strong><br>
                    Starting an audit session snapshots current expected quantities for all active stock lots in <strong><?= View::escape($activeBranchName) ?></strong>. You can then record physical counts and reconcile variances.
                </div>

                <div class="inv-field">
                    <label class="inv-label">Audit Notes / Remarks</label>
                    <textarea class="inv-input" name="notes" rows="3" style="height: auto; padding: 8px 10px;" placeholder="e.g. Month-End Physical Audit September 2026"></textarea>
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
    // 1. Initialize searchable select on status filter dropdown
    const statusSelect = document.getElementById('filterAuditStatus');
    if (statusSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(statusSelect);
    }

    // 2. Register Universal Floating Search on bottom nav
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search Audit #, Date, Product, Lot, Status, User...',
                initialValue: <?= json_encode($search) ?>,
                onSearch: function(query) {
                    filterAuditsTableRealtime(query);
                },
                onEnter: function(query) {
                    const params = new URLSearchParams(window.location.search);
                    params.set('search', query.trim());
                    params.set('page', '1');
                    window.location.search = params.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 50);
        }
    }
    registerBottomSearch();

    // 3. Escape key listener for drawer and modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAuditDetailDrawer();
            closeStartAuditModal();
        }
    });

    // 4. Keyboard Navigation: Enter advances focus on forms
    document.querySelectorAll('#startAuditForm input, #startAuditForm textarea').forEach((input) => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                document.getElementById('startAuditBtn').click();
            }
        });
    });
});

function applyAuditFilters() {
    const status = document.getElementById('filterAuditStatus').value;
    const params = new URLSearchParams(window.location.search);
    if (status) {
        params.set('status', status);
    } else {
        params.delete('status');
    }
    params.set('page', '1');
    window.location.search = params.toString();
}

function changeAuditLimit(newLimit) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', newLimit);
    params.set('page', '1');
    window.location.search = params.toString();
}

function filterAuditsTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.inv-row');

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        // Also search data cells excluding action column
        const cellsText = Array.from(tr.querySelectorAll('td:not(.col-right)'))
            .map(td => td.innerText.toLowerCase())
            .join(' ');
        const combined = searchAttr + ' ' + cellsText;

        if (!q || combined.includes(q)) {
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
            btn.disabled = false;
        }
    } catch (err) {
        alert('Server error: ' + err.message);
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

        const notesWrap = document.getElementById('drawerAuditNotesWrap');
        if (aud.notes && aud.notes.trim()) {
            document.getElementById('drawerAuditNotes').textContent = aud.notes;
            notesWrap.style.display = 'block';
        } else {
            notesWrap.style.display = 'none';
        }

        let itemsHtml = '';
        let totalCostVariance = 0;

        items.forEach(item => {
            const exp = parseFloat(item.expected_quantity || 0);
            const cnt = parseFloat(item.counted_quantity || 0);
            const varQty = parseFloat(item.variance_quantity || 0);
            const cost = parseFloat(item.lot_cost || item.cost || 0);
            const varCostVal = varQty * cost;
            totalCostVariance += varCostVal;

            const varColor = Math.abs(varQty) < 0.001 ? '#64748b' : (varQty > 0 ? '#166534' : '#dc2626');

            itemsHtml += `
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 6px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                        <div>
                            <div style="font-weight: 800; color: #0f172a; font-size: 13px;">${esc(item.product_name)}</div>
                            <span class="inv-code-pill" style="font-size: 10px; margin-top: 2px;">${esc(item.barcode)}</span>
                        </div>
                        <span class="inv-code-pill" style="background: #f8fafc; color: #475569; font-size: 11px;">Lot: ${esc(item.lot_number || 'N/A')}</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 6px; background: #f8fafc; padding: 8px; border-radius: 8px; margin-top: 4px; font-size: 11.5px; text-align: center;">
                        <div>
                            <span style="color: #64748b; font-size: 9.5px; font-weight: 800; text-transform: uppercase;">EXPECTED</span>
                            <div style="font-weight: 800; color: #0f172a; font-family: monospace;">${exp.toFixed(2)}</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 9.5px; font-weight: 800; text-transform: uppercase;">COUNTED</span>
                            <div style="font-weight: 800; color: #0f172a; font-family: monospace;">${cnt.toFixed(2)}</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 9.5px; font-weight: 800; text-transform: uppercase;">VARIANCE</span>
                            <div style="font-weight: 800; font-family: monospace; color: ${varColor};">${(varQty > 0 ? '+' : '') + varQty.toFixed(2)}</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 9.5px; font-weight: 800; text-transform: uppercase;">COST IMPACT</span>
                            <div style="font-weight: 800; font-family: monospace; color: ${varColor};">${(varCostVal >= 0 ? '+' : '') + varCostVal.toFixed(2)}</div>
                        </div>
                    </div>
                    ${item.notes ? `<div style="font-size: 11px; color: #64748b; font-style: italic; margin-top: 2px;">Note: ${esc(item.notes)}</div>` : ''}
                </div>
            `;
        });

        itemsHtml += `
            <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: #ffffff; padding: 12px 14px; border-radius: 10px; font-size: 12.5px; margin-top: 4px;">
                <span style="font-weight: 700;">Total Variance Impact:</span>
                <span style="font-weight: 800; font-family: monospace; font-size: 14px; color: ${totalCostVariance >= 0 ? '#4ade80' : '#f87171'};">
                    ${totalCostVariance >= 0 ? '+' : ''}${totalCostVariance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
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
