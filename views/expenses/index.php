<?php
/**
 * Curtiss POS - Centralized Expense Management Master View
 * 
 * Features:
 * - Fixed screen viewport with internal sticky table scrolling
 * - 48px row heights & zebra striping
 * - Floating top-right header actions (+ New Expense, Trash Bin, Export)
 * - Bottom floating navigation realtime search via CurtissNav.enablePageSearch
 * - Searchable dropdown comboboxes via CurtissSearchableSelect
 * - Interactive modals for Expense Entry, Details/Voucher, Bill Payment, and Reversals
 * - Full keyboard navigation (Enter key field focus) & double-submit protection
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Header Action Controls -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (Auth::hasPermission('expenses.create')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openNewExpenseModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        + New Expense
    </button>
    <?php endif; ?>

    <button type="button" id="btnToggleTrash" class="inv-btn inv-btn-trash" onclick="toggleTrashView()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
        <span id="trashBtnText">Trash Bin</span>
    </button>

    <?php if (Auth::hasPermission('expenses.export')): ?>
    <button type="button" class="inv-btn inv-btn-ghost" onclick="exportExpensesCsv()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </button>
    <?php endif; ?>
</div>

<style>
    /* Fixed Screen Viewport Container */
    .exp-main-container {
        width: 100%;
        max-width: 100%;
        height: calc(100vh - 110px);
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
        overflow: hidden;
        padding-bottom: 8px;
    }

    /* Page Header */
    .inv-header {
        flex-shrink: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
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

    /* Action Buttons */
    .inv-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 16px;
        height: 34px;
        min-height: 34px;
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
    .inv-btn-primary {
        background: #0f172a;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
    }
    .inv-btn-primary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }
    .inv-btn-ghost {
        background: rgba(255, 255, 255, 0.95);
        border-color: rgba(226, 232, 240, 0.9);
        color: #0f172a;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    .inv-btn-ghost:hover {
        background: #ffffff;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .inv-btn-trash {
        color: #dc2626;
        background: #fef2f2;
        border-color: #fca5a5;
    }
    .inv-btn-trash:hover {
        background: #fee2e2;
        border-color: #f87171;
    }

    /* KPI Summary Cards Grid */
    .exp-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.75rem;
        margin-bottom: 0.85rem;
        flex-shrink: 0;
    }
    .exp-kpi-card {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .exp-kpi-title {
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .exp-kpi-val {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    /* Table Panel & Scrolling */
    .prod-table-panel {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }
    .prod-table-scroll {
        overflow-y: auto;
        flex: 1;
        min-height: 0;
    }
    .prod-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        text-align: left;
    }
    .prod-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc;
        padding: 10px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .prod-table tbody tr {
        height: 48px;
        min-height: 48px;
        border-bottom: 1px solid #f1f5f9;
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
    }
    .col-right { text-align: right !important; }
    .col-center { text-align: center !important; }

    /* Theme-matched Pills & Badges (28px height) */
    .exp-badge {
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
        box-sizing: border-box;
        white-space: nowrap;
    }
    .exp-badge-posted { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .exp-badge-draft  { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .exp-badge-unpaid { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    .exp-badge-partial{ background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .exp-badge-reversed{ background: #f3f4f6; color: #9ca3af; text-decoration: line-through; border: 1px solid #e5e7eb; }

    .exp-method-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 10.5px;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
    }

    /* Modal System */
    .exp-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .exp-modal-overlay.active { display: flex; }
    .exp-modal-box {
        background: #ffffff;
        border-radius: 16px;
        width: 100%;
        max-width: 680px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }
    .exp-modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }
    .exp-modal-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; }
    .exp-modal-close {
        background: none; border: none; cursor: pointer; color: #64748b; font-size: 20px; line-height: 1; padding: 4px;
    }
    .exp-modal-close:hover { color: #0f172a; }
    .exp-modal-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }
    .exp-modal-footer {
        padding: 14px 20px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    /* Form Layout */
    .exp-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
    .exp-form-full { grid-column: span 2; }
    .exp-form-group { display: flex; flex-direction: column; gap: 5px; }
    .exp-label { font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; }
    .exp-input, .exp-select, .exp-textarea {
        height: 38px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        font-family: inherit;
        box-sizing: border-box;
    }
    .exp-textarea { height: 70px; padding: 8px 12px; resize: vertical; }
    .exp-input:focus, .exp-select:focus, .exp-textarea:focus {
        outline: none;
        border-color: #0f172a;
        box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.1);
    }

    /* Pagination Footer */
    .exp-pagination-bar {
        padding: 10px 16px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        font-size: 12px;
        color: #64748b;
    }
</style>

<div class="exp-main-container">
    <!-- Page Header (Headline Only) & Inline Top-Right Filter Controls -->
    <div class="inv-header">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Expense Management</h1>
        </div>

        <!-- Inline Filters (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <?php if (count($branches) > 1): ?>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Branch:</span>
                <select id="filterBranch" onchange="loadExpenses(1)" style="height: 34px; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 0 12px; border: 1px solid #cbd5e1;">
                    <option value="all">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                    <option value="<?= (int)$b['id'] ?>" <?= ((int)$b['id'] === $branchId) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Category:</span>
                <select id="filterCategory" onchange="loadExpenses(1)" style="height: 34px; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 0 12px; border: 1px solid #cbd5e1;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Payment:</span>
                <select id="filterMethod" onchange="loadExpenses(1)" style="height: 34px; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 0 12px; border: 1px solid #cbd5e1;">
                    <option value="">All Payment Sources</option>
                    <option value="cash_drawer">Cash Drawer</option>
                    <option value="cash_in_hand">Cash in Hand</option>
                    <option value="petty_cash">Petty Cash</option>
                    <option value="bank">Bank Account</option>
                    <option value="unpaid">Unpaid Bills</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterStatus" onchange="loadExpenses(1)" style="height: 34px; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 0 12px; border: 1px solid #cbd5e1;">
                    <option value="">All Statuses</option>
                    <option value="posted">Posted</option>
                    <option value="draft">Draft</option>
                    <option value="paid">Fully Paid</option>
                    <option value="partially_paid">Partially Paid</option>
                    <option value="reversed">Reversed</option>
                </select>
            </div>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="exp-kpi-grid">
        <div class="exp-kpi-card">
            <span class="exp-kpi-title">Total Recorded</span>
            <span class="exp-kpi-val" id="kpiTotalAmount">LKR <?= number_format($stats['total_amount'], 2) ?></span>
        </div>
        <div class="exp-kpi-card">
            <span class="exp-kpi-title">From Cash Drawer</span>
            <span class="exp-kpi-val" id="kpiDrawerAmount" style="color: #0284c7;">LKR <?= number_format($stats['cash_drawer_amount'], 2) ?></span>
        </div>
        <div class="exp-kpi-card">
            <span class="exp-kpi-title">From Bank Accounts</span>
            <span class="exp-kpi-val" id="kpiBankAmount" style="color: #7c3aed;">LKR <?= number_format($stats['bank_amount'], 2) ?></span>
        </div>
        <div class="exp-kpi-card">
            <span class="exp-kpi-title">From Main Safe</span>
            <span class="exp-kpi-val" id="kpiSafeAmount" style="color: #059669;">LKR <?= number_format($stats['cash_in_hand_amount'], 2) ?></span>
        </div>
        <div class="exp-kpi-card">
            <span class="exp-kpi-title">From Petty Cash</span>
            <span class="exp-kpi-val" id="kpiPettyAmount" style="color: #d97706;">LKR <?= number_format($stats['petty_cash_amount'], 2) ?></span>
        </div>
        <div class="exp-kpi-card">
            <span class="exp-kpi-title">Unpaid Bills</span>
            <span class="exp-kpi-val" id="kpiUnpaidAmount" style="color: #dc2626;">LKR <?= number_format($stats['unpaid_amount'], 2) ?></span>
        </div>
    </div>

    <!-- Data Table Panel & Internal Scroll -->
    <div class="prod-table-panel">
        <div class="prod-table-scroll">
            <table class="prod-table" id="expenseTable">
                <thead>
                    <tr>
                        <th style="width: 100px;">Expense #</th>
                        <th style="width: 90px;">Date</th>
                        <th style="width: 140px;">Category</th>
                        <th>Description & Purpose</th>
                        <th style="width: 140px;">Payee</th>
                        <th style="width: 140px;">Payment Source</th>
                        <th class="col-right" style="width: 110px;">Amount (LKR)</th>
                        <th class="col-center" style="width: 90px;">Status</th>
                        <th class="col-right" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="expenseTableBody">
                    <tr><td colspan="9" class="col-center" style="padding: 30px;">Loading expenses...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="exp-pagination-bar">
            <div id="paginationInfo">Showing 0 to 0 of 0 entries</div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b;">Per Page:
                    <select id="pageSizeSelect" onchange="changePageSize(this.value)" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 6px; font-size: 11px;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="75">75</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </label>
                <button type="button" class="inv-btn inv-btn-ghost" id="btnPrevPage" onclick="prevPage()" style="height: 28px; padding: 0 10px;">Previous</button>
                <span id="pageNumberDisplay" style="font-weight: 800; color: #0f172a;">1</span>
                <button type="button" class="inv-btn inv-btn-ghost" id="btnNextPage" onclick="nextPage()" style="height: 28px; padding: 0 10px;">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: ADD / EDIT EXPENSE -->
<div class="exp-modal-overlay" id="expenseEntryModal">
    <div class="exp-modal-box">
        <div class="exp-modal-header">
            <h3 class="exp-modal-title" id="entryModalTitle">+ Record New Expense</h3>
            <button type="button" class="exp-modal-close" onclick="closeExpenseModal()">&times;</button>
        </div>
        <form id="expenseForm" onsubmit="handleFormSubmit(this); return false;" enctype="multipart/form-data">
            <input type="hidden" id="expId" name="id" value="">
            <div class="exp-modal-body">
                <div class="exp-form-grid">
                    <div class="exp-form-group">
                        <label class="exp-label">Expense Date *</label>
                        <input type="date" id="expDate" name="expense_date" class="exp-input" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="exp-form-group">
                        <label class="exp-label">Expense Category *</label>
                        <select id="expCategory" name="category_id" class="exp-select" required onchange="handleCategoryChange(this.value)">
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" data-account-id="<?= (int)$cat['account_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="exp-form-group">
                        <label class="exp-label">PAYMENT ACCOUNT / METHOD *</label>
                        <select id="expPaymentMethod" name="payment_method" class="exp-select" required>
                            <option value="cash_in_hand">Cash in Hand (Main Safe)</option>
                            <?php if (!empty($eligibleAccounts['cash_accounts'])): ?>
                                <?php foreach ($eligibleAccounts['cash_accounts'] as $ca): ?>
                                    <?php if ($ca['account_code'] !== '1010'): ?>
                                    <option value="<?= htmlspecialchars($ca['account_name']) ?>"><?= htmlspecialchars($ca['account_name']) ?> (<?= htmlspecialchars($ca['account_code']) ?>)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <option value="cash_drawer">Cash Drawer (POS Register)</option>
                            <option value="petty_cash">Petty Cash Account</option>
                            <?php if (!empty($eligibleAccounts['bank_accounts'])): ?>
                                <?php foreach ($eligibleAccounts['bank_accounts'] as $ba): ?>
                                <option value="Bank:<?= (int)$ba['id'] ?>">Bank Transfer: <?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <option value="unpaid">Unpaid Bill / Accrued (2030)</option>
                        </select>
                    </div>

                    <div class="exp-form-group">
                        <label class="exp-label">Amount (LKR) *</label>
                        <input type="number" step="0.01" min="0.01" id="expAmount" name="amount" class="exp-input" placeholder="0.00" required>
                    </div>

                    <div class="exp-form-group">
                        <label class="exp-label">Payee / Paid To *</label>
                        <input type="text" id="expPayee" name="payee" class="exp-input" placeholder="e.g. CEB, Landlord, Sri Lanka Telecom" required>
                    </div>

                    <div class="exp-form-group">
                        <label class="exp-label">Bill / Invoice # (Optional)</label>
                        <input type="text" id="expInvoiceRef" name="invoice_ref" class="exp-input" placeholder="e.g. INV-904812">
                    </div>

                    <div class="exp-form-group exp-form-full">
                        <label class="exp-label">Description / Purpose *</label>
                        <input type="text" id="expDescription" name="description" class="exp-input" placeholder="Purpose of this expense" required>
                    </div>
                </div>
            </div>
            <div class="exp-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeExpenseModal()">Cancel</button>
                <button type="button" class="inv-btn inv-btn-ghost" id="btnSaveDraft" onclick="submitExpenseForm('draft')">Save as Draft</button>
                <button type="button" class="inv-btn inv-btn-primary" id="btnPostDirect" onclick="submitExpenseForm('posted')">Post Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: EXPENSE DETAILS & PRINTABLE VOUCHER -->
<div class="exp-modal-overlay" id="expenseDetailsModal">
    <div class="exp-modal-box" style="max-width: 650px;">
        <div class="exp-modal-header">
            <h3 class="exp-modal-title" id="detailsModalTitle">Expense Voucher</h3>
            <button type="button" class="exp-modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="exp-modal-body" id="detailsModalContent">
            <!-- Dynamically populated -->
        </div>
        <div class="exp-modal-footer" id="detailsModalFooter">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeDetailsModal()">Close</button>
            <button type="button" class="inv-btn inv-btn-ghost" id="btnPrintVoucher" onclick="printExpenseVoucher()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Voucher
            </button>
        </div>
    </div>
</div>

<!-- MODAL 3: RECORD PAYMENT (FOR UNPAID BILLS) -->
<div class="exp-modal-overlay" id="paymentModal">
    <div class="exp-modal-box" style="max-width: 480px;">
        <div class="exp-modal-header">
            <h3 class="exp-modal-title">Record Bill Payment</h3>
            <button type="button" class="exp-modal-close" onclick="closePaymentModal()">&times;</button>
        </div>
        <form id="paymentForm" onsubmit="submitPaymentForm(); return false;">
            <input type="hidden" id="payExpenseId" value="">
            <div class="exp-modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 14px;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700;">OUTSTANDING AMOUNT</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #b91c1c;" id="payRemainingDisplay">LKR 0.00</div>
                </div>

                <div class="exp-form-group" style="margin-bottom: 12px;">
                    <label class="exp-label">Payment Date *</label>
                    <input type="date" id="payDate" class="exp-input" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="exp-form-group" style="margin-bottom: 12px;">
                    <label class="exp-label">Amount (LKR) *</label>
                    <input type="number" step="0.01" min="0.01" id="payAmount" class="exp-input" placeholder="0.00" required>
                </div>

                <div class="exp-form-group" style="margin-bottom: 12px;">
                    <label class="exp-label">PAYMENT ACCOUNT / METHOD *</label>
                    <select id="payMethod" class="exp-select" required>
                        <option value="cash_in_hand">Cash in Hand (Main Safe)</option>
                        <?php if (!empty($eligibleAccounts['cash_accounts'])): ?>
                            <?php foreach ($eligibleAccounts['cash_accounts'] as $ca): ?>
                                <?php if ($ca['account_code'] !== '1010'): ?>
                                <option value="<?= htmlspecialchars($ca['account_name']) ?>"><?= htmlspecialchars($ca['account_name']) ?> (<?= htmlspecialchars($ca['account_code']) ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <option value="cash_drawer">Cash Drawer (POS Register)</option>
                        <option value="petty_cash">Petty Cash Account</option>
                        <?php if (!empty($eligibleAccounts['bank_accounts'])): ?>
                            <?php foreach ($eligibleAccounts['bank_accounts'] as $ba): ?>
                            <option value="Bank:<?= (int)$ba['id'] ?>">Bank Transfer: <?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="exp-form-group">
                    <label class="exp-label">Reference / Notes (Optional)</label>
                    <input type="text" id="payNotes" class="exp-input" placeholder="Reference or payment memo">
                </div>
            </div>
            <div class="exp-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closePaymentModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitPayment">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 4: REVERSE EXPENSE CONFIRMATION -->
<div class="exp-modal-overlay" id="reverseModal">
    <div class="exp-modal-box" style="max-width: 440px;">
        <div class="exp-modal-header" style="background: #fef2f2; border-bottom-color: #fecaca;">
            <h3 class="exp-modal-title" style="color: #b91c1c;">Reverse Expense Transaction</h3>
            <button type="button" class="exp-modal-close" onclick="closeReverseModal()">&times;</button>
        </div>
        <form id="reverseForm" onsubmit="submitReverseForm(); return false;">
            <input type="hidden" id="revExpenseId" value="">
            <div class="exp-modal-body">
                <p style="font-size: 13px; color: #475569; margin-top: 0;">
                    Are you sure you want to reverse this posted expense? This will post an auditable mirror journal entry reversing debits/credits in the General Ledger and restore any Cash Drawer movements.
                </p>
                <div class="exp-form-group">
                    <label class="exp-label">Mandatory Reversal Reason *</label>
                    <textarea id="revReason" class="exp-textarea" placeholder="Explain why this transaction is being reversed..." required></textarea>
                </div>
            </div>
            <div class="exp-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeReverseModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-trash" id="btnConfirmReverse">Confirm Reversal</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;
let currentLimit = 25;
let currentView = 'active'; // 'active' or 'deleted'
let currentSearchQuery = '';
let activeDetailsExpense = null;

document.addEventListener('DOMContentLoaded', function() {
    // Attach CurtissSearchableSelect to inline filter dropdowns
    if (window.CurtissSearchableSelect) {
        document.querySelectorAll('#filterBranch, #filterCategory, #filterMethod, #filterStatus').forEach(el => {
            CurtissSearchableSelect.attach(el);
        });
    }

    // Connect to Floating Bottom Navigation Realtime Search
    if (window.CurtissNav && typeof window.CurtissNav.enablePageSearch === 'function') {
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search expenses by #, payee, category, amount...',
            onSearch: function(query) {
                currentSearchQuery = query;
                loadExpenses(1);
            }
        });
    }

    // Keyboard navigation: Enter advances to next field
    setupKeyboardNavigation(document.getElementById('expenseForm'));
    setupKeyboardNavigation(document.getElementById('paymentForm'));

    // Initial load
    loadExpenses(1);
});

function setupKeyboardNavigation(form) {
    if (!form) return;
    const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
    inputs.forEach((input, index) => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const nextInput = inputs[index + 1];
                if (nextInput) {
                    nextInput.focus();
                    if (typeof nextInput.select === 'function') nextInput.select();
                }
            }
        });
    });
}

function loadExpenses(page = 1) {
    currentPage = page;
    const branch = document.getElementById('filterBranch') ? document.getElementById('filterBranch').value : '<?= $branchId ?>';
    const cat = document.getElementById('filterCategory').value;
    const method = document.getElementById('filterMethod').value;
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        limit: currentLimit,
        view: currentView,
        branch_id: branch,
        category_id: cat,
        payment_method: method,
        status: status,
        search: currentSearchQuery
    });

    fetch('expense_api.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Failed to load expenses.');
                return;
            }
            renderTable(data.items);
            renderPagination(data);
            refreshStats();
        })
        .catch(err => {
            console.error('Error fetching expenses:', err);
        });
}

