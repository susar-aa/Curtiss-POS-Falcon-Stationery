<?php
/**
 * Curtiss POS - Customer List View (Curtiss Master Rules Enforced)
 * Fixed viewport height calc(100vh - 110px), internal scrolling table (.prod-table-scroll),
 * 48px row heights, sticky uppercase headers, alternating zebra striping,
 * clickable theme-matched status pills, full-row click for details drawer,
 * floating top-right header action bar (#curtiss-floating-header-right),
 * Trash Bin & soft-delete restore support, and universal realtime search.
 */
$canManage = Auth::hasPermission('customers.manage') && !Auth::isSpecialUser();
$canChangeStatus = Auth::hasPermission('customers.status') && !Auth::isSpecialUser();
$canPay = Auth::hasPermission('customers.pay') && !Auth::isSpecialUser();
$canDelete = Auth::hasPermission('customers.delete') && !Auth::isSpecialUser();
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header Container -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <!-- Status Filter Form -->
    <form method="GET" action="customers.php" id="headerCustomerFilterForm" style="margin: 0; padding: 0; display: flex; align-items: center; gap: 8px;">
        <?php if (!empty($view)): ?>
            <input type="hidden" name="view" value="<?= View::escape($view) ?>">
        <?php endif; ?>
        <?php if (!empty($limit)): ?>
            <input type="hidden" name="limit" value="<?= (int)$limit ?>">
        <?php endif; ?>
        <select name="status" id="headerCustomerStatusSelect" class="curtiss-select-enhance" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.96); border: 1px solid rgba(226,232,240,0.9); color: #0f172a; min-width: 150px;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
    </form>

    <?php if ($canManage): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateCustomerModal()" style="height: 34px; min-height: 34px; line-height: 32px; padding: 0 14px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Add Customer</span>
    </button>
    <?php endif; ?>

    <a href="customer_export.php" class="inv-btn inv-btn-ghost" title="Export Customers CSV" style="height: 34px; min-height: 34px; line-height: 32px; padding: 0 12px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Export</span>
    </a>

    <?php if ($view === 'deleted'): ?>
        <a href="customers.php" class="inv-btn inv-btn-ghost" style="height: 34px; min-height: 34px; line-height: 32px; padding: 0 12px; background: #ffffff; color: #0f172a; border-color: #cbd5e1;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
            <span>Active Customers</span>
        </a>
    <?php else: ?>
        <a href="customers.php?view=deleted" class="inv-btn" style="height: 34px; min-height: 34px; line-height: 32px; padding: 0 12px; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;" title="View Trash Bin">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            <span>Trash Bin</span>
        </a>
    <?php endif; ?>
</div>

<style>
/* ── Master Theme & Typography ── */
.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
}

.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 14px;
    height: 34px;
    min-height: 34px;
    line-height: 32px;
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
    background: rgba(255,255,255,0.9);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.inv-btn-ghost:hover {
    background: #ffffff;
    border-color: #cbd5e1;
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

/* ── Standard 48px Table & Zebra Striping ── */
.prod-table-panel {
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 14px;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
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
    position: sticky;
    top: 0;
    z-index: 10;
    text-align: left;
    white-space: nowrap;
}
.prod-table thead th.col-right { text-align: right; }
.prod-table thead th.col-center { text-align: center; }

.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.15s ease;
}
.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:hover            { background: #f1f5f9 !important; }

.prod-table td {
    height: 48px;
    padding: 0 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
    box-sizing: border-box;
    border-bottom: 1px solid #f1f5f9;
}
.prod-table td.col-right { text-align: right; }
.prod-table td.col-center { text-align: center; }

/* ── In-Cell Status Badges & Action Buttons (28px) ── */
.prod-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    border: 1px solid transparent;
    box-sizing: border-box;
    text-transform: uppercase;
}
.prod-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.prod-badge-active {
    background: #dcfce7;
    color: #15803d;
    border-color: #bbf7d0;
}
.prod-badge-active .dot {
    background: #16a34a;
}
.prod-badge-inactive {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #fecdd3;
}
.prod-badge-inactive .dot {
    background: #dc2626;
}

.prod-act-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.inv-code-pill {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 800;
    background: #f1f5f9;
    color: #0f172a;
    padding: 2px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

/* ── Pagination Footer ── */
.prod-table-footer {
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: 12px;
    flex-wrap: wrap;
    gap: 8px;
}
.page-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0 12px;
    height: 28px;
    line-height: 26px;
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
    background: #f1f5f9;
    border-color: #94a3b8;
}

/* ── Drawers (480px width) ── */
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
    padding: 1.1rem 1.4rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}
.inv-drawer-body {
    padding: 1.4rem;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}
