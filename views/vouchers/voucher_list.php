<?php
/**
 * Curtiss POS - Gift Voucher Management View (Redesign Theme & Design Rules Enforced)
 * Glassmorphism layout, summary stats cards, slide drawer preview, floating bottom nav search, top-right header filter.
 */
$canManage = Auth::hasPermission('vouchers.manage') && !Auth::isSpecialUser();
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <form method="GET" action="vouchers.php" id="headerVoucherFilterForm" style="margin: 0; padding: 0;">
        <select name="status" id="headerVoucherStatusSelect" class="inv-select curtiss-select-enhance" style="height: 38px; padding: 0 16px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.96); border: 1px solid rgba(226,232,240,0.9); color: #0f172a; min-width: 170px;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="redeemed" <?= ($statusFilter === 'redeemed') ? 'selected' : '' ?>>Redeemed</option>
            <option value="expired" <?= ($statusFilter === 'expired') ? 'selected' : '' ?>>Expired</option>
            <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </form>

    <?php if ($canManage): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openIssueVoucherModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Issue Gift Voucher
    </button>
    <?php endif; ?>
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
.inv-btn-danger {
    background: #dc2626;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(220,38,38,0.2);
}
.inv-btn-danger:hover {
    background: #b91c1c;
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

.voucher-status-badge {
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
.v-status-active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.v-status-redeemed { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
.v-status-expired { background: #feefc3; color: #b45309; border: 1px solid #fde68a; }
.v-status-cancelled { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }

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

/* Pagination */
.inv-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
}
.page-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #0f172a;
    text-decoration: none;
    transition: all 0.15s ease;
}
.page-pill:hover {
    background: #f8fafc;
    border-color: #94a3b8;
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
    <h1 class="inv-title">Gift Vouchers</h1>
    <p class="inv-subtitle">Issue and track single-use gift vouchers for POS billing settlement.</p>
</div>

<?php if (!empty($successMsg)): ?>
    <div style="background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; padding: 12px 16px; border-radius: 12px; font-size: 0.85rem; font-weight: 700; margin-bottom: 1.25rem;">
        <?= View::escape($successMsg) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div style="background: #ffe4e6; border: 1px solid #fecdd3; color: #be123c; padding: 12px 16px; border-radius: 12px; font-size: 0.85rem; font-weight: 700; margin-bottom: 1.25rem;">
        <?= View::escape($errorMsg) ?>
    </div>
<?php endif; ?>

<!-- Summary Statistics Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
    <!-- Active Vouchers -->
    <div style="background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 14px; padding: 1.1rem 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Active Vouchers</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #166534; margin-top: 4px;"><?= number_format((int)($stats['active_vouchers'] ?? 0)) ?></div>
            <div style="font-size: 0.72rem; font-weight: 700; font-family: ui-monospace, monospace; color: #16a34a; margin-top: 2px;">
                LKR <?= number_format((float)($stats['active_amount'] ?? 0), 2) ?>
            </div>
        </div>
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="7" y2="16"/><line x1="11" y1="8" x2="11" y2="16"/><line x1="15" y1="8" x2="17" y2="16"/></svg>
        </div>
    </div>

    <!-- Redeemed Vouchers -->
    <div style="background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 14px; padding: 1.1rem 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Redeemed Vouchers</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #0284c7; margin-top: 4px;"><?= number_format((int)($stats['redeemed_vouchers'] ?? 0)) ?></div>
            <div style="font-size: 0.72rem; color: #94a3b8; margin-top: 2px;">Settled count</div>
        </div>
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #f0f9ff; color: #0284c7; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
    </div>

    <!-- Expired / Cancelled -->
    <div style="background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 14px; padding: 1.1rem 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Expired / Cancelled</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #b45309; margin-top: 4px;"><?= number_format((int)($stats['expired_vouchers'] ?? 0)) ?></div>
            <div style="font-size: 0.72rem; color: #94a3b8; margin-top: 2px;">Inactive count</div>
        </div>
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #fffbeb; color: #b45309; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
    </div>

    <!-- Total Issued -->
    <div style="background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 14px; padding: 1.1rem 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Total Issued</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-top: 4px;"><?= number_format((int)($stats['total_vouchers'] ?? 0)) ?></div>
            <div style="font-size: 0.72rem; color: #94a3b8; margin-top: 2px;">Lifetime generated</div>
        </div>
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #f8fafc; color: #0f172a; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="inv-card">
    <div class="inv-table-wrap">
        <table class="inv-table" id="vouchersTable">
            <thead>
                <tr>
                    <th>Voucher Code</th>
                    <th>Amount</th>
                    <th>Issue Date</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Issued By</th>
                    <th>Redeemed Invoice</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vouchers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                            No gift vouchers found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vouchers as $v): ?>
                        <?php
                        $st = strtolower($v['status']);
                        $badgeClass = 'v-status-' . $st;
                        $searchData = strtolower($v['voucher_code'] . ' ' . $v['creator_name'] . ' ' . ($v['redeemed_invoice_no'] ?? '') . ' ' . ($v['notes'] ?? ''));
                        ?>
                        <tr class="inv-row" data-search="<?= View::escape($searchData) ?>" onclick="openVoucherDrawer(<?= htmlspecialchars(json_encode($v)) ?>)">
                            <td>
                                <span class="inv-code-pill" style="color: #0284c7; background: #e0f2fe; border-color: #bae6fd; font-size: 0.8rem; padding: 4px 10px;">
                                    <?= View::escape($v['voucher_code']) ?>
                                </span>
                            </td>
                            <td style="font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; font-size: 0.9rem;">
                                LKR <?= number_format((float)$v['amount'], 2) ?>
                            </td>
                            <td style="color: #475569; font-weight: 600;"><?= View::escape($v['issue_date']) ?></td>
                            <td style="color: #475569; font-weight: 600;">
                                <?= View::escape($v['expiry_date']) ?>
                                <?php if ($st === 'active' && strtotime($v['expiry_date']) <= strtotime('+7 days')): ?>
                                    <span class="inv-code-pill" style="font-size: 0.62rem; color: #b45309; background: #fef3c7; border-color: #fde68a; margin-left: 4px;">Expiring soon</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="voucher-status-badge <?= $badgeClass ?>"><?= ucfirst($st) ?></span>
                            </td>
                            <td style="font-weight: 600; color: #334155;"><?= View::escape($v['creator_name']) ?></td>
                            <td>
                                <?php if (!empty($v['redeemed_invoice_no'])): ?>
                                    <span class="inv-code-pill" style="color: #059669; background: #d1fae5; border-color: #a7f3d0;">#<?= View::escape($v['redeemed_invoice_no']) ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;" onclick="event.stopPropagation()">
                                <button type="button" class="inv-btn inv-btn-ghost" style="padding: 5px 12px; font-size: 11px; gap: 4px;" onclick="openVoucherDrawer(<?= htmlspecialchars(json_encode($v)) ?>)">
                                    <span>Details</span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="inv-pagination">
            <div style="font-size: 0.78rem; color: #64748b; font-weight: 500;">
                Showing page <strong style="color: #0f172a;"><?= $page ?></strong> of <strong style="color: #0f172a;"><?= $totalPages ?></strong> (Total <?= $totalItems ?> vouchers)
            </div>
            <div style="display: flex; gap: 8px;">
                <?php if ($page > 1): ?>
                    <a href="vouchers.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="page-pill">
                        ‹ Previous
                    </a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="vouchers.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="page-pill">
                        Next ›
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Slide-in Voucher Preview & Details Side Panel Drawer -->
<div class="inv-drawer-backdrop" id="voucherDrawerBackdrop" onclick="closeVoucherDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerVoucherCode" style="font-size: 0.85rem; color: #0284c7; background: #e0f2fe; border-color: #bae6fd;"></span>
                <span id="drawerVoucherStatusBadge" style="margin-left: 6px;"></span>
            </div>
            <button type="button" class="inv-drawer-close" onclick="closeVoucherDrawer()" title="Close side panel">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="inv-drawer-body">
            <!-- Visual Voucher Preview Card -->
            <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; padding: 20px; color: #ffffff; box-shadow: 0 10px 25px rgba(15,23,42,0.3); position: relative; overflow: hidden;">
                <div style="position: absolute; right: -20px; bottom: -20px; opacity: 0.08; color: #ffffff;">
                    <svg width="180" height="180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="7" y2="16"/><line x1="11" y1="8" x2="11" y2="16"/><line x1="15" y1="8" x2="17" y2="16"/></svg>
                </div>
                <div style="font-size: 0.68rem; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.1em;">CURTISS POS GIFT VOUCHER</div>
                <div id="drawerPreviewCode" style="font-size: 1.25rem; font-weight: 800; font-family: ui-monospace, monospace; margin-top: 6px; letter-spacing: 0.05em; color: #ffffff;"></div>
                <div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <div style="font-size: 0.65rem; color: #94a3b8; text-transform: uppercase;">VALUE</div>
                        <div id="drawerPreviewAmount" style="font-size: 1.5rem; font-weight: 800; font-family: ui-monospace, monospace; color: #4ade80;"></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.65rem; color: #94a3b8; text-transform: uppercase;">VALID UNTIL</div>
                        <div id="drawerPreviewExpiry" style="font-size: 0.88rem; font-weight: 700; color: #e2e8f0;"></div>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">ISSUE DATE</span>
                    <div id="drawerIssueDate" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">ISSUED BY</span>
                    <div id="drawerCreatorName" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">NOTES / OCCASION</span>
                <div id="drawerNotes" style="font-size: 0.85rem; color: #334155; margin-top: 2px;"></div>
            </div>

            <!-- Redemption Info Box -->
            <div id="drawerRedemptionBox" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; display: none;">
                <span style="font-size: 0.68rem; font-weight: 800; color: #166534; text-transform: uppercase;">REDEMPTION INFO</span>
                <div style="font-size: 0.88rem; font-weight: 700; color: #14532d; margin-top: 2px;">
                    Redeemed on Invoice: <span id="drawerRedeemedInvoice" style="font-family: monospace;"></span>
                </div>
            </div>

            <!-- Drawer Actions -->
            <div id="drawerActionContainer" style="margin-top: 6px;"></div>
        </div>
        <div class="inv-drawer-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeVoucherDrawer()">Close Panel</button>
        </div>
    </div>
</div>

<!-- Modal: Issue New Gift Voucher -->
<div class="inv-modal-backdrop" id="issueVoucherModal">
    <div class="inv-modal-box">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Issue New Gift Voucher</h3>
            <button type="button" class="inv-drawer-close" onclick="closeIssueVoucherModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="vouchers.php" method="POST">
            <input type="hidden" name="action" value="create_voucher">
            <div class="inv-modal-body">
                <div class="inv-field">
                    <label class="inv-label">Voucher Value (LKR) *</label>
                    <input type="number" step="100" class="inv-input" name="amount" placeholder="e.g. 1000.00" style="font-size: 1rem; font-weight: 800; font-family: ui-monospace, monospace; color: #166534;" required>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Expiry Date *</label>
                    <input type="date" class="inv-input" name="expiry_date" value="<?= date('Y-m-d', strtotime('+3 months')) ?>" required>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Notes / Occasion (Optional)</label>
                    <input type="text" class="inv-input" name="notes" placeholder="e.g. Birthday gift, promotion, loyalty reward...">
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeIssueVoucherModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary">Generate Gift Voucher</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
window.CAN_MANAGE_VOUCHERS = <?= $canManage ? 'true' : 'false' ?>;

document.addEventListener('DOMContentLoaded', () => {
    // Register Real-time Search Hook on Floating Bottom Nav Bar
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search Voucher Code, Issuer, Invoice, Notes...',
                onSearch: function(query) {
                    filterVouchersTableRealtime(query);
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

function filterVouchersTableRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#vouchersTable tbody tr.inv-row');

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        if (!q || searchAttr.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}

function openIssueVoucherModal() {
    document.getElementById('issueVoucherModal').classList.add('active');
}

function closeIssueVoucherModal() {
    document.getElementById('issueVoucherModal').classList.remove('active');
}

function openVoucherDrawer(voucher) {
    const backdrop = document.getElementById('voucherDrawerBackdrop');
    backdrop.classList.add('active');

    const st = (voucher.status || 'active').toLowerCase();

    document.getElementById('drawerVoucherCode').textContent = voucher.voucher_code || 'N/A';
    document.getElementById('drawerPreviewCode').textContent = voucher.voucher_code || 'N/A';
    document.getElementById('drawerPreviewAmount').textContent = 'LKR ' + parseFloat(voucher.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('drawerPreviewExpiry').textContent = voucher.expiry_date || 'N/A';

    document.getElementById('drawerVoucherStatusBadge').className = 'voucher-status-badge v-status-' + st;
    document.getElementById('drawerVoucherStatusBadge').textContent = st.toUpperCase();

    document.getElementById('drawerIssueDate').textContent = voucher.issue_date || 'N/A';
    document.getElementById('drawerCreatorName').textContent = voucher.creator_name || 'System';
    document.getElementById('drawerNotes').textContent = voucher.notes || 'No notes provided.';

    const redemptionBox = document.getElementById('drawerRedemptionBox');
    if (voucher.redeemed_invoice_no) {
        document.getElementById('drawerRedeemedInvoice').textContent = '#' + voucher.redeemed_invoice_no;
        redemptionBox.style.display = 'block';
    } else {
        redemptionBox.style.display = 'none';
    }

    const actionContainer = document.getElementById('drawerActionContainer');
    if (st === 'active' && window.CAN_MANAGE_VOUCHERS) {
        actionContainer.innerHTML = `
            <form action="vouchers.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this gift voucher?');">
                <input type="hidden" name="action" value="cancel_voucher">
                <input type="hidden" name="id" value="${voucher.id}">
                <button type="submit" class="inv-btn inv-btn-danger" style="width: 100%; justify-content: center; font-size: 12px; padding: 8px;">
                    Cancel Voucher
                </button>
            </form>
        `;
    } else {
        actionContainer.innerHTML = '';
    }
}

function closeVoucherDrawer() {
    document.getElementById('voucherDrawerBackdrop').classList.remove('active');
}
</script>
