<?php
/**
 * Curtiss POS - Bank Accounts & Cash Deposits Master View (Stage 13 Redesign)
 * Full-Page Report System for Bank Register Ledger with Print, Filter, Export & Fixed-Screen Tables.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header Buttons -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <!-- Hub Actions (Visible on Bank Accounts Hub) -->
    <div id="hubHeaderActions" style="display: flex; align-items: center; gap: 8px;">
        <?php if (($view ?? 'active') === 'deleted'): ?>
        <a href="bank_accounts.php" class="inv-btn inv-btn-ghost" style="color: #1e293b; border-color: #cbd5e1; background: #ffffff;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Active Accounts
        </a>
        <?php else: ?>
        <a href="bank_accounts.php?view=deleted" class="inv-btn" style="color: #dc2626; background: #fef2f2; border-color: #fca5a5;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Trash Bin
        </a>
        <?php if (Auth::hasPermission('make_bank_deposit')): ?>
        <button type="button" class="inv-btn inv-btn-ghost" onclick="openDepositModal()" style="color: #15803d; border-color: rgba(34,197,94,0.3); background: rgba(240,253,244,0.95);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            + Cash Deposit
        </button>
        <?php endif; ?>
        <?php if (Auth::hasPermission('manage_bank_accounts')): ?>
        <button type="button" class="inv-btn inv-btn-primary" onclick="openAccountModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
            + Add Bank Account
        </button>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Register Report Actions (Visible on Full Page Register Ledger) -->
    <div id="registerHeaderActions" style="display: none; align-items: center; gap: 8px;">
        <button type="button" class="inv-btn inv-btn-ghost" onclick="closeRegisterFullPage()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            ← Bank Accounts
        </button>

        <button type="button" class="inv-btn inv-btn-ghost" onclick="openRegisterFilterModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter Scope
        </button>

        <?php if ($canExport): ?>
        <button type="button" class="inv-btn inv-btn-ghost" onclick="openRegisterExportModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Options
        </button>
        <?php endif; ?>

        <button type="button" class="inv-btn inv-btn-primary" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print Statement
        </button>
    </div>
</div>

<style>
    :root {
        --inv-primary: #0f172a;
        --inv-accent: #2563eb;
        --inv-success: #16a34a;
        --inv-warning: #d97706;
        --inv-danger: #dc2626;
        --inv-bg-glass: rgba(255, 255, 255, 0.94);
        --inv-border: rgba(226, 232, 240, 0.9);
        --inv-radius: 16px;
        --inv-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
    }

    /* Page Header */
    .inv-header {
        margin-bottom: 0.85rem;
    }
    .inv-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.025em;
        line-height: 1.2;
        margin: 0;
    }

    /* Button System */
    .inv-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 34px;
        min-height: 34px;
        line-height: 32px;
        padding: 0 16px;
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
        background: rgba(255, 255, 255, 0.9);
        border-color: rgba(226, 232, 240, 0.9);
        color: #0f172a;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
    }
    .inv-btn-primary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    /* Status Badges */
    .prod-badge {
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
        white-space: nowrap;
        box-sizing: border-box;
    }
    .prod-badge-active {
        background: #dcfce7;
        color: #15803d;
        border-color: #86efac;
    }
    .prod-badge-active .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #16a34a;
    }
    .prod-badge-inactive {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #fca5a5;
    }
    .prod-badge-inactive .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #dc2626;
    }

    /* Glassmorphism Cards & Grids */
    .inv-card {
        background: var(--inv-bg-glass);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--inv-border);
        border-radius: var(--inv-radius);
        box-shadow: var(--inv-shadow);
        margin-bottom: 0.85rem;
        padding: 1rem 1.25rem;
        width: 100%;
        box-sizing: border-box;
    }

    /* KPI Summary Cards Grid */
    .bank-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
        flex-shrink: 0;
    }
    .bank-kpi-card {
        background: rgba(255, 255, 255, 0.88);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.03);
        position: relative;
        overflow: hidden;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .bank-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(0,0,0,0.06);
    }
    .bank-kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: #3b82f6;
    }
    .bank-kpi-card.balance::before { background: #16a34a; }
    .bank-kpi-card.accounts::before { background: #6366f1; }

    .bank-kpi-title {
        font-size: 0.72rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.25rem;
    }
    .bank-kpi-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }
    .bank-kpi-sub {
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 0.25rem;
        font-weight: 500;
    }

    /* Bank Accounts Cards Grid */
    .bank-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1rem;
        overflow-y: auto;
        flex: 1;
        padding-bottom: 1rem;
        min-height: 0;
    }
    .bank-card {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        padding: 1.25rem;
        box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .bank-card:hover {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
        transform: translateY(-2px);
    }
    .bank-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    .bank-name {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
    }

    .bank-acc-num {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.88rem;
        color: #2563eb;
        font-weight: 700;
        margin-bottom: 0.75rem;
        background: #eff6ff;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-block;
        border: 1px solid #bfdbfe;
    }
    .bank-meta {
        font-size: 0.82rem;
        color: #475569;
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    .bank-balance-box {
        background: rgba(248, 250, 252, 0.9);
        border-radius: 12px;
        padding: 0.85rem 1rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1rem;
    }
    .bank-balance-label {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .bank-balance-amount {
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .bank-card-actions {
        display: flex;
        gap: 0.5rem;
    }
    .bank-card-actions .inv-btn {
        flex: 1;
    }

    /* Fixed Screen Table System (Report Style) */
    .inv-table-panel {
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.04);
        margin-top: 0.5rem;
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }
    .inv-table-scroll {
        overflow-y: auto;
        overflow-x: auto;
        flex: 1;
        min-height: 0;
    }
    .financial-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }
    .financial-table thead {
        position: sticky;
        top: 0;
        background: #f8fafc;
        z-index: 10;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .financial-table th {
        padding: 10px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #64748b;
        white-space: nowrap;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .financial-table tbody tr {
        height: 48px;
        min-height: 48px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.12s ease;
    }
    .financial-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
    .financial-table tbody tr:nth-child(odd)  { background: #ffffff; }
    .financial-table tbody tr:hover            { background: #f1f5f9 !important; }

    .financial-table td {
        height: 48px;
        padding: 0 14px;
        font-size: 12.5px;
        color: #1e293b;
        vertical-align: middle;
        box-sizing: border-box;
    }
    .financial-table tfoot {
        background: #f8fafc;
        font-weight: 800;
        border-top: 2px solid #cbd5e1;
    }
    .financial-table tfoot td {
        height: 48px;
        padding: 0 14px;
        font-size: 13px;
        color: #0f172a;
        vertical-align: middle;
    }

    .col-right {
        text-align: right !important;
    }
    .col-center {
        text-align: center !important;
    }

    /* Code Pills */
    .inv-code-pill {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.74rem;
        background: #f1f5f9;
        color: #334155;
        padding: 2px 6px;
        border-radius: 6px;
        font-weight: 600;
    }

    /* Form Controls */
    .inv-input, .inv-select {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-size: 0.82rem;
        font-family: inherit;
        color: #0f172a;
        background: #ffffff;
        transition: all 0.15s ease;
        box-sizing: border-box;
    }
    .inv-input:focus, .inv-select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .inv-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #475569;
        margin-bottom: 0.35rem;
        display: block;
    }

    /* Modal Styling */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1100;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: #ffffff;
        border-radius: 18px;
        width: 90%;
        max-width: 580px;
        padding: 1.75rem;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-sizing: border-box;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .modal-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; }
    .modal-close {
        background: rgba(241, 245, 249, 0.8);
        border: 1px solid #cbd5e1;
        width: 32px; height: 32px;
        border-radius: 50%;
        font-size: 1.2rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #475569;
    }

    /* Pagination Footer Bar */
    .table-pagination-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        font-size: 12px;
        color: #64748b;
        flex-shrink: 0;
    }

    /* Print Stylesheet */
    @media print {
        .no-print, #curtiss-floating-header-right, .app-sidebar, .app-header, .inv-header {
            display: none !important;
        }
        body, .app-container, .app-main, .app-content {
            background: #ffffff !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .inv-card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
        .inv-table-scroll {
            max-height: none !important;
            overflow: visible !important;
        }
        .financial-table th, .financial-table td {
            padding: 6px 8px !important;
            font-size: 10pt !important;
        }
    }
</style>

<!-- Main Fixed Screen Viewport Container -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- ========================================================================= -->
    <!-- 1. BANK ACCOUNTS HUB VIEW (MAIN LANDING) -->
    <!-- ========================================================================= -->
    <div id="bankAccountsHubView" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
        <!-- Page Header (Headline Only) -->
        <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">
                    <?= (($view ?? 'active') === 'deleted') ? 'Bank Accounts - Trash Bin' : 'Bank Accounts & Cash Deposits' ?>
                </h1>
            </div>
        </div>

        <?php if (($view ?? 'active') !== 'deleted'): ?>
        <!-- KPI Summary Cards -->
        <div class="bank-kpi-grid">
            <div class="bank-kpi-card balance">
                <div class="bank-kpi-title">Total Company Bank Balance (LKR)</div>
                <div class="bank-kpi-value"><?= number_format($totalBankFunds, 2) ?></div>
                <div class="bank-kpi-sub">Across <?= $activeAccountCount ?> active bank accounts</div>
            </div>
            <div class="bank-kpi-card accounts">
                <div class="bank-kpi-title">Active Bank Accounts</div>
                <div class="bank-kpi-value"><?= $activeAccountCount ?></div>
                <div class="bank-kpi-sub">Linked to dedicated COA accounts</div>
            </div>
            <div class="bank-kpi-card">
                <div class="bank-kpi-title">POS Drawer Status</div>
                <div class="bank-kpi-value" style="font-size: 1.25rem;">
                    <?= $activeDay ? '<span style="color:#16a34a;">Open (' . htmlspecialchars($activeDay['business_date']) . ')</span>' : '<span style="color:#ef4444;">No Active Day</span>' ?>
                </div>
                <div class="bank-kpi-sub"><?= count($closedDays) ?> past closed days eligible for deposit</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bank Accounts Cards Grid -->
        <div class="bank-grid" id="bankAccountsCardsContainer">
            <?php if (empty($bankAccounts)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #94a3b8; background: #ffffff; border-radius: 16px; border: 1px dashed #cbd5e1;">
                <?= (($view ?? 'active') === 'deleted') ? 'Trash Bin is empty. No soft-deleted bank accounts.' : 'No bank accounts found.' ?>
            </div>
            <?php endif; ?>

            <?php foreach ($bankAccounts as $acc): ?>
            <div class="bank-card" id="bank-card-<?= $acc['id'] ?>" data-account-id="<?= $acc['id'] ?>">
                <div>
                    <div class="bank-card-header">
                        <div>
                            <div class="bank-name search-target"><?= htmlspecialchars($acc['account_name']) ?></div>
                            <div class="search-target" style="font-size: 0.8rem; color: #64748b; font-weight: 700; margin-top: 2px;"><?= htmlspecialchars($acc['bank_name']) ?></div>
                        </div>
                        <?php if (empty($acc['deleted_at'])): ?>
                        <span class="prod-badge <?= $acc['status'] === 'active' ? 'prod-badge-active' : 'prod-badge-inactive' ?>" onclick="event.stopPropagation(); toggleStatus(<?= $acc['id'] ?>);" style="cursor: pointer;" title="Click to toggle status">
                            <span class="dot"></span><?= $acc['status'] === 'active' ? 'Active' : 'Deactive' ?>
                        </span>
                        <?php else: ?>
                        <span class="prod-badge prod-badge-inactive"><span class="dot"></span>Deleted</span>
                        <?php endif; ?>
                    </div>

                    <div class="bank-acc-num search-target">A/C: <?= htmlspecialchars($acc['account_number']) ?></div>

                    <div class="bank-meta">
                        <div><strong>Branch:</strong> <span class="search-target"><?= htmlspecialchars($acc['bank_branch_name'] ?? $acc['branch_name'] ?? 'Company-Wide / Head Office') ?></span></div>
                        <div><strong>COA Account:</strong> <span class="inv-code-pill search-target"><?= htmlspecialchars($acc['account_code']) ?></span> <span class="search-target"><?= htmlspecialchars($acc['coa_account_name'] ?? $acc['account_name']) ?></span></div>
                    </div>
                </div>

                <div>
                    <div class="bank-balance-box">
                        <div class="bank-balance-label">Current Book Balance (GL) (LKR)</div>
                        <div class="bank-balance-amount search-target"><?= number_format($acc['current_balance'], 2) ?></div>
                    </div>

                    <div class="bank-card-actions">
                        <?php if (empty($acc['deleted_at'])): ?>
                        <button type="button" class="inv-btn inv-btn-ghost" onclick="openRegisterFullPage(<?= $acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['account_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['bank_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['account_number'])) ?>', '<?= htmlspecialchars($acc['account_code']) ?>')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            Register
                        </button>
                        <?php if (Auth::hasPermission('manage_bank_accounts')): ?>
                        <button type="button" class="inv-btn inv-btn-ghost" onclick='editAccount(<?= json_encode($acc) ?>)'>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Edit
                        </button>
                        <button type="button" class="inv-btn" style="color: #dc2626; background: #fef2f2; border-color: #fca5a5;" onclick="deleteAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['account_name'])) ?>')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Delete
                        </button>
                        <?php endif; ?>
                        <?php else: ?>
                        <?php if (Auth::hasPermission('manage_bank_accounts')): ?>
                        <button type="button" class="inv-btn" style="color: #15803d; background: #f0fdf4; border-color: #86efac;" onclick="restoreAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['account_name'])) ?>')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                            Restore Account
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- 2. FULL-PAGE BANK REGISTER LEDGER REPORT VIEW -->
    <!-- ========================================================================= -->
    <div id="bankRegisterReportView" style="display: none; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
        <!-- Report Header Title Card -->
        <div class="inv-card" style="flex-shrink: 0; margin-bottom: 0.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h1 id="registerReportTitle" style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0;">Bank Activity Register Ledger Report</h1>
                    <div id="registerReportMeta" style="font-size: 0.82rem; color: #64748b; margin-top: 4px;">Loading account details...</div>
                </div>
                <div id="registerReportDateBadge" style="background: #f1f5f9; padding: 6px 14px; border-radius: 999px; font-weight: 700; font-size: 0.8rem; color: #334155; border: 1px solid #cbd5e1;">
                    All Historical Activity
                </div>
            </div>
        </div>

        <!-- Fixed-to-Screen Table System -->
        <div class="inv-table-panel">
            <div class="inv-table-scroll">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th style="width: 140px; text-align: left;">Date & Time</th>
                            <th style="width: 130px; text-align: left;">Entry #</th>
                            <th style="text-align: left;">Description / Memo</th>
                            <th class="col-right" style="width: 150px;">Deposit (+) (LKR)</th>
                            <th class="col-right" style="width: 150px;">Payment (-) (LKR)</th>
                            <th class="col-right" style="width: 170px;">Running Balance (LKR)</th>
                        </tr>
                    </thead>
                    <tbody id="registerReportTbody">
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 3rem 1rem;">Loading bank register activity ledger...</td>
                        </tr>
                    </tbody>
                    <tfoot id="registerReportTfoot" style="display: none;">
                        <tr>
                            <td colspan="3" style="text-transform: uppercase; letter-spacing: 0.05em; font-weight: 800;">Total Bank Activity Balance (LKR)</td>
                            <td id="registerTotalDeposits" class="col-right" style="color: #16a34a; font-weight: 800;">0.00</td>
                            <td id="registerTotalPayments" class="col-right" style="color: #dc2626; font-weight: 800;">0.00</td>
                            <td id="registerFinalBalance" class="col-right" style="font-size: 1rem; color: #0f172a; font-weight: 800;">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Table Pagination Controls -->
            <div class="table-pagination-bar">
                <div id="registerPaginationCount">Showing 0 to 0 of 0 entries</div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span>Rows:</span>
                        <select id="registerPageLimit" onchange="changeRegisterPageLimit(this.value)" style="height: 30px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; padding: 0 6px;">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <button type="button" class="inv-btn inv-btn-ghost" id="regPrevPageBtn" onclick="prevRegisterPage()" style="height: 30px; padding: 0 10px; font-size: 11px;">Prev</button>
                    <span id="registerPageIndicator" style="font-weight: 700;">Page 1 of 1</span>
                    <button type="button" class="inv-btn inv-btn-ghost" id="regNextPageBtn" onclick="nextRegisterPage()" style="height: 30px; padding: 0 10px; font-size: 11px;">Next</button>
                </div>
            </div>
        </div>
    </div>

</div>


<!-- ========================================================================= -->
<!-- MODALS -->
<!-- ========================================================================= -->

<!-- Modal: Add / Edit Bank Account -->
<div class="modal-overlay" id="accountModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="accountModalTitle">Add New Bank Account</div>
            <button class="modal-close" onclick="closeModal('accountModal')">&times;</button>
        </div>
        <form id="accountForm" onsubmit="handleSaveAccount(event)">
            <input type="hidden" name="id" id="acc_id" value="">
            <input type="hidden" name="account_id" id="acc_account_id" value="">
            <div style="margin-bottom: 1rem;">
                <label class="inv-label">Bank Name *</label>
                <input type="text" class="inv-input" name="bank_name" id="acc_bank_name" required placeholder="e.g. Commercial Bank, Sampath Bank">
            </div>
            <div style="margin-bottom: 1rem;">
                <label class="inv-label">Account Name *</label>
                <input type="text" class="inv-input" name="account_name" id="acc_account_name" required placeholder="e.g. Main Operational Account">
            </div>
            <div style="margin-bottom: 1rem;">
                <label class="inv-label">Account Number *</label>
                <input type="text" class="inv-input" name="account_number" id="acc_account_number" required placeholder="e.g. 8009910022">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="inv-label">Company Branch</label>
                    <select class="inv-select curtiss-searchable" name="branch_id" id="acc_branch_id">
                        <option value="">Company-Wide (Shared)</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="inv-label">Bank Branch Name</label>
                    <input type="text" class="inv-input" name="bank_branch_name" id="acc_bank_branch_name" placeholder="e.g. Kandy Main Branch">
                </div>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label class="inv-label">Opening Balance (LKR)</label>
                <input type="number" step="0.01" class="inv-input" name="opening_balance" id="acc_opening_balance" value="0.00">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('accountModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="saveAccountBtn">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Cash Deposit -->
<div class="modal-overlay" id="depositModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Record Cash Deposit to Bank</div>
            <button class="modal-close" onclick="closeModal('depositModal')">&times;</button>
        </div>
        <form id="depositForm" onsubmit="handleCreateDeposit(event)">
            <div style="margin-bottom: 1rem;">
                <label class="inv-label">Target Bank Account *</label>
                <select class="inv-select curtiss-searchable" name="bank_account_id" id="dep_bank_account_id" required>
                    <option value="">-- Select Bank Account --</option>
                    <?php foreach ($bankAccounts as $ba): if ($ba['status'] === 'active' && empty($ba['deleted_at'])): ?>
                    <option value="<?= $ba['id'] ?>"><?= htmlspecialchars($ba['bank_name'] . ' - ' . $ba['account_name'] . ' (' . $ba['account_number'] . ')') ?></option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label class="inv-label">Deposit Cash Source *</label>
                <select class="inv-select" name="source_type" id="dep_source_type" onchange="toggleSourceFields()" required>
                    <option value="active_drawer">Active POS Drawer (Records Cash Out movement)</option>
                    <option value="closed_day">Closed Business Day Cash (No drawer duplicate)</option>
                    <option value="other_cash">Other Direct Cash</option>
                </select>
            </div>
            <div id="closed_day_group" style="margin-bottom: 1rem; display: none;">
                <label class="inv-label">Select Closed Business Day *</label>
                <select class="inv-select curtiss-searchable" name="business_day_id" id="dep_business_day_id">
                    <option value="">-- Select Closed Business Day --</option>
                    <?php foreach ($closedDays as $cd): ?>
                    <option value="<?= $cd['id'] ?>">Date: <?= htmlspecialchars($cd['business_date']) ?> (Pending Cash: LKR <?= number_format($cd['cash_to_deposit'], 2) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="inv-label">Deposit Amount (LKR) *</label>
                    <input type="number" step="0.01" class="inv-input" name="amount" id="dep_amount" required placeholder="0.00">
                </div>
                <div>
                    <label class="inv-label">Deposit Slip / Ref #</label>
                    <input type="text" class="inv-input" name="reference_no" id="dep_reference_no" placeholder="e.g. SLIP-99881">
                </div>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label class="inv-label">Deposit Date</label>
                <input type="date" class="inv-input" name="deposit_date" id="dep_deposit_date" value="<?= date('Y-m-d') ?>">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label class="inv-label">Notes / Remarks</label>
                <textarea class="inv-input" name="notes" id="dep_notes" style="height: auto; padding: 8px 12px;" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('depositModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="depositBtn" style="background:#16a34a; border-color:#16a34a;">Submit Cash Deposit</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Register Filter Scope -->
<div class="modal-overlay" id="registerFilterModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Filter Register Ledger</div>
            <button class="modal-close" onclick="closeModal('registerFilterModal')">&times;</button>
        </div>
        <form onsubmit="applyRegisterFilter(event)">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="inv-label">Start Date</label>
                    <input type="date" id="regFilterStartDate" class="inv-input">
                </div>
                <div>
                    <label class="inv-label">End Date</label>
                    <input type="date" id="regFilterEndDate" class="inv-input">
                </div>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label class="inv-label">Search Entry / Ref / Description</label>
                <input type="text" id="regFilterSearch" class="inv-input" placeholder="Keyword search...">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="resetRegisterFilter()">Reset</button>
                <button type="submit" class="inv-btn inv-btn-primary">Apply Filter Scope</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Register Export Options -->
<div class="modal-overlay" id="registerExportModal">
    <div class="modal-box" style="max-width: 440px;">
        <div class="modal-header">
            <div class="modal-title">Export Bank Register Ledger</div>
            <button class="modal-close" onclick="closeModal('registerExportModal')">&times;</button>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <button type="button" class="inv-btn inv-btn-ghost" style="padding: 12px 16px; justify-content: flex-start; gap: 10px;" onclick="exportRegisterFile('excel')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Export as Excel Spreadsheet (.CSV)</span>
            </button>
            <button type="button" class="inv-btn inv-btn-ghost" style="padding: 12px 16px; justify-content: flex-start; gap: 10px;" onclick="exportRegisterFile('json')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                <span>Export as Raw JSON Data</span>
            </button>
        </div>
    </div>
</div>


<script>
    let activeRegisterAccountId = <?= (int)($registerAccountId ?? 0) ?>;
    let activeRegisterAccountData = null;
    let currentRegisterItems = [];
    let currentRegisterPage = 1;
    let currentRegisterLimit = 25;
    let currentRegisterTotalPages = 1;
    let currentRegisterTotalItems = 0;

    // Attach CurtissSearchableSelect and setup keyboard focus
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.curtiss-searchable').forEach(el => {
            if (typeof CurtissSearchableSelect !== 'undefined') {
                CurtissSearchableSelect.attach(el);
            }
        });

        // Setup Floating Navigation Realtime Search
        if (typeof CurtissNav !== 'undefined' && CurtissNav.enablePageSearch) {
            CurtissNav.enablePageSearch((query) => {
                const term = (query || '').toLowerCase().trim();
                
                // If on Hub View: search bank cards
                const hubView = document.getElementById('bankAccountsHubView');
                if (hubView && hubView.style.display !== 'none') {
                    const cards = document.querySelectorAll('.bank-card');
                    cards.forEach(card => {
                        if (!term) {
                            card.style.display = '';
                            return;
                        }
                        // Query displayed data fields only, excluding action buttons
                        const searchTargets = card.querySelectorAll('.search-target');
                        let match = false;
                        searchTargets.forEach(t => {
                            if (t.innerText.toLowerCase().includes(term)) {
                                match = true;
                            }
                        });
                        card.style.display = match ? '' : 'none';
                    });
                }

                // If on Register View: pass search to register fetcher
                const regView = document.getElementById('bankRegisterReportView');
                if (regView && regView.style.display !== 'none') {
                    document.getElementById('regFilterSearch').value = query || '';
                    currentRegisterPage = 1;
                    fetchRegisterReportData();
                }
            });
        }

        // Setup Enter key auto-advance on modals
        setupModalEnterAdvancing('accountModal');
        setupModalEnterAdvancing('depositModal');

        // Auto load full page register if parameter was in URL
        if (activeRegisterAccountId > 0) {
            const accs = <?= json_encode($bankAccounts) ?>;
            const targetAcc = accs.find(a => parseInt(a.id) === activeRegisterAccountId);
            if (targetAcc) {
                openRegisterFullPage(targetAcc.id, targetAcc.account_name, targetAcc.bank_name, targetAcc.account_number, targetAcc.account_code);
            }
        }
    });

    function setupModalEnterAdvancing(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        const inputs = Array.from(modal.querySelectorAll('input:not([type="hidden"]), select, textarea'));
        inputs.forEach((input, idx) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    if (idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                        if (inputs[idx + 1].select) inputs[idx + 1].select();
                    } else {
                        const submitBtn = modal.querySelector('button[type="submit"]');
                        if (submitBtn) submitBtn.click();
                    }
                }
            });
        });
    }

    function closeModal(id) {
        document.getElementById(id)?.classList.remove('active');
    }

    function openAccountModal() {
        document.getElementById('accountForm').reset();
        document.getElementById('acc_id').value = '';
        document.getElementById('acc_account_id').value = '';
        document.getElementById('accountModalTitle').innerText = 'Add New Bank Account';
        document.getElementById('accountModal').classList.add('active');
        setTimeout(() => {
            document.getElementById('acc_bank_name')?.focus();
        }, 100);
    }

    function editAccount(acc) {
        document.getElementById('acc_id').value = acc.id;
        document.getElementById('acc_account_id').value = acc.account_id || '';
        document.getElementById('acc_bank_name').value = acc.bank_name;
        document.getElementById('acc_account_name').value = acc.account_name;
        document.getElementById('acc_account_number').value = acc.account_number;
        document.getElementById('acc_branch_id').value = acc.branch_id || '';
        document.getElementById('acc_bank_branch_name').value = acc.bank_branch_name || '';
        document.getElementById('acc_opening_balance').value = acc.opening_balance;
        document.getElementById('accountModalTitle').innerText = 'Edit Bank Account';
        document.getElementById('accountModal').classList.add('active');
        setTimeout(() => {
            document.getElementById('acc_bank_name')?.focus();
        }, 100);
    }

    async function handleSaveAccount(e) {
        e.preventDefault();
        const btn = document.getElementById('saveAccountBtn');
        btn.disabled = true;
        btn.innerText = 'Saving...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('banking_api.php?action=save_account', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (err) {
            alert('Server connection error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Save Account';
        }
    }

    async function deleteAccount(id, name) {
        if (!confirm(`Are you sure you want to move bank account "${name}" to Trash Bin?`)) {
            return;
        }

        try {
            const res = await fetch('banking_api.php?action=delete_account', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (err) {
            alert('Server error: ' + err.message);
        }
    }

    async function restoreAccount(id, name) {
        if (!confirm(`Restore bank account "${name}" back to Active status?`)) {
            return;
        }

        try {
            const res = await fetch('banking_api.php?action=restore_account', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (err) {
            alert('Server error: ' + err.message);
        }
    }

    async function toggleStatus(id) {
        try {
            const res = await fetch('banking_api.php?action=toggle_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (err) {
            alert('Server connection error: ' + err.message);
        }
    }

    function openDepositModal() {
        document.getElementById('depositForm').reset();
        toggleSourceFields();
        document.getElementById('depositModal').classList.add('active');
        setTimeout(() => {
            document.getElementById('dep_amount')?.focus();
        }, 100);
    }

    function toggleSourceFields() {
        const src = document.getElementById('dep_source_type').value;
        const closedGrp = document.getElementById('closed_day_group');
        if (src === 'closed_day') {
            closedGrp.style.display = 'block';
            document.getElementById('dep_business_day_id').required = true;
        } else {
            closedGrp.style.display = 'none';
            document.getElementById('dep_business_day_id').required = false;
        }
    }

    async function handleCreateDeposit(e) {
        e.preventDefault();
        const btn = document.getElementById('depositBtn');
        btn.disabled = true;
        btn.innerText = 'Processing...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('banking_api.php?action=create_deposit', {
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
            alert('Server connection error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Submit Cash Deposit';
        }
    }

    /* ── FULL-PAGE BANK REGISTER LEDGER ENGINE ── */
    function openRegisterFullPage(id, accName, bankName, accNum, accCode) {
        activeRegisterAccountId = id;
        activeRegisterAccountData = { id, accName, bankName, accNum, accCode };
        currentRegisterPage = 1;

        document.getElementById('bankAccountsHubView').style.display = 'none';
        document.getElementById('bankRegisterReportView').style.display = 'flex';
        document.getElementById('hubHeaderActions').style.display = 'none';
        document.getElementById('registerHeaderActions').style.display = 'flex';

        document.getElementById('registerReportTitle').innerText = `Bank Activity Register: ${accName}`;
        document.getElementById('registerReportMeta').innerHTML = `
            <strong>Bank:</strong> ${bankName} | <strong>A/C:</strong> <span class="inv-code-pill">${accNum}</span> | <strong>COA Code:</strong> ${accCode}
        `;

        if (window.history && window.history.pushState) {
            window.history.pushState(null, '', `bank_accounts.php?register_id=${id}`);
        }

        fetchRegisterReportData();
    }

    function closeRegisterFullPage() {
        document.getElementById('bankAccountsHubView').style.display = 'flex';
        document.getElementById('bankRegisterReportView').style.display = 'none';
        document.getElementById('hubHeaderActions').style.display = 'flex';
        document.getElementById('registerHeaderActions').style.display = 'none';

        if (window.history && window.history.pushState) {
            window.history.pushState(null, '', 'bank_accounts.php');
        }
    }

    function changeRegisterPageLimit(val) {
        currentRegisterLimit = parseInt(val) || 25;
        currentRegisterPage = 1;
        fetchRegisterReportData();
    }

    function prevRegisterPage() {
        if (currentRegisterPage > 1) {
            currentRegisterPage--;
            fetchRegisterReportData();
        }
    }

    function nextRegisterPage() {
        if (currentRegisterPage < currentRegisterTotalPages) {
            currentRegisterPage++;
            fetchRegisterReportData();
        }
    }

    async function fetchRegisterReportData() {
        const tbody = document.getElementById('registerReportTbody');
        const tfoot = document.getElementById('registerReportTfoot');
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 3rem 1rem; color:#64748b;">Loading full bank register activity ledger...</td></tr>';
        tfoot.style.display = 'none';

        const startDate = document.getElementById('regFilterStartDate').value;
        const endDate = document.getElementById('regFilterEndDate').value;
        const search = document.getElementById('regFilterSearch').value;

        let url = `banking_api.php?action=get_register&bank_account_id=${activeRegisterAccountId}&page=${currentRegisterPage}&limit=${currentRegisterLimit}`;
        if (startDate) url += `&start_date=${encodeURIComponent(startDate)}`;
        if (endDate) url += `&end_date=${encodeURIComponent(endDate)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        try {
            const res = await fetch(url);
            const result = await res.json();

            if (result.success && result.data) {
                currentRegisterItems = result.data.items || [];
                currentRegisterTotalItems = result.data.totalItems || 0;
                currentRegisterTotalPages = result.data.totalPages || 1;
                currentRegisterPage = result.data.page || 1;

                // Update pagination controls
                const startIdx = currentRegisterTotalItems > 0 ? (currentRegisterPage - 1) * currentRegisterLimit + 1 : 0;
                const endIdx = Math.min(currentRegisterPage * currentRegisterLimit, currentRegisterTotalItems);
                document.getElementById('registerPaginationCount').innerText = `Showing ${startIdx} to ${endIdx} of ${currentRegisterTotalItems} entries`;
                document.getElementById('registerPageIndicator').innerText = `Page ${currentRegisterPage} of ${currentRegisterTotalPages}`;
                document.getElementById('regPrevPageBtn').disabled = (currentRegisterPage <= 1);
                document.getElementById('regNextPageBtn').disabled = (currentRegisterPage >= currentRegisterTotalPages);

                if (!currentRegisterItems.length) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 3rem 1rem; color:#94a3b8;">No transactions found matching the filter scope.</td></tr>';
                    return;
                }

                let totDeposits = 0;
                let totPayments = 0;
                let lastRunningBal = 0;

                tbody.innerHTML = currentRegisterItems.map(it => {
                    const dep = parseFloat(it.deposit_in) || 0;
                    const pay = parseFloat(it.payment_out) || 0;
                    const bal = parseFloat(it.running_balance) || 0;
                    totDeposits += dep;
                    totPayments += pay;
                    lastRunningBal = bal;

                    return `
                        <tr>
                            <td style="color:#64748b; font-size:12px; text-align:left;">${it.entry_date}</td>
                            <td style="text-align:left;"><span class="inv-code-pill">${it.entry_number}</span></td>
                            <td style="text-align:left;"><strong>${it.description}</strong> ${it.memo ? '<br><small style="color:#94a3b8;">' + it.memo + '</small>' : ''}</td>
                            <td class="col-right" style="color:#16a34a; font-weight:700;">${dep > 0 ? dep.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'}</td>
                            <td class="col-right" style="color:#dc2626; font-weight:700;">${pay > 0 ? pay.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'}</td>
                            <td class="col-right" style="font-weight:800; color:#0f172a;">${bal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        </tr>
                    `;
                }).join('');

                document.getElementById('registerTotalDeposits').innerText = totDeposits.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
                document.getElementById('registerTotalPayments').innerText = totPayments.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
                document.getElementById('registerFinalBalance').innerText = lastRunningBal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
                tfoot.style.display = 'table-footer-group';
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 3rem 1rem; color:#dc2626;">Failed to load register ledger: ${err.message}</td></tr>`;
        }
    }

    function openRegisterFilterModal() {
        document.getElementById('registerFilterModal').classList.add('active');
    }

    function applyRegisterFilter(e) {
        e.preventDefault();
        closeModal('registerFilterModal');
        const startDate = document.getElementById('regFilterStartDate').value;
        const endDate = document.getElementById('regFilterEndDate').value;
        if (startDate || endDate) {
            document.getElementById('registerReportDateBadge').innerText = `${startDate || 'Start'} to ${endDate || 'Today'}`;
        }
        currentRegisterPage = 1;
        fetchRegisterReportData();
    }

    function resetRegisterFilter() {
        document.getElementById('regFilterStartDate').value = '';
        document.getElementById('regFilterEndDate').value = '';
        document.getElementById('regFilterSearch').value = '';
        document.getElementById('registerReportDateBadge').innerText = 'All Historical Activity';
        closeModal('registerFilterModal');
        currentRegisterPage = 1;
        fetchRegisterReportData();
    }

    function openRegisterExportModal() {
        document.getElementById('registerExportModal').classList.add('active');
    }

    function exportRegisterFile(format) {
        if (!currentRegisterItems.length) {
            alert('No register items available to export.');
            return;
        }

        if (format === 'json') {
            const blob = new Blob([JSON.stringify(currentRegisterItems, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `bank_register_${activeRegisterAccountId}_${new Date().toISOString().slice(0,10)}.json`;
            a.click();
        } else if (format === 'excel') {
            let csv = '\uFEFF';
            csv += 'Date,Entry Number,Description,Memo,Deposit (+) (LKR),Payment (-) (LKR),Running Balance (LKR)\n';
            currentRegisterItems.forEach(i => {
                csv += `"${i.entry_date}","${i.entry_number}","${(i.description||'').replace(/"/g, '""')}","${(i.memo||'').replace(/"/g, '""')}",${i.deposit_in||0},${i.payment_out||0},${i.running_balance||0}\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `bank_register_${activeRegisterAccountId}_${new Date().toISOString().slice(0,10)}.csv`;
            a.click();
        }
        closeModal('registerExportModal');
    }
</script>