.inv-drawer-footer {
    padding: 0.85rem 1.4rem;
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

/* ── Modals ── */
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
    padding: 16px;
    box-sizing: border-box;
}
.inv-modal-backdrop.active {
    display: flex;
}
.inv-modal-box {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0,0,0,0.18);
    overflow: hidden;
    animation: modalSlideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.inv-modal-header {
    padding: 1.1rem 1.4rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
.inv-modal-body {
    padding: 1.25rem 1.4rem;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}
.inv-modal-footer {
    padding: 0.85rem 1.4rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.inv-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.inv-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.inv-input {
    width: 100%;
    height: 36px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0 12px;
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

<!-- Fixed Viewport Workspace -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title" style="margin: 0;"><?= $view === 'deleted' ? 'Customers - Trash Bin' : 'Customers & Customer Credit' ?></h1>
        </div>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div style="flex-shrink: 0; background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; padding: 10px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; margin-bottom: 0.65rem;">
            <?= View::escape($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div style="flex-shrink: 0; background: #ffe4e6; border: 1px solid #fecdd3; color: #be123c; padding: 10px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; margin-bottom: 0.65rem;">
            <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Summary Statistics Cards Grid (Fixed Shrink) -->
    <div style="flex-shrink: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 0.75rem;">
        <!-- Total Customers -->
        <div style="background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 12px; padding: 0.9rem 1.1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Total Customers</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin-top: 2px;"><?= number_format((int)($stats['total_customers'] ?? 0)) ?></div>
            </div>
            <div style="width: 38px; height: 38px; border-radius: 8px; background: #f0f9ff; color: #0284c7; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
        </div>

        <!-- Active Accounts -->
        <div style="background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 12px; padding: 0.9rem 1.1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Active Accounts</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #166534; margin-top: 2px;"><?= number_format((int)($stats['active_customers'] ?? 0)) ?></div>
            </div>
            <div style="width: 38px; height: 38px; border-radius: 8px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
        </div>

        <!-- Total Outstanding Credit -->
        <div style="background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 12px; padding: 0.9rem 1.1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Total Credit Balance</div>
                <div style="font-size: 1.25rem; font-weight: 800; font-family: ui-monospace, monospace; color: <?= (is_numeric($stats['total_outstanding'] ?? 0) && (float)$stats['total_outstanding'] > 0) ? '#dc2626' : '#166534' ?>; margin-top: 2px;">
                    LKR <?= is_numeric($stats['total_outstanding'] ?? 0) ? number_format((float)$stats['total_outstanding'], 2) : View::escape($stats['total_outstanding'] ?? '0.00') ?>
                </div>
            </div>
            <div style="width: 38px; height: 38px; border-radius: 8px; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
        </div>

        <!-- Loyalty Card Holders -->
        <div style="background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 12px; padding: 0.9rem 1.1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Card Barcode Holders</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin-top: 2px;"><?= number_format((int)($stats['card_holders'] ?? 0)) ?></div>
            </div>
            <div style="width: 38px; height: 38px; border-radius: 8px; background: #faf5ff; color: #9333ea; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="8" x2="7" y2="16"/><line x1="11" y1="8" x2="11" y2="16"/><line x1="15" y1="8" x2="17" y2="16"/></svg>
            </div>
        </div>
    </div>

    <!-- Main Table Panel & Internal Scroll (Flex 1) -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="customersTable">
                <thead>
                    <tr>
                        <th style="width: 140px;">Customer Code / ID</th>
                        <th>Customer Name</th>
                        <th style="width: 160px;">Phone / WhatsApp</th>
                        <th style="width: 130px;">Card Barcode</th>
                        <th class="col-right" style="width: 130px;">Credit Limit</th>
                        <th class="col-right" style="width: 150px;">Outstanding (LKR)</th>
                        <th class="col-center" style="width: 110px;">Status</th>
                        <th class="col-right" style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                                No customers found matching the search criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <?php
                            $bal = is_string($c['credit_balance']) ? $c['credit_balance'] : (float)$c['credit_balance'];
                            $isWalkin = !empty($c['is_walkin']);
                            $isNumBal = is_numeric($c['credit_balance']);
                            $balVal = $isNumBal ? (float)$c['credit_balance'] : 0;
                            $balColor = ($isNumBal && $balVal > 0) ? '#dc2626' : '#166534';
                            $st = strtolower($c['status'] ?? 'active');

                            $hasLimit = ($c['credit_limit'] !== null && $c['credit_limit'] !== '');
                            $rawLimit = $hasLimit ? (float)$c['credit_limit'] : null;
                            $formattedLimit = $hasLimit ? number_format((float)$c['credit_limit'], 2) : 'No Limit';
                            $formattedBal = is_numeric($bal) ? number_format((float)$bal, 2) : $bal;

                            $searchData = strtolower(implode(' ', [
                                $c['customer_code'] ?? '',
                                '#' . $c['id'],
                                $c['id'],
                                $c['name'] ?? '',
                                $c['phone'] ?? '',
                                $c['whatsapp'] ?? '',
                                $c['card_barcode'] ?? '',
                                $st,
                                (string)($rawLimit ?? ''),
                                (string)$formattedLimit,
                                $hasLimit ? 'LKR ' . $formattedLimit : 'no limit unlimited',
                                (string)$balVal,
                                (string)$formattedBal,
                                'LKR ' . $formattedBal
                            ]));
                            ?>
                            <tr class="inv-row" data-cust-id="<?= (int)$c['id'] ?>" data-search="<?= View::escape($searchData) ?>" onclick="openCustomerDetailDrawer(<?= (int)$c['id'] ?>)" style="cursor: pointer;">
                                <td>
                                    <span class="inv-code-pill" style="color: #0284c7; background: #e0f2fe; border-color: #bae6fd;"><?= View::escape($c['customer_code']) ?></span>
                                    <span style="font-size: 10.5px; color: #94a3b8; margin-left: 4px;">#<?= (int)$c['id'] ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 12.5px; display: flex; align-items: center; flex-wrap: wrap; gap: 4px;">
                                        <?= View::escape($c['name']) ?>
                                        <?php if (!empty($c['is_loyalty'])): ?>
                                            <span class="inv-code-pill" style="font-size: 10px; color: #7c3aed; background: #f5f3ff; border-color: #ddd6fe; font-weight: 800; padding: 1px 6px;" title="Loyalty Member">
                                                ★ Loyalty<?= !empty($c['loyalty_discount_override']) ? ' (' . number_format((float)$c['loyalty_discount_override'], 1) . '%)' : '' ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($isWalkin): ?>
                                            <span class="inv-code-pill" style="font-size: 10px; color: #0369a1; background: #e0f2fe; border-color: #bae6fd;">Default Walk-in</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #334155; font-size: 12px;"><?= View::escape($c['phone'] ?? 'N/A') ?></div>
                                    <?php if (!empty($c['whatsapp'])): ?>
                                        <div style="font-size: 10.5px; font-weight: 700; color: #16a34a;">WA: <?= View::escape($c['whatsapp']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['card_barcode'])): ?>
                                        <span class="inv-code-pill"><?= View::escape($c['card_barcode']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 11px;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" style="font-weight: 700; font-family: ui-monospace, monospace; color: #475569;">
                                    <?php if ($c['credit_limit'] !== null && $c['credit_limit'] !== ''): ?>
                                        <?= number_format((float)$c['credit_limit'], 2) ?>
                                    <?php else: ?>
                                        <span class="inv-code-pill" style="background: #f1f5f9; color: #64748b; font-weight: 700; font-size: 10.5px;">No Limit</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" style="font-weight: 800; font-family: ui-monospace, monospace; color: <?= $balColor ?>;">
                                    <?= is_numeric($bal) ? number_format((float)$bal, 2) : View::escape($bal) ?>
                                </td>
                                <td class="col-center">
                                    <?php if ($view === 'deleted'): ?>
                                        <span class="prod-badge" style="background: #fee2e2; color: #b91c1c; border-color: #fecdd3;"><span class="dot" style="background: #dc2626;"></span>Deleted</span>
                                    <?php elseif ($st === 'active'): ?>
                                        <span class="prod-badge prod-badge-active" <?= ($canChangeStatus && !$isWalkin) ? 'onclick="event.stopPropagation(); toggleCustomerStatus(' . (int)$c['id'] . ');"' : '' ?> style="cursor: <?= ($canChangeStatus && !$isWalkin) ? 'pointer' : 'default' ?>;" title="<?= ($canChangeStatus && !$isWalkin) ? 'Click to toggle status' : '' ?>"><span class="dot"></span>Active</span>
                                    <?php else: ?>
                                        <span class="prod-badge prod-badge-inactive" <?= ($canChangeStatus && !$isWalkin) ? 'onclick="event.stopPropagation(); toggleCustomerStatus(' . (int)$c['id'] . ');"' : '' ?> style="cursor: <?= ($canChangeStatus && !$isWalkin) ? 'pointer' : 'default' ?>;" title="<?= ($canChangeStatus && !$isWalkin) ? 'Click to toggle status' : '' ?>"><span class="dot"></span>Deactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation()">
                                    <?php if ($view === 'deleted'): ?>
                                        <button type="button" class="prod-act-btn" onclick="restoreCustomer(<?= (int)$c['id'] ?>)" style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;">
                                            Restore
                                        </button>
                                    <?php else: ?>
                                        <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 4px;">
                                            <?php if ($balVal > 0 && $canPay): ?>
                                                <button type="button" class="prod-act-btn inv-btn-success" onclick="openCollectPaymentModal(<?= (int)$c['id'] ?>)" title="Collect Outstanding Payment">
                                                    Collect
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($canDelete && !$isWalkin): ?>
                                                <button type="button" class="prod-act-btn" onclick="deleteCustomer(<?= (int)$c['id'] ?>)" style="color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;" title="Move to Trash Bin">
                                                    Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="prod-table-footer">
            <div style="color: #64748b; font-weight: 600;">
                <?php
                $startItem = $totalItems > 0 ? (($page - 1) * $limit) + 1 : 0;
                $endItem = min($totalItems, $page * $limit);
                ?>
                Showing <strong style="color: #0f172a;"><?= $startItem ?></strong> to <strong style="color: #0f172a;"><?= $endItem ?></strong> of <strong style="color: #0f172a;"><?= number_format($totalItems) ?></strong> entries
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="color: #64748b; font-weight: 700; font-size: 11px; text-transform: uppercase;">Per page:</span>
                    <select id="custLimitSelect" onchange="changeCustomerPageSize(this.value)" style="height: 28px; padding: 0 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; color: #0f172a; background: #ffffff;">
                        <?php foreach ([25, 50, 75, 100, 500] as $lim): ?>
                            <option value="<?= $lim ?>" <?= ($limit === $lim) ? 'selected' : '' ?>><?= $lim ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 6px;">
                    <?php if ($page > 1): ?>
                        <a href="customers.php?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&view=<?= urlencode($view) ?>" class="page-pill">‹ Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="customers.php?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&view=<?= urlencode($view) ?>" class="page-pill">Next ›</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PANEL 1: Slide-in Customer Profile Side Panel Drawer -->
<div class="inv-drawer-backdrop" id="customerDrawerBackdrop" onclick="closeCustomerDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span class="inv-code-pill" id="drawerCustCode" style="font-size: 0.85rem; color: #0284c7; background: #e0f2fe; border-color: #bae6fd;"></span>
                <span id="drawerCustStatusBadge" style="margin-left: 6px;"></span>
            </div>
            <button type="button" class="inv-drawer-close" onclick="closeCustomerDetailDrawer()" title="Close side panel">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="inv-drawer-body">
            <!-- Customer Name & Header -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <span style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase;">CUSTOMER NAME</span>
                <div id="drawerCustName" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                <div id="drawerWalkinBadge" style="margin-top: 4px;"></div>
            </div>

            <!-- Customer Actions Side Panel Box (Transaction History, Collect Payment, Edit, Deactivate/Activate) -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;" id="drawerQuickActions">
                <!-- Dynamically rendered via JS -->
            </div>

            <!-- Financial Summary Box -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">CREDIT LIMIT</span>
                    <div id="drawerCreditLimit" style="font-size: 1.05rem; font-weight: 800; font-family: ui-monospace, monospace; color: #475569; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">OUTSTANDING BALANCE</span>
                    <div id="drawerOutstandingBalance" style="font-size: 1.05rem; font-weight: 800; font-family: ui-monospace, monospace; margin-top: 2px;"></div>
                    <div id="drawerPayTriggerContainer" style="margin-top: 6px;"></div>
                </div>
            </div>

            <!-- Contact Information -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">PHONE NUMBER</span>
                    <div id="drawerPhone" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">WHATSAPP</span>
                    <div id="drawerWhatsApp" style="font-size: 0.88rem; font-weight: 700; color: #16a34a; margin-top: 2px;"></div>
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">CARD BARCODE</span>
                <div id="drawerCardBarcode" style="font-size: 0.88rem; font-weight: 700; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;"></div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px;">
                <span style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase;">ADDRESS</span>
                <div id="drawerAddress" style="font-size: 0.85rem; color: #334155; margin-top: 2px;"></div>
            </div>

            <!-- Recent Transactions Preview -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <h4 style="margin: 0; font-size: 0.82rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.03em;">Recent Activity</h4>
                    <span id="drawerHistoryLinkContainer"></span>
                </div>
                <div id="drawerActivityContainer" style="display: flex; flex-direction: column; gap: 8px;">
                    <!-- Rendered via JS -->
                </div>
            </div>
        </div>
        <div class="inv-drawer-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeCustomerDetailDrawer()">Close Panel</button>
        </div>
    </div>
</div>

<!-- PANEL 2: Slide-in Transaction History Side Panel Drawer (Same 480px width) -->
<div class="inv-drawer-backdrop" id="txHistoryDrawerBackdrop" style="z-index: 2010;" onclick="closeTxHistoryDrawer()">
    <div class="inv-drawer" style="z-index: 2011;" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <span style="font-size: 0.68rem; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 0.04em;">TRANSACTION HISTORY</span>
                <div id="txDrawerCustTitle" style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin-top: 2px;">Customer Transactions</div>
            </div>
            <button type="button" class="inv-drawer-close" onclick="closeTxHistoryDrawer()" title="Close panel">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="inv-drawer-body">
            <!-- Filter Tabs -->
            <div style="display: flex; gap: 6px; background: #f1f5f9; padding: 4px; border-radius: 10px;">
                <button type="button" id="txTabAll" class="inv-btn inv-btn-ghost" style="flex:1; justify-content:center; padding: 0 8px; font-size: 11px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" onclick="switchTxTab('all')">
                    All Activity
                </button>
                <button type="button" id="txTabSales" class="inv-btn inv-btn-ghost" style="flex:1; justify-content:center; padding: 0 8px; font-size: 11px; border-color: transparent; background: transparent;" onclick="switchTxTab('sales')">
                    Sales Invoices
                </button>
                <button type="button" id="txTabLedger" class="inv-btn inv-btn-ghost" style="flex:1; justify-content:center; padding: 0 8px; font-size: 11px; border-color: transparent; background: transparent;" onclick="switchTxTab('ledger')">
                    Credit Ledger
                </button>
            </div>

            <!-- Transaction List Timeline Container -->
            <div id="txHistoryTimelineBody" style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Populated via JS -->
            </div>
        </div>
        <div class="inv-drawer-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeTxHistoryDrawer()">Close Panel</button>
        </div>
    </div>
</div>

<!-- Modal: Collect Customer Payment -->
<div class="inv-modal-backdrop" id="collectPaymentModal" style="z-index: 2200;">
    <div class="inv-modal-box" style="max-width: 840px; width: 95%;">
        <div class="inv-modal-header" style="background: #0f172a; color: #ffffff; flex-shrink: 0;">
            <div>
                <span style="font-size: 0.68rem; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.05em;">CUSTOMER CREDIT COLLECTION</span>
                <h3 style="margin: 2px 0 0 0; font-size: 1.1rem; font-weight: 800; color: #ffffff;" id="payModalCustTitle">Collect Payment</h3>
            </div>
            <button type="button" class="inv-drawer-close" onclick="closeCollectPaymentModal()" style="color: #cbd5e1;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="collectPaymentForm" onsubmit="handleCollectPaymentSubmit(event)" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; margin: 0;">
            <input type="hidden" id="payCustomerId" name="customer_id" value="">
            <div class="inv-modal-body" style="padding: 1.15rem 1.4rem; display: flex; flex-direction: column; gap: 12px; flex: 1; min-height: 0; overflow-y: auto;">
                
                <!-- Top Summary: Current Outstanding, Total Collection Input, Remaining Credit -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
                    <!-- Outstanding Summary Badge -->
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 10px 14px; display: flex; flex-direction: column; justify-content: center;">
                        <span style="font-size: 0.68rem; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.04em;">Current Outstanding</span>
                        <div id="payModalOutstandingVal" style="font-size: 1.35rem; font-weight: 800; font-family: ui-monospace, monospace; color: #dc2626; margin-top: 2px;">LKR 0.00</div>
                        <div id="payModalLimitInfo" style="font-size: 0.72rem; color: #64748b; margin-top: 2px;">Credit Limit: LKR 0.00 | Available: LKR 0.00</div>
                    </div>

                    <!-- Collection Total Amount Input -->
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 10px 14px; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label class="inv-label" style="font-size: 0.68rem; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0;">Paying Now (LKR) *</label>
                            <button type="button" onclick="setCustomerPayToFull()" style="background: #dcfce7; border: 1px solid #86efac; color: #15803d; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 999px; cursor: pointer;" title="Set paying amount to full balance">
                                Pay Full
                            </button>
                        </div>
                        <input type="number" step="0.01" min="0.01" id="payAmountInput" class="inv-input" oninput="onCustomerTotalAmountChanged(this.value)" style="font-size: 1.25rem; font-weight: 800; font-family: ui-monospace, monospace; color: #14532d; background: #ffffff; border-color: #86efac; margin-top: 4px; height: 38px;" placeholder="0.00" required>
                        <div style="font-size: 0.70rem; color: #15803d; margin-top: 2px;">Full or partial payment allowed</div>
                    </div>

                    <!-- Remaining Balance (Kept as Credit) -->
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 10px 14px; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.68rem; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.04em;">Remaining Balance</span>
                            <span id="payModalRemainingBadge" class="prod-badge" style="background: #dbeafe; color: #1e40af; font-size: 9.5px; padding: 2px 6px;">Kept as Credit</span>
                        </div>
                        <div id="payModalRemainingCreditVal" style="font-size: 1.35rem; font-weight: 800; font-family: ui-monospace, monospace; color: #2563eb; margin-top: 2px;">LKR 0.00</div>
                        <div style="font-size: 0.72rem; color: #64748b; margin-top: 2px;">Debt balance after this payment</div>
                    </div>
                </div>

                <!-- Collapsible Unpaid Invoices Drawer / Accordion -->
                <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #ffffff;">
                    <div onclick="toggleCustomerInvoicesPreview()" style="display: flex; justify-content: space-between; align-items: center; padding: 7px 12px; background: #f8fafc; cursor: pointer; user-select: none;">
                        <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.04em;">
                            Unpaid Credit Invoices (<span id="payModalInvoicesCount">0</span>)
                        </span>
                        <span id="payModalInvoicesToggleText" style="font-size: 11px; font-weight: 700; color: #0284c7;">
                            Show Invoices ▼
                        </span>
                    </div>
                    <div id="payModalInvoicesContainer" style="display: none; max-height: 130px; overflow-y: auto; padding: 4px 8px; background: #ffffff; border-top: 1px solid #f1f5f9;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid #e2e8f0; text-align: left; color: #64748b; font-size: 0.68rem; text-transform: uppercase;">
                                    <th style="padding: 4px 6px;">Invoice No</th>
                                    <th style="padding: 4px 6px;">Date</th>
                                    <th style="padding: 4px 6px;">Total</th>
                                    <th style="padding: 4px 6px; text-align: right;">Due Amount</th>
                                </tr>
                            </thead>
                            <tbody id="payModalUnpaidInvoicesTbody">
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 1rem;">No unpaid invoices</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Split Payments Section -->
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #334155;">
                            Payment Account Allocation (Multi-Account / Splits)
                        </span>
                        <button type="button" class="inv-btn" style="height: 28px; padding: 0 10px; font-size: 11px; font-weight: 700; color: #0284c7; background: #e0f2fe; border-color: #bae6fd;" onclick="addCustomerSplitRow()">
                            + Add Account Split
                        </button>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;" id="customerSplitsTable">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; height: 34px; color: #64748b; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">
                                    <th style="padding: 0 10px; text-align: left;">Payment Account / Method</th>
                                    <th style="padding: 0 10px; text-align: right; width: 130px;">Amount (LKR)</th>
                                    <th style="padding: 0 10px; text-align: left; width: 140px;">Cheque / Ref / Voucher</th>
                                    <th style="padding: 0 10px; text-align: left; width: 130px;">Cheque Bank</th>
                                    <th style="padding: 0 10px; text-align: left; width: 120px;">Banking Date</th>
                                    <th style="padding: 0 8px; text-align: center; width: 36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="customerSplitsBody">
                                <!-- Dynamic rows generated by JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Allocation Balance Bar -->
                    <div id="customerSplitSummaryBar" style="display: flex; justify-content: space-between; align-items: center; padding: 7px 12px; background: #f1f5f9; border-radius: 8px; font-size: 12px; font-weight: 700;">
                        <span id="customerSplitAllocatedText">Allocated: LKR 0.00 / Total: LKR 0.00</span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button id="syncTotalToSplitsBtn" type="button" onclick="syncCustomerTotalToSplits()" style="display: none; background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; cursor: pointer;">
                                Set Total to Splits
                            </button>
                            <span id="customerSplitDifferenceBadge" class="prod-badge prod-badge-active" style="background: #dcfce7; color: #15803d; font-size: 11px; padding: 3px 10px;">✓ Balanced (100%)</span>
                        </div>
                    </div>
                </div>

                <!-- Remarks / Notes -->
                <div class="inv-field" style="margin-top: -2px;">
                    <label class="inv-label" style="font-size: 11px; margin-bottom: 2px;">Remarks / Notes</label>
                    <input type="text" id="payNotesInput" class="inv-input" placeholder="Optional collection notes (e.g. Received by Susara, Cheque banked)..." style="height: 34px; font-size: 12px;">
                </div>

            </div>

            <!-- Sticky Modal Footer -->
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeCollectPaymentModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-success" id="submitPaymentBtn" style="padding: 0 20px; font-size: 12px; font-weight: 700;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Process Collection Payment</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add New Customer -->
<div class="inv-modal-backdrop" id="createCustomerModal">
    <div class="inv-modal-box">
        <div class="inv-modal-header">
            <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Add New Customer</h3>
            <button type="button" class="inv-drawer-close" onclick="closeCreateCustomerModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="createCustomerForm" onsubmit="handleCreateCustomerSubmit(event)">
            <div class="inv-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="inv-field">
                        <label class="inv-label">Customer Name *</label>
                        <input type="text" class="inv-input" name="name" placeholder="e.g. John Doe / ABC Traders" required>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">Customer Code (Optional)</label>
                        <input type="text" class="inv-input" name="customer_code" placeholder="Auto-generated if empty">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="inv-field">
                        <label class="inv-label">Phone Number *</label>
                        <input type="text" class="inv-input" name="phone" placeholder="0771234567" required>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">WhatsApp Number</label>
                        <input type="text" class="inv-input" name="whatsapp" placeholder="0771234567">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="inv-field">
                        <label class="inv-label">Card Barcode (Scan / Enter)</label>
                        <input type="text" class="inv-input" name="card_barcode" placeholder="Scan customer loyalty card">
                    </div>
                    <div class="inv-field">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label class="inv-label" style="margin-bottom: 0;">Credit Limit</label>
                            <label style="display: flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: #0284c7; cursor: pointer; user-select: none;">
                                <input type="checkbox" id="modal_has_credit_limit" name="has_credit_limit" value="1" onchange="toggleCreditLimitInput(this, 'modal_credit_limit_wrap', 'modal_credit_limit_input', 'modal_credit_no_limit_badge', 'modal_credit_limit_status')" style="width: 14px; height: 14px; accent-color: #0284c7; cursor: pointer;">
                                <span id="modal_credit_limit_status">Off (No Limit)</span>
                            </label>
                        </div>
                        <div id="modal_credit_limit_wrap" style="display: none;">
                            <input type="number" step="0.01" min="0" class="inv-input" id="modal_credit_limit_input" name="credit_limit" placeholder="Enter Credit Limit (LKR)..." disabled>
                        </div>
                        <div id="modal_credit_no_limit_badge" style="display: flex; align-items: center; height: 38px; padding: 0 12px; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 8px; font-size: 12px; font-weight: 700; color: #64748b;">
                            <i class="fa-solid fa-infinity" style="margin-right: 6px; color: #0284c7;"></i> No Credit Limit (Unlimited)
                        </div>
                    </div>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Address</label>
                    <textarea class="inv-input" name="address" rows="2" style="height: auto; padding: 8px 12px;" placeholder="Optional address..."></textarea>
                </div>

                <!-- Loyalty Program Setting -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; margin-top: 4px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #0f172a; cursor: pointer; margin: 0; font-size: 0.82rem;">
                        <input type="checkbox" name="is_loyalty" value="1" id="modal_is_loyalty" onchange="document.getElementById('modal_loyalty_override_wrap').style.display = this.checked ? 'block' : 'none';" style="width: 16px; height: 16px; accent-color: #7c3aed;">
                        <span>★ Mark as Loyalty Customer (Eligible for Loyalty Discount)</span>
                    </label>
                    <div id="modal_loyalty_override_wrap" style="display: none; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #cbd5e1;">
                        <label class="inv-label" style="font-size: 0.72rem; margin-bottom: 4px;">Custom Loyalty Discount % (Optional Override)</label>
                        <input type="number" step="0.01" min="0" max="100" class="inv-input" name="loyalty_discount_override" placeholder="Leave empty for default (<?= View::escape(Settings::get('loyalty_discount_percentage', '5.00')) ?>%)">
                    </div>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeCreateCustomerModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="saveCustomerBtn">Create Customer</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
window.CAN_MANAGE_CUSTOMERS = <?= $canManage ? 'true' : 'false' ?>;
window.CAN_CHANGE_STATUS = <?= $canChangeStatus ? 'true' : 'false' ?>;
window.CAN_PAY_CUSTOMERS = <?= $canPay ? 'true' : 'false' ?>;
window.CAN_DELETE_CUSTOMERS = <?= $canDelete ? 'true' : 'false' ?>;
window.customerBankAccounts = <?= json_encode($bankAccounts ?? []) ?>;
window.customerCashAccounts = <?= json_encode($cashAccounts ?? []) ?>;

let currentLoadedCustomerDetails = null;
let currentTxTabMode = 'all';

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
                placeholder: 'Search Customer Code, Name, Barcode, Phone, Balance...',
                onSearch: function(query) {
                    filterCustomersTableRealtime(query);
                },
                onEnter: function(query) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('search', query);
                    url.searchParams.set('page', 1);
                    window.location.href = url.toString();
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

function filterCustomersTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#customersTable tbody tr.inv-row');

    rows.forEach(tr => {
        if (!q) {
            tr.style.display = '';
            return;
        }
        // Exclude action column cells from text matching
        const dataCells = Array.from(tr.querySelectorAll('td:not(.col-right:last-child)'));
        const rowText = dataCells.map(td => td.textContent).join(' ').toLowerCase();
        const searchAttr = (tr.getAttribute('data-search') || '').toLowerCase();

        if (rowText.includes(q) || searchAttr.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}

function changeCustomerPageSize(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('limit', val);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

async function toggleCustomerStatus(id) {
    if (!confirm('Are you sure you want to toggle this customer account status?')) return;
    try {
        const res = await fetch('customer_api.php?action=toggle_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await res.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Could not update customer status.'));
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

async function deleteCustomer(id) {
    if (!confirm('Are you sure you want to move this customer to the Trash Bin?')) return;
    try {
        const res = await fetch('customer_api.php?action=delete_customer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await res.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Could not delete customer.'));
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

async function restoreCustomer(id) {
    if (!confirm('Are you sure you want to restore this customer from the Trash Bin?')) return;
    try {
        const res = await fetch('customer_api.php?action=restore_customer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await res.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Could not restore customer.'));
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

function openCreateCustomerModal() {
    const form = document.getElementById('createCustomerForm');
    form.reset();
    const cb = document.getElementById('modal_has_credit_limit');
    if (cb) {
        cb.checked = false;
        toggleCreditLimitInput(cb, 'modal_credit_limit_wrap', 'modal_credit_limit_input', 'modal_credit_no_limit_badge', 'modal_credit_limit_status');
    }
    document.getElementById('createCustomerModal').classList.add('active');
}

function closeCreateCustomerModal() {
    document.getElementById('createCustomerModal').classList.remove('active');
}

function toggleCreditLimitInput(cb, wrapId, inputId, badgeId, statusId) {
    const wrap = document.getElementById(wrapId);
    const input = document.getElementById(inputId);
    const badge = document.getElementById(badgeId);
    const status = document.getElementById(statusId);
    if (cb.checked) {
        if (wrap) wrap.style.display = 'block';
        if (badge) badge.style.display = 'none';
        if (input) {
            input.disabled = false;
            input.focus();
        }
        if (status) status.textContent = 'Limit Enabled';
    } else {
        if (wrap) wrap.style.display = 'none';
        if (badge) badge.style.display = 'flex';
        if (input) {
            input.disabled = true;
            input.value = '';
        }
        if (status) status.textContent = 'Off (No Limit)';
    }
}

async function handleCreateCustomerSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('saveCustomerBtn');
    btn.disabled = true;

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('customer_api.php?action=create_customer', {
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

async function openCustomerDetailDrawer(customerId) {
    const backdrop = document.getElementById('customerDrawerBackdrop');
    backdrop.classList.add('active');

    document.getElementById('drawerCustName').textContent = 'Loading...';
    document.getElementById('drawerQuickActions').innerHTML = '<span style="font-size: 0.78rem; color: #94a3b8;">Loading options...</span>';

    try {
        const res = await fetch(`customer_api.php?action=get_customer_details&customer_id=${customerId}`);
        const data = await res.json();

        if (!data.success) {
            alert(data.message || 'Failed to load customer profile.');
            closeCustomerDetailDrawer();
            return;
        }

        currentLoadedCustomerDetails = data;

        const cust = data.customer;
        const purchaseHist = (data.purchase_history && data.purchase_history.invoices) ? data.purchase_history.invoices : [];
        const st = (cust.status || 'active').toLowerCase();
        const isWalkin = !!cust.is_walkin;
        const balVal = is_numeric(cust.credit_balance) ? parseFloat(cust.credit_balance) : 0;

        document.getElementById('drawerCustCode').textContent = cust.customer_code || ('ID: #' + cust.id);
        document.getElementById('drawerCustName').textContent = cust.name || 'N/A';

        if (isWalkin) {
            document.getElementById('drawerWalkinBadge').innerHTML = '<span class="inv-code-pill" style="font-size: 0.65rem; color: #0369a1; background: #e0f2fe; border-color: #bae6fd;">Default POS Walk-in Customer</span>';
        } else {
            let badges = '';
            if (cust.is_loyalty == 1) {
                const discText = cust.loyalty_discount_override ? `${parseFloat(cust.loyalty_discount_override)}%` : 'Standard';
                badges += `<span class="inv-code-pill" style="font-size: 0.65rem; color: #7c3aed; background: #f5f3ff; border-color: #ddd6fe; font-weight: 800;">★ Loyalty Member (${discText})</span>`;
            }
            document.getElementById('drawerWalkinBadge').innerHTML = badges;
        }

        document.getElementById('drawerCustStatusBadge').className = 'prod-badge ' + (st === 'active' ? 'prod-badge-active' : 'prod-badge-inactive');
        document.getElementById('drawerCustStatusBadge').innerHTML = `<span class="dot"></span>${st.toUpperCase()}`;

        // Render Action Buttons inside Side Panel Drawer
        const actionsContainer = document.getElementById('drawerQuickActions');
        let actionsHtml = `
            <button type="button" class="inv-btn inv-btn-ghost" style="font-size: 11px; padding: 0 12px; background: #f1f5f9;" onclick="openTxHistoryDrawer(${cust.id})">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                History
            </button>
        `;

        if (balVal > 0 && window.CAN_PAY_CUSTOMERS) {
            actionsHtml += `
                <button type="button" class="inv-btn inv-btn-success" style="font-size: 11px; padding: 0 12px;" onclick="openCollectPaymentModal(${cust.id})">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Collect Payment
                </button>
            `;
        }

        if (window.CAN_MANAGE_CUSTOMERS) {
            actionsHtml += `
                <a href="customer_edit.php?id=${cust.id}" class="inv-btn inv-btn-ghost" style="font-size: 11px; padding: 0 12px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </a>
            `;
        }

        if (window.CAN_CHANGE_STATUS && !isWalkin) {
            const isAct = st === 'active';
            actionsHtml += `
                <button type="button" class="inv-btn ${isAct ? 'inv-btn-danger' : 'inv-btn-success'}" style="font-size: 11px; padding: 0 12px;" onclick="toggleCustomerStatusFromDrawer(${cust.id}, '${st}')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">${isAct ? '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>' : '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'}</svg>
                    ${isAct ? 'Deactivate' : 'Activate'}
                </button>
            `;
        }
        actionsContainer.innerHTML = actionsHtml;

        const hasNoLimit = (cust.credit_limit === null || cust.credit_limit === undefined || cust.credit_limit === '');
        const limitVal = hasNoLimit ? 'No Limit' : (is_numeric(cust.credit_limit) ? 'LKR ' + parseFloat(cust.credit_limit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : cust.credit_limit);
        const balDisplay = is_numeric(cust.credit_balance) ? balVal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : cust.credit_balance;

        document.getElementById('drawerCreditLimit').textContent = limitVal;
        const balEl = document.getElementById('drawerOutstandingBalance');
        balEl.textContent = 'LKR ' + balDisplay;
        balEl.style.color = (is_numeric(cust.credit_balance) && balVal > 0) ? '#dc2626' : '#166534';

        const payTriggerContainer = document.getElementById('drawerPayTriggerContainer');
        if (balVal > 0 && window.CAN_PAY_CUSTOMERS) {
            payTriggerContainer.innerHTML = `
                <button type="button" class="inv-btn inv-btn-success" style="width: 100%; justify-content: center; padding: 0 10px; font-size: 11px;" onclick="openCollectPaymentModal(${cust.id})">
                    Collect LKR ${balDisplay}
                </button>
            `;
        } else {
            payTriggerContainer.innerHTML = '';
        }

        document.getElementById('drawerPhone').textContent = cust.phone || 'N/A';
        document.getElementById('drawerWhatsApp').textContent = cust.whatsapp || 'N/A';
        document.getElementById('drawerCardBarcode').textContent = cust.card_barcode || 'None';
        document.getElementById('drawerAddress').textContent = cust.address || 'No address provided.';

        document.getElementById('drawerHistoryLinkContainer').innerHTML = `<button type="button" onclick="openTxHistoryDrawer(${cust.id})" style="background: none; border: none; font-size: 0.72rem; font-weight: 800; color: #0284c7; cursor: pointer;">View All →</button>`;

        // Render recent activity
        const activityContainer = document.getElementById('drawerActivityContainer');
        let activityHtml = '';

        if (purchaseHist.length > 0) {
            purchaseHist.slice(0, 3).forEach(p => {
                activityHtml += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem;">
                        <div>
                            <div style="font-weight: 700; color: #0f172a;">Invoice #${esc(p.invoice_no || p.id)}</div>
                            <div style="font-size: 0.68rem; color: #94a3b8;">${esc(p.date || '')}</div>
                        </div>
                        <div style="font-weight: 800; font-family: monospace; color: #0f172a;">
                            LKR ${parseFloat(p.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}
                        </div>
                    </div>
                `;
            });
            activityHtml += `
                <button type="button" class="inv-btn inv-btn-ghost" style="width: 100%; justify-content: center; font-size: 11px; margin-top: 4px; color: #0284c7; border-color: #bae6fd; background: #f0f9ff;" onclick="openTxHistoryDrawer(${cust.id})">
                    View Full Transaction History →
                </button>
            `;
        } else {
            activityHtml = `
                <div style="color: #94a3b8; font-size: 0.78rem; padding: 10px; text-align: center; background: #f8fafc; border-radius: 8px;">
                    No recent purchases recorded.
                    <div style="margin-top: 6px;">
                        <button type="button" class="inv-btn inv-btn-ghost" style="font-size: 11px; padding: 0 10px;" onclick="openTxHistoryDrawer(${cust.id})">View History Drawer</button>
                    </div>
                </div>
            `;
        }

        activityContainer.innerHTML = activityHtml;

    } catch (e) {
        console.error(e);
        alert('Error loading customer profile.');
        closeCustomerDetailDrawer();
    }
}

// PANEL 2: Open Separate Transaction History Side Panel (Same 480px width)
async function openTxHistoryDrawer(customerId) {
    const txBackdrop = document.getElementById('txHistoryDrawerBackdrop');
    txBackdrop.classList.add('active');

    document.getElementById('txDrawerCustTitle').textContent = 'Loading...';
    document.getElementById('txHistoryTimelineBody').innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem;">Loading transaction history...</div>';

    try {
        let data = currentLoadedCustomerDetails;
        if (!data || !data.customer || data.customer.id != customerId) {
            const res = await fetch(`customer_api.php?action=get_customer_details&customer_id=${customerId}`);
            data = await res.json();
            if (data.success) {
                currentLoadedCustomerDetails = data;
            }
        }

        if (!data || !data.success) {
            alert('Failed to load transaction history.');
            closeTxHistoryDrawer();
            return;
        }

        const cust = data.customer;
        document.getElementById('txDrawerCustTitle').textContent = (cust.name || 'Customer') + ' (' + (cust.customer_code || ('#' + cust.id)) + ')';

        switchTxTab('all');

    } catch (e) {
        console.error(e);
        alert('Error opening transaction history panel.');
        closeTxHistoryDrawer();
    }
}

function switchTxTab(mode) {
    currentTxTabMode = mode;
    ['All', 'Sales', 'Ledger'].forEach(t => {
        const el = document.getElementById('txTab' + t);
        if (el) {
            if (t.toLowerCase() === mode) {
                el.style.background = '#ffffff';
                el.style.boxShadow = '0 1px 3px rgba(0,0,0,0.05)';
                el.style.borderColor = '#cbd5e1';
                el.style.color = '#0f172a';
            } else {
                el.style.background = 'transparent';
                el.style.boxShadow = 'none';
                el.style.borderColor = 'transparent';
                el.style.color = '#64748b';
            }
        }
    });

    renderTxTimeline();
}

function renderTxTimeline() {
    const container = document.getElementById('txHistoryTimelineBody');
    if (!currentLoadedCustomerDetails) {
        container.innerHTML = '<div style="color: #94a3b8; text-align: center; padding: 2rem;">No data available.</div>';
        return;
    }

    const data = currentLoadedCustomerDetails;
    const timeline = data.activity_timeline || [];
    const purchases = (data.purchase_history && data.purchase_history.invoices) ? data.purchase_history.invoices : [];
    const ledger = (data.credit_history && data.credit_history.ledger) ? data.credit_history.ledger : [];

    let itemsHtml = '';

    if (currentTxTabMode === 'all') {
        if (timeline.length === 0) {
            itemsHtml = '<div style="color: #94a3b8; font-size: 0.8rem; text-align: center; padding: 2.5rem 1rem; background: #f8fafc; border-radius: 12px;">No transactions recorded for this customer.</div>';
        } else {
            timeline.forEach(item => {
                let badgeStyle = 'background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
                if (item.event_type === 'Invoice' || item.event_type === 'Credit Sale') {
                    badgeStyle = 'background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;';
                } else if (item.event_type === 'Customer Payment') {
                    badgeStyle = 'background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;';
                } else if (item.event_type === 'Sales Return') {
                    badgeStyle = 'background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3;';
                }

                itemsHtml += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="inv-code-pill" style="${badgeStyle}">${esc(item.event_type)}</span>
                            <span style="font-size: 0.68rem; color: #94a3b8; font-weight: 500;">${esc(item.date || '')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 2px;">
                            <div>
                                <div style="font-size: 0.82rem; font-weight: 800; color: #0f172a;">Ref: ${esc(item.reference_no || 'N/A')}</div>
                                <div style="font-size: 0.7rem; color: #64748b;">${esc(item.notes || item.branch_name || '')}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.92rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a;">
                                    LKR ${parseFloat(item.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}
                                </div>
                                ${item.balance_after !== null && item.balance_after !== undefined ? `<div style="font-size: 0.68rem; font-weight: 700; color: #64748b;">Bal: LKR ${parseFloat(item.balance_after).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
        }
    } else if (currentTxTabMode === 'sales') {
        if (purchases.length === 0) {
            itemsHtml = '<div style="color: #94a3b8; font-size: 0.8rem; text-align: center; padding: 2.5rem 1rem; background: #f8fafc; border-radius: 12px;">No sales invoices found.</div>';
        } else {
            purchases.forEach(inv => {
                itemsHtml += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="inv-code-pill" style="background: #e0f2fe; color: #0284c7; border-color: #bae6fd;">Invoice #${esc(inv.invoice_no || inv.id)}</span>
                            <span style="font-size: 0.68rem; color: #94a3b8;">${esc(inv.date || '')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2px;">
                            <div>
                                <div style="font-size: 0.75rem; color: #475569; font-weight: 600;">Branch: ${esc(inv.branch_name || 'Main')}</div>
                                <div style="font-size: 0.7rem; color: #94a3b8;">Items count: ${inv.total_items || 1}</div>
                            </div>
                            <div style="font-size: 0.95rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a;">
                                LKR ${parseFloat(inv.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}
                            </div>
                        </div>
                    </div>
                `;
            });
        }
    } else if (currentTxTabMode === 'ledger') {
        if (ledger.length === 0) {
            itemsHtml = '<div style="color: #94a3b8; font-size: 0.8rem; text-align: center; padding: 2.5rem 1rem; background: #f8fafc; border-radius: 12px;">No credit ledger entries found.</div>';
        } else {
            ledger.forEach(l => {
                const isPaid = (l.transaction_type === 'payment');
                const badgeStyle = isPaid ? 'background: #dcfce7; color: #15803d; border-color: #bbf7d0;' : 'background: #ffe4e6; color: #be123c; border-color: #fecdd3;';
                itemsHtml += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="inv-code-pill" style="${badgeStyle}">${esc(l.transaction_type ? l.transaction_type.toUpperCase() : 'LEDGER')}</span>
                            <span style="font-size: 0.68rem; color: #94a3b8;">${esc(l.created_at || '')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <div>
                                <div style="font-size: 0.78rem; font-weight: 700; color: #0f172a;">Ref: ${esc(l.reference_no || 'N/A')}</div>
                                <div style="font-size: 0.7rem; color: #64748b;">Invoice: LKR ${parseFloat(l.invoice_total || 0).toLocaleString('en-US', {minimumFractionDigits:2})}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.9rem; font-weight: 800; font-family: ui-monospace, monospace; color: ${isPaid ? '#16a34a' : '#dc2626'};">
                                    LKR ${parseFloat(isPaid ? l.amount_paid : l.amount_due).toLocaleString('en-US', {minimumFractionDigits: 2})}
                                </div>
                                <div style="font-size: 0.68rem; font-weight: 700; color: #64748b;">Bal After: LKR ${parseFloat(l.balance_after || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
    }

    container.innerHTML = itemsHtml;
}

function closeTxHistoryDrawer() {
    document.getElementById('txHistoryDrawerBackdrop').classList.remove('active');
}

// Payment Collection Split System & Modal Handlers
let customerSplitRows = [];
let currentCustomerCreditBalance = 0;

function round2(num) {
    return Math.round((parseFloat(num) || 0) * 100) / 100;
}

function toggleCustomerInvoicesPreview() {
    const box = document.getElementById('payModalInvoicesContainer');
    const txt = document.getElementById('payModalInvoicesToggleText');
    if (!box) return;
    if (box.style.display === 'none' || !box.style.display) {
        box.style.display = 'block';
        if (txt) txt.textContent = 'Hide Invoices ▲';
    } else {
        box.style.display = 'none';
        if (txt) txt.textContent = 'Show Invoices ▼';
    }
}

function setCustomerPayToFull() {
    const bal = currentCustomerCreditBalance;
    const input = document.getElementById('payAmountInput');
    if (bal > 0) {
        input.value = bal.toFixed(2);
        onCustomerTotalAmountChanged(input.value);
    }
}

function onCustomerTotalAmountChanged(val) {
    const tot = Math.max(0, parseFloat(val) || 0);
    if (customerSplitRows.length === 1) {
        customerSplitRows[0].amount = tot;
        renderCustomerSplitRows();
    } else {
        recalcCustomerSplits();
    }
}

function getCustomerPaymentMethodOptions(selectedKey) {
    let html = '';
    const methods = [];

    if (window.customerCashAccounts && window.customerCashAccounts.length > 0) {
        window.customerCashAccounts.forEach(ca => {
            const isMain = (ca.account_code === '1010');
            methods.push({
                key: isMain ? 'Cash in Hand' : ca.account_name,
                label: isMain ? 'Cash in Hand (Main Safe)' : `${ca.account_name} (${ca.account_code})`
            });
        });
    } else {
        methods.push({ key: 'Cash in Hand', label: 'Cash in Hand (Main Safe)' });
    }

    methods.push({ key: 'Cash Drawer', label: 'Cash Drawer (POS Register)' });
    methods.push({ key: 'Petty Cash', label: 'Petty Cash Account' });

    if (window.customerBankAccounts && window.customerBankAccounts.length > 0) {
        window.customerBankAccounts.forEach(b => {
            methods.push({
                key: `Bank:${b.id}`,
                label: `Bank Transfer: ${b.bank_name} - ${b.account_name} (${b.account_number})`
            });
        });
    }

    methods.push({ key: 'Cheque', label: 'Cheque Collection' });
    methods.push({ key: 'Voucher', label: 'Gift Voucher' });

    methods.forEach(m => {
        const sel = (m.key === selectedKey) ? 'selected' : '';
        html += `<option value="${m.key}" ${sel}>${m.label}</option>`;
    });

    return html;
}

function renderCustomerSplitRows() {
    const tbody = document.getElementById('customerSplitsBody');
    if (!tbody) return;

    let html = '';
    customerSplitRows.forEach((row, idx) => {
        const isCheque = (row.method === 'Cheque');
        const isVoucher = (row.method === 'Voucher');
        const isBank = (row.method && row.method.startsWith('Bank:'));

        let refPlaceholder = 'Optional Ref #';
        if (isCheque) refPlaceholder = 'Cheque No *';
        else if (isVoucher) refPlaceholder = 'Voucher Code *';
        else if (isBank) refPlaceholder = 'Bank Ref / Tx No';

        html += `
            <tr style="border-bottom: 1px solid #f1f5f9; height: 40px;">
                <td style="padding: 5px 10px;">
                    <select class="inv-input" onchange="updateCustomerSplitMethod(${idx}, this.value)" style="height: 32px; font-size: 11.5px; font-weight: 700; width: 100%;">
                        ${getCustomerPaymentMethodOptions(row.method)}
                    </select>
                </td>
                <td style="padding: 5px 10px;">
                    <input type="number" step="0.01" min="0" class="inv-input" value="${row.amount > 0 ? parseFloat(row.amount).toFixed(2) : ''}" placeholder="0.00" oninput="updateCustomerSplitAmount(${idx}, this.value)" style="height: 32px; font-size: 12px; font-weight: 800; font-family: monospace; text-align: right; width: 100%;">
                </td>
                <td style="padding: 5px 10px;">
                    <input type="text" class="inv-input" value="${esc(row.ref || '')}" placeholder="${refPlaceholder}" oninput="updateCustomerSplitRef(${idx}, this.value)" style="height: 32px; font-size: 11.5px; width: 100%;">
                </td>
                <td style="padding: 5px 10px;">
                    <input type="text" class="inv-input" value="${esc(row.cheque_bank || '')}" placeholder="e.g. BOC, Commercial" oninput="updateCustomerSplitChequeBank(${idx}, this.value)" ${isCheque ? '' : 'disabled style="height: 32px; font-size: 11px; opacity: 0.4; width: 100%;"'} style="height: 32px; font-size: 11px; width: 100%;">
                </td>
                <td style="padding: 5px 10px;">
                    <input type="date" class="inv-input" value="${row.cheque_date || '<?= date('Y-m-d') ?>'}" onchange="updateCustomerSplitChequeDate(${idx}, this.value)" ${isCheque ? '' : 'disabled style="height: 32px; font-size: 11px; opacity: 0.4; width: 100%;"'} style="height: 32px; font-size: 11px; width: 100%;">
                </td>
                <td style="padding: 5px 8px; text-align: center;">
                    ${customerSplitRows.length > 1 ? `
                        <button type="button" onclick="removeCustomerSplitRow(${idx})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;" title="Remove split">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    ` : `
                        <span style="color: #cbd5e1; font-size: 14px;">-</span>
                    `}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    recalcCustomerSplits();
}

function addCustomerSplitRow() {
    const totalToPay = parseFloat(document.getElementById('payAmountInput').value) || 0;
    const currentAlloc = customerSplitRows.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
    const rem = Math.max(0, round2(totalToPay - currentAlloc));

    let defaultMethod = 'Cash in Hand';
    if (customerSplitRows.length === 1 && customerSplitRows[0].method === 'Cash in Hand') {
        if (window.customerBankAccounts && window.customerBankAccounts.length > 0) {
            defaultMethod = `Bank:${window.customerBankAccounts[0].id}`;
        } else {
            defaultMethod = 'Cheque';
        }
    }

    const todayStr = '<?= date('Y-m-d') ?>';

    customerSplitRows.push({
        method: defaultMethod,
        amount: rem,
        ref: '',
        cheque_bank: '',
        cheque_date: todayStr
    });

    renderCustomerSplitRows();
}

function removeCustomerSplitRow(idx) {
    if (customerSplitRows.length <= 1) return;
    customerSplitRows.splice(idx, 1);
    renderCustomerSplitRows();
}

function updateCustomerSplitMethod(idx, val) {
    customerSplitRows[idx].method = val;
    renderCustomerSplitRows();
}

function updateCustomerSplitAmount(idx, val) {
    const amt = Math.max(0, parseFloat(val) || 0);
    customerSplitRows[idx].amount = amt;
    // If only 1 split row exists, keep Paying Now input in sync with what user entered
    if (customerSplitRows.length === 1) {
        const input = document.getElementById('payAmountInput');
        if (input) {
            input.value = amt > 0 ? amt.toFixed(2) : '';
        }
    }
    recalcCustomerSplits();
}

function syncCustomerTotalToSplits() {
    const allocTot = round2(customerSplitRows.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0));
    const input = document.getElementById('payAmountInput');
    if (input) {
        input.value = allocTot > 0 ? allocTot.toFixed(2) : '';
    }
    recalcCustomerSplits();
}

function updateCustomerSplitRef(idx, val) {
    customerSplitRows[idx].ref = val;
}

function updateCustomerSplitChequeBank(idx, val) {
    customerSplitRows[idx].cheque_bank = val;
}

function updateCustomerSplitChequeDate(idx, val) {
    customerSplitRows[idx].cheque_date = val;
}

function recalcCustomerSplits() {
    const targetTot = round2(parseFloat(document.getElementById('payAmountInput').value) || 0);
    const allocTot = round2(customerSplitRows.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0));
    const diff = round2(targetTot - allocTot);

    // Remaining debt calculation (Kept as credit)
    const outstanding = round2(currentCustomerCreditBalance);
    const remainingCredit = Math.max(0, round2(outstanding - targetTot));

    // Update Remaining Credit Card
    const remCreditEl = document.getElementById('payModalRemainingCreditVal');
    const remBadgeEl = document.getElementById('payModalRemainingBadge');
    if (remCreditEl) {
        remCreditEl.innerText = `LKR ${remainingCredit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
    if (remBadgeEl) {
        if (targetTot <= 0) {
            remBadgeEl.className = 'prod-badge';
            remBadgeEl.style.background = '#f1f5f9';
            remBadgeEl.style.color = '#64748b';
            remBadgeEl.innerText = 'No Payment';
        } else if (remainingCredit === 0 && targetTot <= (outstanding + 0.01)) {
            remBadgeEl.className = 'prod-badge prod-badge-active';
            remBadgeEl.style.background = '#dcfce7';
            remBadgeEl.style.color = '#15803d';
            remBadgeEl.innerText = '✓ Fully Settled';
        } else if (targetTot > (outstanding + 0.01)) {
            remBadgeEl.className = 'prod-badge';
            remBadgeEl.style.background = '#fee2e2';
            remBadgeEl.style.color = '#b91c1c';
            remBadgeEl.innerText = '⚠️ Overpaying';
        } else {
            remBadgeEl.className = 'prod-badge';
            remBadgeEl.style.background = '#eff6ff';
            remBadgeEl.style.color = '#1d4ed8';
            remBadgeEl.innerText = 'Kept as Credit';
        }
    }

    const allocText = document.getElementById('customerSplitAllocatedText');
    if (allocText) {
        allocText.innerText = `Splits: LKR ${allocTot.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} / Paying: LKR ${targetTot.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    const badge = document.getElementById('customerSplitDifferenceBadge');
    const syncBtn = document.getElementById('syncTotalToSplitsBtn');
    const submitBtn = document.getElementById('submitPaymentBtn');

    const isSplitsBalanced = (Math.abs(diff) < 0.01 && targetTot > 0);
    const isWithinOutstanding = (targetTot <= (outstanding + 0.01));

    if (syncBtn) {
        if (!isSplitsBalanced && allocTot > 0 && customerSplitRows.length > 1) {
            syncBtn.style.display = 'inline-block';
            syncBtn.innerText = `Set Paying to LKR ${allocTot.toFixed(2)}`;
        } else {
            syncBtn.style.display = 'none';
        }
    }

    if (targetTot > (outstanding + 0.01)) {
        if (badge) {
            badge.className = 'prod-badge';
            badge.style.background = '#fee2e2';
            badge.style.color = '#b91c1c';
            badge.innerText = `Cannot exceed debt (LKR ${outstanding.toFixed(2)})`;
        }
        if (submitBtn) submitBtn.disabled = true;
    } else if (isSplitsBalanced) {
        if (badge) {
            badge.className = 'prod-badge prod-badge-active';
            badge.style.background = '#dcfce7';
            badge.style.color = '#15803d';
            badge.innerText = '✓ Balanced (100%)';
        }
        if (submitBtn) submitBtn.disabled = false;
    } else {
        if (badge) {
            badge.className = 'prod-badge';
            badge.style.background = '#fee2e2';
            badge.style.color = '#b91c1c';
            badge.innerText = (diff > 0) 
                ? `Remaining to allocate: LKR ${diff.toFixed(2)}` 
                : `Over-allocated: LKR ${Math.abs(diff).toFixed(2)}`;
        }
        if (submitBtn) submitBtn.disabled = true;
    }
}

async function openCollectPaymentModal(customerId) {
    const modal = document.getElementById('collectPaymentModal');
    modal.classList.add('active');

    document.getElementById('payCustomerId').value = customerId;
    document.getElementById('payModalCustTitle').textContent = 'Loading customer details...';
    document.getElementById('payModalOutstandingVal').textContent = 'LKR ...';
    document.getElementById('payModalUnpaidInvoicesTbody').innerHTML = '<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 1rem;">Loading unpaid invoices...</td></tr>';
    document.getElementById('payAmountInput').value = '';
    document.getElementById('payNotesInput').value = '';
    document.getElementById('payModalInvoicesCount').textContent = '0';
    document.getElementById('payModalInvoicesContainer').style.display = 'none';
    document.getElementById('payModalInvoicesToggleText').textContent = 'Show Invoices ▼';
    document.getElementById('payModalRemainingCreditVal').textContent = 'LKR 0.00';
    const remBadge = document.getElementById('payModalRemainingBadge');
    if (remBadge) {
        remBadge.className = 'prod-badge';
        remBadge.style.background = '#dbeafe';
        remBadge.style.color = '#1e40af';
        remBadge.textContent = 'Kept as Credit';
    }
    
    currentCustomerCreditBalance = 0;
    customerSplitRows = [];
    renderCustomerSplitRows();

    try {
        const res = await fetch(`customer_payments_api.php?action=get_customer_outstanding&customer_id=${customerId}`);
        const data = await res.json();

        if (!data.success) {
            alert(data.message || 'Failed to fetch customer outstanding balance.');
            closeCollectPaymentModal();
            return;
        }

        const balVal = parseFloat(data.credit_balance || 0);
        currentCustomerCreditBalance = balVal;

        document.getElementById('payModalCustTitle').textContent = (data.name || 'Customer') + ' (' + (data.customer_code || ('#' + customerId)) + ')';
        document.getElementById('payModalOutstandingVal').textContent = 'LKR ' + balVal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        const hasNoLimit = (data.credit_limit === null || data.credit_limit === undefined || data.credit_limit === '');
        const limitDisplay = hasNoLimit ? 'No Limit' : `LKR ${parseFloat(data.credit_limit || 0).toLocaleString('en-US', {minimumFractionDigits:2})}`;
        const availDisplay = hasNoLimit ? 'Unlimited' : `LKR ${parseFloat(data.available_credit || 0).toLocaleString('en-US', {minimumFractionDigits:2})}`;
        document.getElementById('payModalLimitInfo').textContent = `Credit Limit: ${limitDisplay} | Available: ${availDisplay}`;
        
        const initialAmount = balVal > 0 ? balVal : 0;
        document.getElementById('payAmountInput').value = initialAmount > 0 ? initialAmount.toFixed(2) : '';

        // Initialize single default split row
        const todayStr = '<?= date('Y-m-d') ?>';
        customerSplitRows = [{
            method: 'Cash in Hand',
            amount: initialAmount,
            ref: '',
            cheque_bank: '',
            cheque_date: todayStr
        }];
        renderCustomerSplitRows();

        // Render Unpaid Invoices
        const unpaidCount = (data.unpaid_invoices && data.unpaid_invoices.length) ? data.unpaid_invoices.length : 0;
        document.getElementById('payModalInvoicesCount').textContent = unpaidCount;

        let invHtml = '';
        if (unpaidCount === 0) {
            invHtml = '<tr><td colspan="4" style="text-align: center; color: #16a34a; padding: 1rem; font-weight: 700;">No unpaid credit invoices. Account is clear!</td></tr>';
        } else {
            data.unpaid_invoices.forEach(inv => {
                invHtml += `
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 6px 6px;">
                            <strong style="color: #0f172a;">${esc(inv.invoice_no)}</strong>
                        </td>
                        <td style="padding: 6px 6px; font-size: 0.72rem; color: #64748b;">${esc(inv.created_at)}</td>
                        <td style="padding: 6px 6px; font-family: monospace;">LKR ${parseFloat(inv.total_amount).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                        <td style="padding: 6px 6px; text-align: right; font-weight: 800; font-family: monospace; color: #dc2626;">
                            LKR ${parseFloat(inv.due_amount).toLocaleString('en-US', {minimumFractionDigits:2})}
                        </td>
                    </tr>
                `;
            });
        }
        document.getElementById('payModalUnpaidInvoicesTbody').innerHTML = invHtml;

    } catch (e) {
        console.error(e);
        alert('Error loading payment collection details.');
        closeCollectPaymentModal();
    }
}

function closeCollectPaymentModal() {
    document.getElementById('collectPaymentModal').classList.remove('active');
}

async function handleCollectPaymentSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('submitPaymentBtn');
    btn.disabled = true;

    const customerId = parseInt(document.getElementById('payCustomerId').value);
    const targetTot = round2(parseFloat(document.getElementById('payAmountInput').value) || 0);
    const allocTot = round2(customerSplitRows.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0));
    const notes = document.getElementById('payNotesInput').value.trim();

    if (!customerId || targetTot <= 0) {
        alert('Please enter a valid collection payment amount.');
        btn.disabled = false;
        return;
    }

    if (targetTot > (currentCustomerCreditBalance + 0.01)) {
        alert(`Payment collection amount (LKR ${targetTot.toFixed(2)}) cannot exceed the customer's outstanding balance of LKR ${currentCustomerCreditBalance.toFixed(2)}.`);
        btn.disabled = false;
        return;
    }

    if (Math.abs(targetTot - allocTot) > 0.01) {
        alert(`Payment splits (LKR ${allocTot.toFixed(2)}) must exactly match the total collection amount (LKR ${targetTot.toFixed(2)}).`);
        btn.disabled = false;
        return;
    }

    // Validate each split
    for (let i = 0; i < customerSplitRows.length; i++) {
        const row = customerSplitRows[i];
        const rowAmt = parseFloat(row.amount) || 0;
        if (rowAmt <= 0) {
            alert(`Split #${i + 1} has an invalid or zero amount.`);
            btn.disabled = false;
            return;
        }

        if (row.method === 'Cheque') {
            if (!row.ref) {
                alert(`Please enter the Cheque Number for Split #${i + 1}.`);
                btn.disabled = false;
                return;
            }
            if (!row.cheque_bank || !row.cheque_bank.trim()) {
                alert(`Please enter the Cheque Bank name (e.g. BOC, Commercial) for Split #${i + 1}.`);
                btn.disabled = false;
                return;
            }
        }

        if (row.method === 'Voucher' && !row.ref) {
            alert(`Please enter or scan the Gift Voucher code for Split #${i + 1}.`);
            btn.disabled = false;
            return;
        }
    }

    // Format splits payload
    const splitsPayload = customerSplitRows.map(r => {
        let m = r.method;
        let bankId = null;
        if (m.startsWith('Bank:')) {
            bankId = parseInt(m.replace('Bank:', ''));
            m = 'Bank Transfer';
        }
        return {
            payment_method: m,
            bank_account_id: bankId,
            amount: parseFloat(r.amount) || 0,
            reference_no: r.ref || '',
            cheque_no: (m === 'Cheque') ? (r.ref || '') : '',
            bank_name: (m === 'Cheque') ? (r.cheque_bank || '') : '',
            cheque_date: (m === 'Cheque') ? (r.cheque_date || '<?= date('Y-m-d') ?>') : '',
            voucher_code: (m === 'Voucher') ? (r.ref || '') : ''
        };
    });

    const payload = {
        customer_id: customerId,
        amount: targetTot,
        splits: splitsPayload,
        allocation_mode: 'auto',
        notes: notes
    };

    try {
        const res = await fetch('customer_payments_api.php?action=process_customer_payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            alert(`SUCCESS: ${data.message}`);
            closeCollectPaymentModal();
            // Reload customer drawer & page
            currentLoadedCustomerDetails = null;
            openCustomerDetailDrawer(customerId);
            location.reload();
        } else {
            alert(`PAYMENT ERROR: ${data.message}`);
        }
    } catch (err) {
        alert('Communication error: ' + err.message);
    } finally {
        btn.disabled = false;
    }
}

async function toggleCustomerStatusFromDrawer(id, currentStatus) {
    const actionText = currentStatus === 'active' ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${actionText} this customer account?`)) return;

    try {
        const res = await fetch('customer_api.php?action=toggle_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await res.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Could not update customer status.'));
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

function is_numeric(val) {
    return !isNaN(parseFloat(val)) && isFinite(val);
}

function closeCustomerDetailDrawer() {
    document.getElementById('customerDrawerBackdrop').classList.remove('active');
}
</script>