function refreshStats() {
    const branch = document.getElementById('filterBranch') ? document.getElementById('filterBranch').value : '<?= $branchId ?>';
    fetch('expense_api.php?action=stats&branch_id=' + encodeURIComponent(branch))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                const s = data.data;
                document.getElementById('kpiTotalAmount').textContent = 'LKR ' + formatMoney(s.total_amount);
                document.getElementById('kpiDrawerAmount').textContent = 'LKR ' + formatMoney(s.cash_drawer_amount);
                document.getElementById('kpiBankAmount').textContent   = 'LKR ' + formatMoney(s.bank_amount);
                document.getElementById('kpiSafeAmount').textContent   = 'LKR ' + formatMoney(s.cash_in_hand_amount);
                document.getElementById('kpiPettyAmount').textContent  = 'LKR ' + formatMoney(s.petty_cash_amount);
                document.getElementById('kpiUnpaidAmount').textContent = 'LKR ' + formatMoney(s.unpaid_amount);
            }
        });
}

function renderTable(items) {
    const tbody = document.getElementById('expenseTableBody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="col-center" style="padding: 40px; color: #64748b;">No expense records found.</td></tr>`;
        return;
    }

    let html = '';
    items.forEach(it => {
        let statusBadge = '';
        if (it.status === 'posted') {
            statusBadge = `<span class="exp-badge exp-badge-posted">Posted</span>`;
        } else if (it.status === 'draft') {
            statusBadge = `<span class="exp-badge exp-badge-draft">Draft</span>`;
        } else if (it.status === 'unpaid') {
            statusBadge = `<span class="exp-badge exp-badge-unpaid">Unpaid</span>`;
        } else if (it.status === 'partially_paid') {
            statusBadge = `<span class="exp-badge exp-badge-partial">Partial</span>`;
        } else if (it.status === 'paid') {
            statusBadge = `<span class="exp-badge exp-badge-posted">Paid</span>`;
        } else if (it.status === 'reversed') {
            statusBadge = `<span class="exp-badge exp-badge-reversed">Reversed</span>`;
        }

        let methodLabel = formatMethodName(it.payment_method);
        if (it.bank_name) {
            methodLabel += ` (${escapeHtml(it.bank_name)})`;
        }

        let actionButtons = '';
        if (currentView === 'deleted') {
            actionButtons = `<button type="button" class="inv-btn inv-btn-ghost" onclick="event.stopPropagation(); restoreDraft(${it.id})" style="height: 28px; padding: 0 10px; font-size: 11px;">Restore</button>`;
        } else {
            if (it.status === 'draft') {
                actionButtons = `
                    <button type="button" class="inv-btn inv-btn-primary" onclick="event.stopPropagation(); postDraft(${it.id})" style="height: 26px; padding: 0 8px; font-size: 11px;">Post</button>
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="event.stopPropagation(); editDraft(${it.id})" style="height: 26px; padding: 0 8px; font-size: 11px;">Edit</button>
                    <button type="button" class="inv-btn inv-btn-trash" onclick="event.stopPropagation(); deleteDraft(${it.id})" style="height: 26px; padding: 0 8px; font-size: 11px;">Delete</button>
                `;
            } else if (it.status === 'posted' || it.status === 'partially_paid') {
                if (it.payment_method === 'unpaid' && (parseFloat(it.amount) - parseFloat(it.paid_amount)) > 0) {
                    actionButtons += `<button type="button" class="inv-btn inv-btn-primary" onclick="event.stopPropagation(); openPaymentModal(${it.id}, ${parseFloat(it.amount) - parseFloat(it.paid_amount)})" style="height: 26px; padding: 0 8px; font-size: 11px; margin-right: 4px;">Pay</button>`;
                }
                actionButtons += `<button type="button" class="inv-btn inv-btn-trash" onclick="event.stopPropagation(); openReverseModal(${it.id})" style="height: 26px; padding: 0 8px; font-size: 11px;">Reverse</button>`;
            } else if (it.status === 'paid') {
                actionButtons = `<button type="button" class="inv-btn inv-btn-trash" onclick="event.stopPropagation(); openReverseModal(${it.id})" style="height: 26px; padding: 0 8px; font-size: 11px;">Reverse</button>`;
            }
        }

        html += `
            <tr onclick="viewExpenseDetails(${it.id})" style="cursor: pointer;">
                <td style="font-weight: 800; font-family: monospace; color: #0f172a;">${escapeHtml(it.expense_no)}</td>
                <td style="white-space: nowrap;">${escapeHtml(it.expense_date)}</td>
                <td><span style="font-weight: 700;">${escapeHtml(it.category_name || 'General')}</span></td>
                <td>
                    <div style="font-weight: 600; color: #0f172a;">${escapeHtml(it.description)}</div>
                    ${it.invoice_ref ? `<span style="font-size: 11px; color: #64748b;">Ref: ${escapeHtml(it.invoice_ref)}</span>` : ''}
                </td>
                <td>${escapeHtml(it.payee)}</td>
                <td><span class="exp-method-tag">${escapeHtml(methodLabel)}</span></td>
                <td class="col-right" style="font-weight: 800; font-family: monospace;">${formatMoney(it.amount)}</td>
                <td class="col-center">${statusBadge}</td>
                <td class="col-right" onclick="event.stopPropagation();" style="white-space: nowrap;">
                    ${actionButtons}
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function renderPagination(data) {
    const total = data.total || 0;
    const start = total === 0 ? 0 : (currentPage - 1) * currentLimit + 1;
    const end = Math.min(currentPage * currentLimit, total);
    document.getElementById('paginationInfo').textContent = `Showing ${start} to ${end} of ${total} entries`;
    document.getElementById('pageNumberDisplay').textContent = `${currentPage} / ${data.total_pages || 1}`;
    document.getElementById('btnPrevPage').disabled = (currentPage <= 1);
    document.getElementById('btnNextPage').disabled = (currentPage >= (data.total_pages || 1));
}

function prevPage() {
    if (currentPage > 1) loadExpenses(currentPage - 1);
}
function nextPage() {
    loadExpenses(currentPage + 1);
}
function changePageSize(val) {
    currentLimit = parseInt(val, 10);
    loadExpenses(1);
}

function toggleTrashView() {
    if (currentView === 'active') {
        currentView = 'deleted';
        document.getElementById('trashBtnText').textContent = 'Active Expenses';
        document.getElementById('btnToggleTrash').className = 'inv-btn inv-btn-ghost';
    } else {
        currentView = 'active';
        document.getElementById('trashBtnText').textContent = 'Trash Bin';
        document.getElementById('btnToggleTrash').className = 'inv-btn inv-btn-trash';
    }
    loadExpenses(1);
}

function exportExpensesCsv() {
    const branch = document.getElementById('filterBranch') ? document.getElementById('filterBranch').value : '<?= $branchId ?>';
    const cat = document.getElementById('filterCategory').value;
    const method = document.getElementById('filterMethod').value;
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams({
        action: 'export_csv',
        branch_id: branch,
        category_id: cat,
        payment_method: method,
        status: status,
        search: currentSearchQuery
    });
    window.location.href = 'expense_api.php?' + params.toString();
}

/* Modal Open / Close */
function openNewExpenseModal() {
    document.getElementById('expenseForm').reset();
    document.getElementById('expId').value = '';
    document.getElementById('entryModalTitle').textContent = '+ Record New Expense';
    document.getElementById('expDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('btnSaveDraft').style.display = 'inline-flex';
    document.getElementById('btnPostDirect').textContent = 'Post Expense';
    document.getElementById('expPaymentMethod').value = 'cash_in_hand';
    document.getElementById('expenseEntryModal').classList.add('active');
}

function closeExpenseModal() {
    document.getElementById('expenseEntryModal').classList.remove('active');
}

function handleCategoryChange(catId) {
    // Category change handler if needed for dynamic accounts
}

function submitExpenseForm(status) {
    const form = document.getElementById('expenseForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    formData.set('status', status);
    formData.set('action', formData.get('id') ? 'update_draft' : 'create');

    const submitBtn = (status === 'draft') ? document.getElementById('btnSaveDraft') : document.getElementById('btnPostDirect');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';

    fetch('expense_api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.textContent = (status === 'draft') ? 'Save as Draft' : 'Post Expense';
        if (!data.success) {
            alert(data.message || 'Submission failed.');
            return;
        }
        closeExpenseModal();
        loadExpenses(currentPage);
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.textContent = (status === 'draft') ? 'Save as Draft' : 'Post Expense';
        alert('Server connection error.');
    });
}

function editDraft(id) {
    fetch('expense_api.php?action=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.data) {
                alert('Failed to load expense details.');
                return;
            }
            const it = data.data;
            document.getElementById('expId').value = it.id;
            document.getElementById('entryModalTitle').textContent = 'Edit Draft Expense #' + it.expense_no;
            document.getElementById('expDate').value = it.expense_date;
            document.getElementById('expCategory').value = it.category_id;
            if (it.payment_method === 'bank' && it.bank_account_id) {
                document.getElementById('expPaymentMethod').value = 'Bank:' + it.bank_account_id;
            } else {
                document.getElementById('expPaymentMethod').value = it.payment_method;
            }
            document.getElementById('expAmount').value = it.amount;
            document.getElementById('expPayee').value = it.payee;
            document.getElementById('expDescription').value = it.description;
            if (document.getElementById('expInvoiceRef')) document.getElementById('expInvoiceRef').value = it.invoice_ref || '';

            document.getElementById('btnSaveDraft').style.display = 'inline-flex';
            document.getElementById('btnPostDirect').textContent = 'Post Expense';
            document.getElementById('expenseEntryModal').classList.add('active');
        });
}

function postDraft(id) {
    if (!confirm('Post this draft expense to the General Ledger now?')) return;
    const fd = new FormData();
    fd.append('action', 'post');
    fd.append('id', id);

    fetch('expense_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Posting failed.');
                return;
            }
            loadExpenses(currentPage);
        });
}

function deleteDraft(id) {
    if (!confirm('Move this draft expense to trash bin?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_draft');
    fd.append('id', id);

    fetch('expense_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Deletion failed.');
                return;
            }
            loadExpenses(currentPage);
        });
}

function restoreDraft(id) {
    const fd = new FormData();
    fd.append('action', 'restore_draft');
    fd.append('id', id);

    fetch('expense_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Restore failed.');
                return;
            }
            loadExpenses(currentPage);
        });
}

/* View Details & Voucher Preview */
function viewExpenseDetails(id) {
    fetch('expense_api.php?action=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.data) {
                alert('Expense details could not be loaded.');
                return;
            }
            activeDetailsExpense = data.data;
            renderDetailsContent(activeDetailsExpense);
            document.getElementById('expenseDetailsModal').classList.add('active');
        });
}

function closeDetailsModal() {
    document.getElementById('expenseDetailsModal').classList.remove('active');
}

function renderDetailsContent(exp) {
    document.getElementById('detailsModalTitle').textContent = `Expense Voucher #${exp.expense_no}`;
    let html = `
        <div style="border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-size: 1.2rem; font-weight: 800; color: #0f172a;">${escapeHtml(exp.payee)}</div>
                <div style="font-size: 12px; color: #64748b;">${escapeHtml(exp.category_name)} &bull; ${escapeHtml(exp.expense_date)}</div>
                <div style="font-size: 11px; color: #94a3b8;">Branch: ${escapeHtml(exp.branch_name || 'Main')}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 1.4rem; font-weight: 800; color: #0f172a; font-family: monospace;">LKR ${formatMoney(exp.amount)}</div>
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Status: ${escapeHtml(exp.status)}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 12px; margin-bottom: 14px;">
            <div><span style="color: #64748b;">Payment Source:</span> <strong>${escapeHtml(formatMethodName(exp.payment_method))} ${exp.bank_name ? `(${escapeHtml(exp.bank_name)})` : ''}</strong></div>
            <div><span style="color: #64748b;">GL Expense Account:</span> <strong>${escapeHtml(exp.expense_account_name)} (${escapeHtml(exp.expense_account_code)})</strong></div>
            <div><span style="color: #64748b;">Recorded By:</span> <strong>${escapeHtml(exp.created_by_name || 'System')}</strong></div>
            <div><span style="color: #64748b;">Journal Entry:</span> <strong>${exp.journal_entry_number ? '#' + escapeHtml(exp.journal_entry_number) : 'Not Posted'}</strong></div>
            ${exp.invoice_ref ? `<div><span style="color: #64748b;">Invoice Ref:</span> <strong>${escapeHtml(exp.invoice_ref)}</strong></div>` : ''}
            ${exp.reference_no ? `<div><span style="color: #64748b;">Payment Ref:</span> <strong>${escapeHtml(exp.reference_no)}</strong></div>` : ''}
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; font-size: 12.5px; margin-bottom: 14px;">
            <div style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Description & Purpose</div>
            <div>${escapeHtml(exp.description)}</div>
            ${exp.notes ? `<div style="font-size: 11px; color: #64748b; margin-top: 6px;">Notes: ${escapeHtml(exp.notes)}</div>` : ''}
        </div>
    `;

    // Payment history if applicable
    if (exp.payments && exp.payments.length > 0) {
        html += `
            <div style="margin-top: 14px;">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 6px;">Bill Payments History</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                            <th style="padding: 6px;">Payment #</th>
                            <th style="padding: 6px;">Date</th>
                            <th style="padding: 6px;">Source</th>
                            <th style="padding: 6px; text-align: right;">Amount (LKR)</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        exp.payments.forEach(p => {
            html += `
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 6px; font-family: monospace;">${escapeHtml(p.payment_no)}</td>
                    <td style="padding: 6px;">${escapeHtml(p.payment_date)}</td>
                    <td style="padding: 6px;">${escapeHtml(formatMethodName(p.payment_method))}</td>
                    <td style="padding: 6px; text-align: right; font-weight: 700;">${formatMoney(p.amount)}</td>
                </tr>
            `;
        });
        html += `</tbody></table></div>`;
    }

    // Attachments if applicable
    if (exp.attachments && exp.attachments.length > 0) {
        html += `
            <div style="margin-top: 14px;">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 6px;">Supporting Attachments</div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        `;
        exp.attachments.forEach(att => {
            html += `
                <a href="${escapeHtml(att.file_path)}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f1f5f9; border-radius: 6px; font-size: 12px; text-decoration: none; color: #0f172a; font-weight: 700;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    ${escapeHtml(att.file_name)}
                </a>
            `;
        });
        html += `</div></div>`;
    }

    document.getElementById('detailsModalContent').innerHTML = html;
}

function printExpenseVoucher() {
    if (!activeDetailsExpense) return;
    const exp = activeDetailsExpense;
    const printWindow = window.open('', '_blank', 'width=700,height=800');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Expense Voucher #${exp.expense_no}</title>
            <style>
                body { font-family: monospace, sans-serif; padding: 20px; font-size: 13px; color: #000; line-height: 1.4; }
                .center { text-align: center; }
                .right { text-align: right; }
                .bold { font-weight: bold; }
                .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
                .double { border-bottom: 3px double #000; margin: 10px 0; }
                table { width: 100%; border-collapse: collapse; margin: 8px 0; }
                th, td { padding: 4px 0; vertical-align: top; }
                .voucher-title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
            </style>
        </head>
        <body onload="window.print(); window.close();">
            <div class="center">
                <div class="voucher-title">EXPENSE PAYMENT VOUCHER</div>
                <div>CURTISS POS - ${escapeHtml(exp.branch_name || 'Main')}</div>
            </div>
            <div class="double"></div>
            <table>
                <tr><td><strong>VOUCHER NO:</strong></td><td class="right">${escapeHtml(exp.expense_no)}</td></tr>
                <tr><td><strong>DATE:</strong></td><td class="right">${escapeHtml(exp.expense_date)}</td></tr>
                <tr><td><strong>CATEGORY:</strong></td><td class="right">${escapeHtml(exp.category_name)}</td></tr>
                <tr><td><strong>PAYEE:</strong></td><td class="right">${escapeHtml(exp.payee)}</td></tr>
                <tr><td><strong>PAYMENT SOURCE:</strong></td><td class="right">${escapeHtml(formatMethodName(exp.payment_method))} ${exp.bank_name ? `(${escapeHtml(exp.bank_name)})` : ''}</td></tr>
                <tr><td><strong>GL ACCOUNT:</strong></td><td class="right">${escapeHtml(exp.expense_account_name)} (${escapeHtml(exp.expense_account_code)})</td></tr>
            </table>
            <div class="divider"></div>
            <div><strong>DESCRIPTION:</strong></div>
            <div style="margin: 4px 0 10px 0;">${escapeHtml(exp.description)}</div>
            <div class="divider"></div>
            <table>
                <tr class="bold" style="font-size: 15px;">
                    <td>TOTAL AMOUNT:</td>
                    <td class="right">LKR ${formatMoney(exp.amount)}</td>
                </tr>
            </table>
            <div class="double"></div>
            <br><br><br>
            <table style="margin-top: 30px;">
                <tr>
                    <td style="width: 33%; text-align: center; border-top: 1px solid #000;">Prepared By<br>(${escapeHtml(exp.created_by_name || 'System')})</td>
                    <td style="width: 33%;"></td>
                    <td style="width: 33%; text-align: center; border-top: 1px solid #000;">Authorized By</td>
                </tr>
            </table>
        </body>
        </html>
    `);
    printWindow.document.close();
}

/* Payment Modal for Unpaid Bills */
function openPaymentModal(expenseId, remaining) {
    document.getElementById('payExpenseId').value = expenseId;
    document.getElementById('payRemainingDisplay').textContent = 'LKR ' + formatMoney(remaining);
    document.getElementById('payAmount').value = remaining.toFixed(2);
    document.getElementById('payAmount').max = remaining.toFixed(2);
    document.getElementById('payDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('payNotes').value = '';
    document.getElementById('payMethod').value = 'cash_in_hand';
    document.getElementById('paymentModal').classList.add('active');
}
function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
}
function submitPaymentForm() {
    const expenseId = document.getElementById('payExpenseId').value;
    const amount = document.getElementById('payAmount').value;
    const method = document.getElementById('payMethod').value;
    const date = document.getElementById('payDate').value;
    const notes = document.getElementById('payNotes').value;

    const fd = new FormData();
    fd.append('action', 'record_payment');
    fd.append('expense_id', expenseId);
    fd.append('amount', amount);
    fd.append('payment_method', method);
    fd.append('payment_date', date);
    fd.append('notes', notes);

    const btn = document.getElementById('btnSubmitPayment');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch('expense_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Save Payment';
            if (!data.success) {
                alert(data.message || 'Payment recording failed.');
                return;
            }
            closePaymentModal();
            loadExpenses(currentPage);
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Save Payment';
            alert('Server error.');
        });
}

/* Reverse Modal */
function openReverseModal(id) {
    document.getElementById('revExpenseId').value = id;
    document.getElementById('revReason').value = '';
    document.getElementById('reverseModal').classList.add('active');
}
function closeReverseModal() {
    document.getElementById('reverseModal').classList.remove('active');
}
function submitReverseForm() {
    const id = document.getElementById('revExpenseId').value;
    const reason = document.getElementById('revReason').value.trim();
    if (!reason) {
        alert('Please provide a mandatory reversal reason.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'reverse');
    fd.append('id', id);
    fd.append('reason', reason);

    const btn = document.getElementById('btnConfirmReverse');
    btn.disabled = true;
    btn.textContent = 'Reversing...';

    fetch('expense_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Confirm Reversal';
            if (!data.success) {
                alert(data.message || 'Reversal failed.');
                return;
            }
            closeReverseModal();
            loadExpenses(currentPage);
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Confirm Reversal';
            alert('Server connection error.');
        });
}

/* Format Helpers */
function formatMoney(amount) {
    return parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function formatMethodName(method) {
    if (!method) return 'N/A';
    const map = {
        'cash_drawer': 'Cash Drawer',
        'cash_in_hand': 'Cash in Hand (Safe)',
        'petty_cash': 'Petty Cash',
        'bank': 'Bank Account',
        'unpaid': 'Unpaid Bill'
    };
    return map[method] || method.replace('_', ' ').toUpperCase();
}
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
