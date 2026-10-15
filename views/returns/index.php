<?php
/**
 * Curtiss POS - Sales Returns & Exchanges Master View (Enhanced)
 * Redesigned Layout:
 * 1. Customer & Invoice Omni-Search with Drill-down to Sold Items
 * 2. Real-time Product Autocomplete with Keyboard Nav & Lot Selection Modal
 * 3. Removed Editable Cost Price in Manual Returns
 * 4. COA Account Split Routing & Optional Refund (Store Credit / POS Exchange Default)
 * 5. Modern Fixed Viewport Aesthetics with Sticky Headers & Zebra Striping
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<style>
    :root {
        --inv-primary: #0f172a;
        --inv-accent: #2563eb;
        --inv-success: #16a34a;
        --inv-warning: #d97706;
        --inv-danger: #dc2626;
        --inv-bg-glass: rgba(255, 255, 255, 0.95);
        --inv-border: #e2e8f0;
        --inv-radius: 14px;
        --inv-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.05);
    }

    /* Page Header Headline Only (Rule 5) */
    .inv-header {
        margin-bottom: 0.85rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .inv-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.025em;
        line-height: 1.2;
        margin: 0;
    }

    /* Navigation Tabs Bar */
    .returns-tab-container {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        gap: 4px;
    }
    .tab-btn {
        background: transparent;
        border: none;
        font-size: 11.5px;
        font-weight: 700;
        padding: 6px 16px;
        color: #64748b;
        cursor: pointer;
        border-radius: 999px;
        transition: all 0.18s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: inherit;
    }
    .tab-btn:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.7);
    }
    .tab-btn.active {
        background: #0f172a;
        color: #ffffff;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.2);
    }

    /* Button System */
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
        transition: all 0.15s ease;
        white-space: nowrap;
        font-family: inherit;
        box-sizing: border-box;
    }
    .inv-btn-ghost {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .inv-btn-ghost:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }
    .inv-btn-primary {
        background: #0f172a;
        color: #ffffff;
    }
    .inv-btn-primary:hover {
        background: #1e293b;
    }
    .inv-btn-success {
        background: #16a34a;
        color: #ffffff;
    }
    .inv-btn-success:hover {
        background: #15803d;
    }
    .inv-btn-danger {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fca5a5;
    }
    .inv-btn-danger:hover {
        background: #fee2e2;
    }
    .inv-btn-sm {
        height: 28px;
        min-height: 28px;
        padding: 0 10px;
        font-size: 11px;
    }

    /* Cards & Panels */
    .inv-card {
        background: var(--inv-bg-glass);
        border: 1px solid var(--inv-border);
        border-radius: var(--inv-radius);
        box-shadow: var(--inv-shadow);
        padding: 1.15rem;
        box-sizing: border-box;
        margin-bottom: 1rem;
    }

    /* Form Fields */
    .inv-input, .inv-select {
        height: 34px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 12px;
        font-family: inherit;
        color: #0f172a;
        background: #ffffff;
        box-sizing: border-box;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .inv-input:focus, .inv-select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .inv-input-sm {
        height: 30px;
        padding: 0 8px;
        font-size: 11.5px;
    }

    /* Table System with Zebra Striping (Rule 2 & 24) */
    .return-table-wrap {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow-x: auto;
        overflow-y: auto;
        max-height: 360px;
    }
    .return-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        text-align: left;
    }
    .return-table thead th {
        padding: 10px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 10;
        white-space: nowrap;
        text-align: left;
    }
    .return-table thead th.col-right, .return-table tbody td.col-right {
        text-align: right;
    }
    .return-table thead th.col-center, .return-table tbody td.col-center {
        text-align: center;
    }
    .return-table tbody tr {
        height: 48px;
        min-height: 48px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.12s ease;
    }
    .return-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
    .return-table tbody tr:nth-child(odd)  { background: #ffffff; }
    .return-table tbody tr:hover            { background: #f1f5f9 !important; }
    .return-table td {
        padding: 0 14px;
        color: #1e293b;
        vertical-align: middle;
        box-sizing: border-box;
    }

    /* Code Pills */
    .inv-code-pill {
        font-family: ui-monospace, monospace;
        font-size: 11px;
        background: #f1f5f9;
        color: #334155;
        padding: 2px 6px;
        border-radius: 5px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
    }

    /* Autocomplete Dropdown */
    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
        max-height: 280px;
        overflow-y: auto;
        z-index: 1000;
        margin-top: 4px;
        display: none;
    }
    .autocomplete-item {
        padding: 8px 12px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }
    .autocomplete-item:hover, .autocomplete-item.active {
        background: #eff6ff;
    }

    /* Modals */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(6px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1100;
    }
    .modal-content {
        background: #ffffff;
        border-radius: 16px;
        width: 90%;
        max-width: 760px;
        max-height: 85vh;
        overflow-y: auto;
        padding: 1.5rem;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.25);
        border: 1px solid #e2e8f0;
    }

    /* Settlement Options Radio Buttons */
    .settlement-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        cursor: pointer;
        background: #ffffff;
        font-size: 12px;
        font-weight: 700;
        transition: all 0.15s ease;
        user-select: none;
    }
    .settlement-pill input[type="radio"] {
        margin: 0;
        accent-color: #0f172a;
    }
    .settlement-pill.selected {
        border-color: #0f172a;
        background: #f8fafc;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    }
</style>

<script>
    window.returnBankAccounts = <?= json_encode($bankAccounts ?? []) ?>;
</script>

<!-- Headline and Mode Tabs (Rule 5 & 19) -->
<div class="inv-header">
    <div style="display: flex; align-items: center; gap: 8px;">
        <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
        <h1 class="inv-title">Sales Returns & Exchanges</h1>
    </div>

    <!-- Mode Navigation Tabs -->
    <div class="returns-tab-container">
        <button class="tab-btn active" id="tabInvoiceReturnBtn" onclick="switchTab('invoice_return')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Return with Invoice / Customer
        </button>
        <?php if (Auth::hasPermission('returns.no_invoice')): ?>
        <button class="tab-btn" id="tabManualReturnBtn" onclick="switchTab('manual_return')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
            Manual Product Return
        </button>
        <?php endif; ?>
        <button class="tab-btn" id="tabHistoryBtn" onclick="switchTab('history')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Returns History Log
        </button>
    </div>
</div>

<!-- ========================================================================= -->
<!-- TAB 1: RETURN WITH INVOICE / CUSTOMER DRILL-DOWN (Requirement 1 & 4)      -->
<!-- ========================================================================= -->
<div id="panelInvoiceReturn">
    <div class="inv-card">
        <!-- Omni Search Row -->
        <div style="position: relative; margin-bottom: 0.85rem;">
            <div style="display: flex; gap: 8px;">
                <div style="position: relative; flex: 1;">
                    <input type="text" id="omniSearchInput" class="inv-input" placeholder="Search Customer (Phone, Name, Code) or Invoice # (e.g. 077..., Susara, 0001)..." oninput="onOmniSearchInput(this.value)" onkeydown="onOmniSearchKeydown(event)" autocomplete="off" style="padding-left: 36px; height: 38px; font-size: 13px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" style="position: absolute; left: 12px; top: 11px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <button type="button" class="inv-btn inv-btn-ghost" onclick="clearOmniSearch()" title="Clear search">Clear</button>
            </div>
            
            <!-- Realtime Autocomplete Results Container -->
            <div id="omniSearchResults" class="autocomplete-dropdown"></div>
        </div>

        <!-- Selected Invoice Active Banner -->
        <div id="selectedInvoiceBanner" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 10px 14px; margin-bottom: 0.85rem; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #166534; text-transform: uppercase;">Selected Invoice:</span>
                    <strong id="bannerInvoiceNo" style="font-size: 14px; color: #0f172a; margin-left: 4px; font-family: monospace;">#0000</strong>
                </div>
                <div style="color: #64748b; font-size: 12px;">|</div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #166534; text-transform: uppercase;">Customer:</span>
                    <strong id="bannerCustomerName" style="font-size: 12.5px; color: #0f172a; margin-left: 4px;">Walk-in</strong>
                </div>
                <div style="color: #64748b; font-size: 12px;">|</div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #166534; text-transform: uppercase;">Date:</span>
                    <span id="bannerInvoiceDate" style="font-size: 12px; color: #475569; margin-left: 4px;">YYYY-MM-DD</span>
                </div>
                <div style="color: #64748b; font-size: 12px;">|</div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #166534; text-transform: uppercase;">Invoice Total:</span>
                    <strong id="bannerInvoiceTotal" style="font-size: 13px; color: #16a34a; margin-left: 4px;">LKR 0.00</strong>
                </div>
            </div>
            <button type="button" class="inv-btn inv-btn-ghost inv-btn-sm" onclick="clearSelectedInvoice()">Change Invoice</button>
        </div>

        <!-- Invoice Sold Items Table -->
        <div class="return-table-wrap" style="margin-bottom: 0.85rem;">
            <table class="return-table" id="invoiceItemsTable">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;"><input type="checkbox" id="selectAllInvoiceItems" onchange="toggleSelectAllInvoiceItems(this.checked)" title="Select All" style="cursor: pointer;"></th>
                        <th style="width: 240px;">Product & Sold Lot</th>
                        <th style="width: 120px;">Sold / Avail Qty</th>
                        <th style="width: 110px;">Return Qty</th>
                        <th style="width: 120px;">Unit Price (LKR)</th>
                        <th style="width: 130px;">Condition</th>
                        <th>Reason</th>
                        <th class="col-right" style="width: 130px;">Line Total (LKR)</th>
                    </tr>
                </thead>
                <tbody id="invoiceItemsTbody">
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem;">
                            Search by Customer or Invoice Number above to load sold products.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Settlement Mode & Refund Action Card -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <!-- Total Return Summary Pill -->
                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 16px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Return Value:</span>
                    <span id="invoiceReturnTotalVal" style="font-size: 1.35rem; font-weight: 800; color: #16a34a; font-family: monospace;">LKR 0.00</span>
                </div>

                <!-- Settlement Mode Selector (Requirement 4) -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <label class="settlement-pill selected" id="invoiceModeExchangeLabel">
                        <input type="radio" name="invoice_settlement_mode" value="exchange" checked onchange="onInvoiceSettlementModeChange(this.value)">
                        <span>🔄 POS Exchange / Store Credit (No Cash Refund)</span>
                    </label>
                    <label class="settlement-pill" id="invoiceModeCreditLabel">
                        <input type="radio" name="invoice_settlement_mode" value="credit" onchange="onInvoiceSettlementModeChange(this.value)">
                        <span>💳 Customer Account Credit</span>
                    </label>
                    <label class="settlement-pill" id="invoiceModeRefundLabel">
                        <input type="radio" name="invoice_settlement_mode" value="refund" onchange="onInvoiceSettlementModeChange(this.value)">
                        <span>💸 Financial Refund Payout</span>
                    </label>
                </div>
            </div>

            <!-- Optional Financial Refund Settlement Breakdown (Visible ONLY when Payout chosen) -->
            <div id="invoiceRefundBreakdownBox" style="display: none; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 11px; font-weight: 800; color: #334155; text-transform: uppercase;">
                        Refund Settlement Breakdown (COA Accounts)
                    </span>
                    <button type="button" class="inv-btn inv-btn-sm" style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd;" onclick="addInvoiceRefundSplitRow()">
                        + Add Account Split
                    </button>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: #f8fafc; height: 32px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                <th style="padding: 0 10px; text-align: left;">Payment Account / Method</th>
                                <th style="padding: 0 10px; text-align: right; width: 150px;">Amount (LKR)</th>
                                <th style="padding: 0 10px; text-align: left; width: 200px;">Reference / Notes</th>
                                <th style="padding: 0 6px; text-align: center; width: 36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="invoiceRefundSplitsBody"></tbody>
                    </table>
                </div>

                <!-- Allocation Balance Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; background: #f1f5f9; border-radius: 8px; font-size: 12px; font-weight: 700; margin-top: 8px;">
                    <span id="invoiceRefundAllocatedText">Allocated: LKR 0.00 / Total Refund: LKR 0.00</span>
                    <span id="invoiceRefundDiffBadge" class="prod-badge prod-badge-active" style="background: #dcfce7; color: #15803d; font-size: 11px; padding: 3px 10px;">✓ Balanced (100%)</span>
                </div>
            </div>

            <!-- Notes & Finalize Row -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 4px;">
                <input type="text" id="invoiceReturnNotes" class="inv-input" placeholder="Optional return notes / comments..." style="flex: 1; max-width: 480px;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="resetInvoiceReturnForm()">Reset</button>
                    <button type="button" id="btnSubmitInvoiceReturn" class="inv-btn inv-btn-success" onclick="submitInvoiceReturn()">
                        Finalize Sales Return
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- TAB 2: MANUAL / NO-INVOICE PRODUCT RETURN (Requirements 2, 3 & 4)        -->
<!-- ========================================================================= -->
<?php if (Auth::hasPermission('returns.no_invoice')): ?>
<div id="panelManualReturn" style="display: none;">
    <div class="inv-card">
        <!-- Top Bar: Customer Selector & Realtime Product Search -->
        <div style="display: grid; grid-template-columns: 280px 1fr; gap: 12px; margin-bottom: 0.85rem;">
            <!-- Customer Account Selector -->
            <div>
                <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">Customer Account:</label>
                <select id="manualCustomerSelect" class="inv-select" style="font-weight: 700; width: 100%;">
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone'] ?? 'Walk-in') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Realtime Product Search Bar with Keyboard Nav (Requirement 2) -->
            <div style="position: relative;">
                <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">Realtime Product Search:</label>
                <div style="position: relative;">
                    <input type="text" id="manualProductInput" class="inv-input" placeholder="Type product name, barcode, or SKU..." oninput="onManualProductSearchInput(this.value)" onkeydown="onManualProductSearchKeydown(event)" autocomplete="off" style="padding-left: 36px; height: 34px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" style="position: absolute; left: 11px; top: 10px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <!-- Autocomplete Dropdown -->
                <div id="manualProductDropdown" class="autocomplete-dropdown"></div>
            </div>
        </div>

        <!-- Table of Manual Return Items (Requirement 3: Removed Editable Cost Price!) -->
        <div class="return-table-wrap" style="margin-bottom: 0.85rem;">
            <table class="return-table" id="manualItemsTable">
                <thead>
                    <tr>
                        <th style="width: 280px;">Product & Lot</th>
                        <th style="width: 110px;">Return Qty</th>
                        <th style="width: 130px;">Unit Price (LKR)</th>
                        <th style="width: 140px;">Condition</th>
                        <th>Reason</th>
                        <th class="col-right" style="width: 140px;">Line Total (LKR)</th>
                        <th class="col-center" style="width: 50px;">Action</th>
                    </tr>
                </thead>
                <tbody id="manualItemsTbody">
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem;">
                            Type product name or barcode above to add items for return.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Settlement Mode & Refund Action Card for Manual Return -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <!-- Total Return Summary Pill -->
                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 16px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Return Value:</span>
                    <span id="manualReturnTotalVal" style="font-size: 1.35rem; font-weight: 800; color: #16a34a; font-family: monospace;">LKR 0.00</span>
                </div>

                <!-- Settlement Mode Selector -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <label class="settlement-pill selected" id="manualModeExchangeLabel">
                        <input type="radio" name="manual_settlement_mode" value="exchange" checked onchange="onManualSettlementModeChange(this.value)">
                        <span>🔄 POS Exchange / Store Credit (No Cash Refund)</span>
                    </label>
                    <label class="settlement-pill" id="manualModeCreditLabel">
                        <input type="radio" name="manual_settlement_mode" value="credit" onchange="onManualSettlementModeChange(this.value)">
                        <span>💳 Customer Account Credit</span>
                    </label>
                    <label class="settlement-pill" id="manualModeRefundLabel">
                        <input type="radio" name="manual_settlement_mode" value="refund" onchange="onManualSettlementModeChange(this.value)">
                        <span>💸 Financial Refund Payout</span>
                    </label>
                </div>
            </div>

            <!-- Optional Financial Refund Breakdown -->
            <div id="manualRefundBreakdownBox" style="display: none; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 11px; font-weight: 800; color: #334155; text-transform: uppercase;">
                        Refund Settlement Breakdown (COA Accounts)
                    </span>
                    <button type="button" class="inv-btn inv-btn-sm" style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd;" onclick="addManualRefundSplitRow()">
                        + Add Account Split
                    </button>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: #f8fafc; height: 32px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                <th style="padding: 0 10px; text-align: left;">Payment Account / Method</th>
                                <th style="padding: 0 10px; text-align: right; width: 150px;">Amount (LKR)</th>
                                <th style="padding: 0 10px; text-align: left; width: 200px;">Reference / Notes</th>
                                <th style="padding: 0 6px; text-align: center; width: 36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="manualRefundSplitsBody"></tbody>
                    </table>
                </div>

                <!-- Allocation Balance Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; background: #f1f5f9; border-radius: 8px; font-size: 12px; font-weight: 700; margin-top: 8px;">
                    <span id="manualRefundAllocatedText">Allocated: LKR 0.00 / Total Refund: LKR 0.00</span>
                    <span id="manualRefundDiffBadge" class="prod-badge prod-badge-active" style="background: #dcfce7; color: #15803d; font-size: 11px; padding: 3px 10px;">✓ Balanced (100%)</span>
                </div>
            </div>

            <!-- Notes & Finalize Row -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 4px;">
                <input type="text" id="manualReturnNotes" class="inv-input" placeholder="Optional return notes / comments..." style="flex: 1; max-width: 480px;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="resetManualForm()">Reset</button>
                    <button type="button" id="btnSubmitManualReturn" class="inv-btn inv-btn-success" onclick="submitManualReturn()">
                        Finalize Manual Return
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB 3: RETURNS HISTORY LOG                                                -->
<!-- ========================================================================= -->
<div id="panelHistory" style="display: none;">
    <div class="inv-card">
        <form method="GET" action="returns.php" style="display: flex; gap: 8px; margin-bottom: 0.85rem; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="history">
            <input type="text" name="search" class="inv-input" placeholder="Filter Return No, Invoice No, Customer..." value="<?= htmlspecialchars($search) ?>" style="max-width: 320px;">
            <button type="submit" class="inv-btn inv-btn-primary">Filter Log</button>
            <?php if (!empty($search)): ?>
                <a href="returns.php?tab=history" class="inv-btn inv-btn-ghost">Clear</a>
            <?php endif; ?>
        </form>

        <div class="return-table-wrap">
            <table class="return-table">
                <thead>
                    <tr>
                        <th>Return No</th>
                        <th>Date</th>
                        <th>Original Invoice</th>
                        <th>Customer</th>
                        <th class="col-right">Return Total (LKR)</th>
                        <th>Processed By</th>
                        <th class="col-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returnsList)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem;">No sales returns logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($returnsList as $r): ?>
                            <tr>
                                <td><strong style="color: #0f172a; font-family: monospace; font-size: 13px;"><?= htmlspecialchars($r['return_no']) ?></strong></td>
                                <td style="color: #64748b; font-size: 12px;"><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></td>
                                <td><span class="inv-code-pill"><?= htmlspecialchars($r['original_invoice_no'] ?? 'NO-INVOICE') ?></span></td>
                                <td><strong><?= htmlspecialchars($r['customer_name'] ?? 'Walk-in') ?></strong></td>
                                <td class="col-right"><strong style="color: #16a34a; font-family: monospace;">LKR <?= number_format($r['total_return_amount'], 2) ?></strong></td>
                                <td style="font-size: 12px; color: #475569;"><?= htmlspecialchars($r['created_by_name']) ?></td>
                                <td class="col-center">
                                    <button class="inv-btn inv-btn-ghost inv-btn-sm" onclick="viewReturnDetails(<?= $r['id'] ?>)">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 1rem; display: flex; justify-content: center; gap: 4px;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="returns.php?tab=history&page=<?= $i ?>&search=<?= urlencode($search) ?>" class="inv-btn inv-btn-sm <?= $page === $i ? 'inv-btn-primary' : 'inv-btn-ghost' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: LOT SELECTION PANEL (Requirement 2)                                -->
<!-- ========================================================================= -->
<div id="lotSelectionModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 580px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px;">
            <div>
                <span style="font-size: 10px; font-weight: 800; color: #2563eb; text-transform: uppercase;">SELECT PRODUCT LOT</span>
                <h3 id="lotModalProductName" style="margin: 2px 0 0 0; font-size: 15px; font-weight: 800; color: #0f172a;">Product Name</h3>
            </div>
            <button type="button" onclick="closeLotModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <div style="margin-bottom: 10px; font-size: 12px; color: #64748b;">
            Select which inventory lot/batch this returned item belongs to:
        </div>

        <div id="lotModalList" style="display: flex; flex-direction: column; gap: 8px; max-height: 320px; overflow-y: auto;">
            <!-- Dynamic Lot Rows -->
        </div>

        <div style="margin-top: 14px; text-align: right;">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeLotModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: RETURN DETAILS VIEW                                                -->
<!-- ========================================================================= -->
<div id="returnDetailsModal" class="modal-overlay">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 14px;">
            <h3 id="modalReturnTitle" style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">Return Details</h3>
            <button type="button" onclick="closeReturnDetailsModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div id="modalReturnBody">Loading return record details...</div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT SYSTEM ENGINE                                                  -->
<!-- ========================================================================= -->
<script>
    // Global State
    let activeInvoiceSale = null;
    let invoiceReturnCart = [];
    let invoiceRefundSplits = [];

    let manualReturnCart = [];
    let manualRefundSplits = [];
    let currentSelectedProductForLots = null;

    let omniSearchDebounceTimer = null;
    let manualSearchDebounceTimer = null;
    let omniActiveItemIndex = -1;
    let manualActiveItemIndex = -1;

    // Helper functions
    function round2(num) {
        return Math.round((parseFloat(num) || 0) * 100) / 100;
    }

    function esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Switch Tabs
    function switchTab(tab) {
        document.getElementById('tabInvoiceReturnBtn')?.classList.toggle('active', tab === 'invoice_return');
        document.getElementById('tabManualReturnBtn')?.classList.toggle('active', tab === 'manual_return');
        document.getElementById('tabHistoryBtn')?.classList.toggle('active', tab === 'history');

        if (document.getElementById('panelInvoiceReturn')) document.getElementById('panelInvoiceReturn').style.display = (tab === 'invoice_return') ? 'block' : 'none';
        if (document.getElementById('panelManualReturn')) document.getElementById('panelManualReturn').style.display = (tab === 'manual_return') ? 'block' : 'none';
        if (document.getElementById('panelHistory')) document.getElementById('panelHistory').style.display = (tab === 'history') ? 'block' : 'none';
    }

    // Initial check for URL tab parameter
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'history') {
            switchTab('history');
        }

        // Initialize COA Searchable selects if available
        if (window.CurtissSearchableSelect) {
            const manualCust = document.getElementById('manualCustomerSelect');
            if (manualCust) CurtissSearchableSelect.attach(manualCust);
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#omniSearchInput') && !e.target.closest('#omniSearchResults')) {
                document.getElementById('omniSearchResults').style.display = 'none';
            }
            if (!e.target.closest('#manualProductInput') && !e.target.closest('#manualProductDropdown')) {
                document.getElementById('manualProductDropdown').style.display = 'none';
            }
        });
    });

    function getCOAPaymentMethodOptions(selectedKey) {
        let html = '';
        const methods = [
            { key: 'cash_drawer', label: 'Cash Drawer (POS Register)' },
            { key: 'cash_in_hand', label: 'Cash in Hand (Main Safe)' },
            { key: 'petty_cash', label: 'Petty Cash Account' }
        ];

        if (window.returnBankAccounts && window.returnBankAccounts.length > 0) {
            window.returnBankAccounts.forEach(b => {
                methods.push({
                    key: `Bank:${b.id}`,
                    label: `Bank Transfer: ${b.bank_name} - ${b.account_name} (${b.account_number})`
                });
            });
        }

        methods.push({ key: 'cheque', label: 'Cheque Refund' });
        methods.push({ key: 'credit', label: 'Customer Credit Balance' });
        methods.push({ key: 'voucher', label: 'Issue Replacement Gift Voucher' });

        methods.forEach(m => {
            const sel = (m.key === selectedKey) ? 'selected' : '';
            html += `<option value="${m.key}" ${sel}>${m.label}</option>`;
        });

        return html;
    }


    /* =========================================================================
     * SECTION 1: INVOICE & CUSTOMER OMNI-SEARCH (Requirement 1)
     * ========================================================================= */
    function onOmniSearchInput(val) {
        clearTimeout(omniSearchDebounceTimer);
        const q = val.trim();
        const drop = document.getElementById('omniSearchResults');

        if (!q || q.length < 1) {
            drop.style.display = 'none';
            drop.innerHTML = '';
            return;
        }

        omniSearchDebounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`returns_api.php?action=search_sales_or_customers&query=${encodeURIComponent(q)}`);
                const data = await res.json();

                if (!data.success) return;

                const invoices = data.invoices || [];
                const customers = data.customers || [];

                if (invoices.length === 0 && customers.length === 0) {
                    drop.innerHTML = '<div style="padding: 10px 14px; color: #94a3b8; font-size: 12px;">No matching invoices or customers found.</div>';
                    drop.style.display = 'block';
                    return;
                }

                let html = '';

                // Matching Customers Header
                if (customers.length > 0) {
                    html += '<div style="padding: 6px 10px; background: #f8fafc; font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Matching Customers</div>';
                    customers.forEach(c => {
                        html += `
                            <div class="autocomplete-item omni-item" onclick="loadCustomerInvoices(${c.id}, '${esc(c.name)}', '${esc(c.phone)}')">
                                <div>
                                    <strong style="color: #0f172a;">${esc(c.name)}</strong>
                                    <span style="color: #64748b; font-size: 11px; margin-left: 6px;">(${esc(c.phone || 'No Phone')})</span>
                                    <span class="inv-code-pill" style="margin-left: 6px;">${esc(c.customer_code || ('#' + c.id))}</span>
                                </div>
                                <div>
                                    <span style="font-size: 11px; color: #2563eb; font-weight: 700;">${c.total_invoices} Invoices</span>
                                    <button type="button" class="inv-btn inv-btn-ghost inv-btn-sm" style="margin-left: 8px;">View Invoices →</button>
                                </div>
                            </div>
                        `;
                    });
                }

                // Matching Invoices Header
                if (invoices.length > 0) {
                    html += '<div style="padding: 6px 10px; background: #f8fafc; font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Completed Invoices</div>';
                    invoices.forEach(inv => {
                        html += `
                            <div class="autocomplete-item omni-item" onclick="selectInvoiceForReturn('${esc(inv.invoice_no)}')">
                                <div>
                                    <strong style="color: #0f172a; font-family: monospace; font-size: 13px;">${esc(inv.invoice_no)}</strong>
                                    <span style="color: #64748b; font-size: 11px; margin-left: 8px;">${esc(inv.created_at)}</span>
                                    <div style="font-size: 11px; color: #334155; margin-top: 2px;">
                                        Customer: <strong>${esc(inv.customer_name)}</strong> ${inv.customer_phone ? '(' + esc(inv.customer_phone) + ')' : ''}
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <strong style="color: #16a34a; font-family: monospace; font-size: 13px;">LKR ${parseFloat(inv.total_amount).toLocaleString('en-US', {minimumFractionDigits:2})}</strong>
                                    <div style="margin-top: 3px;">
                                        <button type="button" class="inv-btn inv-btn-primary inv-btn-sm">Select Invoice →</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }

                drop.innerHTML = html;
                drop.style.display = 'block';
                omniActiveItemIndex = -1;

            } catch (err) {
                console.error(err);
            }
        }, 220);
    }

    function onOmniSearchKeydown(e) {
        const drop = document.getElementById('omniSearchResults');
        if (drop.style.display === 'none') return;

        const items = drop.querySelectorAll('.omni-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            omniActiveItemIndex = (omniActiveItemIndex + 1) % items.length;
            highlightOmniItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            omniActiveItemIndex = (omniActiveItemIndex - 1 + items.length) % items.length;
            highlightOmniItem(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (omniActiveItemIndex >= 0 && items[omniActiveItemIndex]) {
                items[omniActiveItemIndex].click();
            } else if (items.length === 1) {
                items[0].click();
            }
        } else if (e.key === 'Escape') {
            drop.style.display = 'none';
        }
    }

    function highlightOmniItem(items) {
        items.forEach((item, idx) => {
            item.classList.toggle('active', idx === omniActiveItemIndex);
            if (idx === omniActiveItemIndex) item.scrollIntoView({ block: 'nearest' });
        });
    }

    function clearOmniSearch() {
        document.getElementById('omniSearchInput').value = '';
        document.getElementById('omniSearchResults').style.display = 'none';
    }

    async function loadCustomerInvoices(customerId, custName, custPhone) {
        const drop = document.getElementById('omniSearchResults');
        drop.innerHTML = '<div style="padding: 12px; text-align: center; color: #64748b;">Loading invoices for ' + esc(custName) + '...</div>';

        try {
            const res = await fetch(`returns_api.php?action=get_customer_invoices&customer_id=${customerId}`);
            const data = await res.json();

            if (!data.success || !data.invoices || !data.invoices.length) {
                drop.innerHTML = `<div style="padding: 12px; text-align: center; color: #94a3b8;">No completed invoices found for ${esc(custName)}.</div>`;
                return;
            }

            let html = `
                <div style="padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 800; color: #0f172a;">${esc(custName)}'s Invoices (${data.invoices.length})</span>
                    <button type="button" class="inv-btn inv-btn-ghost inv-btn-sm" onclick="onOmniSearchInput(document.getElementById('omniSearchInput').value)">Back</button>
                </div>
            `;

            data.invoices.forEach(inv => {
                html += `
                    <div class="autocomplete-item omni-item" onclick="selectInvoiceForReturn('${esc(inv.invoice_no)}')">
                        <div>
                            <strong style="color: #0f172a; font-family: monospace; font-size: 13px;">${esc(inv.invoice_no)}</strong>
                            <span style="color: #64748b; font-size: 11px; margin-left: 8px;">${esc(inv.created_at)}</span>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Items: ${inv.item_count} items</div>
                        </div>
                        <div style="text-align: right;">
                            <strong style="color: #16a34a; font-family: monospace; font-size: 13px;">LKR ${parseFloat(inv.total_amount).toLocaleString('en-US', {minimumFractionDigits:2})}</strong>
                            <div style="margin-top: 3px;">
                                <button type="button" class="inv-btn inv-btn-primary inv-btn-sm">Select Invoice →</button>
                            </div>
                        </div>
                    </div>
                `;
            });

            drop.innerHTML = html;
            drop.style.display = 'block';

        } catch (e) {
            console.error(e);
        }
    }

    async function selectInvoiceForReturn(invoiceNo) {
        document.getElementById('omniSearchResults').style.display = 'none';

        try {
            const res = await fetch(`returns_api.php?action=search_invoice&invoice_no=${encodeURIComponent(invoiceNo)}`);
            const data = await res.json();

            if (!data.success) {
                alert(data.message || 'Invoice not found.');
                return;
            }

            activeInvoiceSale = data.sale;

            // Populate banner
            document.getElementById('bannerInvoiceNo').textContent = '#' + activeInvoiceSale.invoice_no;
            document.getElementById('bannerCustomerName').textContent = activeInvoiceSale.customer_name + (activeInvoiceSale.customer_phone ? ' (' + activeInvoiceSale.customer_phone + ')' : '');
            document.getElementById('bannerInvoiceDate').textContent = activeInvoiceSale.created_at;
            document.getElementById('bannerInvoiceTotal').textContent = 'LKR ' + parseFloat(activeInvoiceSale.total_amount).toLocaleString('en-US', {minimumFractionDigits:2});
            document.getElementById('selectedInvoiceBanner').style.display = 'flex';

            // Populate Cart items from invoice
            invoiceReturnCart = (activeInvoiceSale.items || []).map(item => ({
                sale_item_id: item.id,
                product_id: item.product_id,
                product_name: item.product_name,
                lot_id: item.lot_id,
                lot_number: item.lot_number,
                sold_qty: Math.round(parseFloat(item.quantity) || 0),
                available_qty: Math.round(parseFloat(item.available_return_qty) || 0),
                return_qty: 0,
                unit_price: parseFloat(item.unit_price) || 0,
                cost_price: parseFloat(item.unit_cost) || 0,
                item_condition: 'good',
                reason: 'Customer Return'
            }));

            // Reset settlement mode to default Exchange (No refund)
            document.querySelector('input[name="invoice_settlement_mode"][value="exchange"]').checked = true;
            onInvoiceSettlementModeChange('exchange');

            renderInvoiceReturnTable();

        } catch (e) {
            alert('Error loading invoice: ' + e.message);
        }
    }

    function clearSelectedInvoice() {
        activeInvoiceSale = null;
        invoiceReturnCart = [];
        document.getElementById('selectedInvoiceBanner').style.display = 'none';
        document.getElementById('invoiceItemsTbody').innerHTML = '<tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem;">Search by Customer or Invoice Number above to load sold products.</td></tr>';
        updateInvoiceTotals();
    }

    function toggleInvoiceItemSelection(idx, isChecked) {
        if (!invoiceReturnCart[idx]) return;
        invoiceReturnCart[idx].selected = isChecked;
        if (isChecked && invoiceReturnCart[idx].return_qty <= 0) {
            invoiceReturnCart[idx].return_qty = Math.min(1, invoiceReturnCart[idx].available_qty);
        } else if (!isChecked) {
            invoiceReturnCart[idx].return_qty = 0;
        }
        renderInvoiceReturnTable();
    }

    function toggleSelectAllInvoiceItems(isChecked) {
        invoiceReturnCart.forEach(item => {
            if (item.available_qty > 0) {
                item.selected = isChecked;
                item.return_qty = isChecked ? Math.min(1, item.available_qty) : 0;
            }
        });
        renderInvoiceReturnTable();
    }

    function renderInvoiceReturnTable() {
        const tbody = document.getElementById('invoiceItemsTbody');
        if (!invoiceReturnCart.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem;">No items found on this invoice.</td></tr>';
            updateInvoiceTotals();
            return;
        }

        let html = '';
        invoiceReturnCart.forEach((item, idx) => {
            const lineTotal = item.return_qty * item.unit_price;
            const isFullyReturned = (item.available_qty <= 0);
            const isRowSelected = (item.selected || item.return_qty > 0);

            html += `
                <tr style="${isRowSelected ? 'background: #f0fdf4;' : ''}">
                    <td class="col-center">
                        <input type="checkbox" class="item-select-checkbox" data-idx="${idx}" ${isRowSelected ? 'checked' : ''} ${isFullyReturned ? 'disabled' : ''} onchange="toggleInvoiceItemSelection(${idx}, this.checked)" style="cursor: pointer;">
                    </td>
                    <td>
                        <strong style="color: #0f172a;">${esc(item.product_name)}</strong><br>
                        <span class="inv-code-pill" style="margin-top: 2px; display: inline-block; background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">Sold Lot: ${esc(item.lot_number)}</span>
                    </td>
                    <td style="font-size: 12px; color: #475569;">
                        ${isFullyReturned ? `
                            <span class="prod-badge" style="background: #fee2e2; color: #b91c1c;">Returned (${item.sold_qty})</span>
                        ` : `
                            <strong>${item.sold_qty}</strong> sold / <strong style="color: #16a34a;">${item.available_qty}</strong> avail
                        `}
                    </td>
                    <td>
                        <input type="number" step="1" min="0" max="${item.available_qty}" class="inv-input inv-input-sm qty-input" style="width: 85px; font-weight: 800; font-family: monospace;" value="${item.return_qty > 0 ? parseInt(item.return_qty, 10) : ''}" placeholder="0" ${isFullyReturned ? 'disabled' : ''} oninput="updateInvoiceReturnQty(${idx}, this.value)">
                    </td>
                    <td>
                        <span style="font-weight: 700; font-family: monospace;">LKR ${item.unit_price.toFixed(2)}</span>
                    </td>
                    <td>
                        <select class="inv-select inv-input-sm" style="width: 125px; font-weight: 700;" onchange="invoiceReturnCart[${idx}].item_condition = this.value">
                            <option value="good" ${item.item_condition === 'good' ? 'selected' : ''}>Good (Restock)</option>
                            <option value="damaged" ${item.item_condition === 'damaged' ? 'selected' : ''}>Damaged (Loss)</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="inv-input inv-input-sm" value="${esc(item.reason)}" placeholder="Return reason..." oninput="invoiceReturnCart[${idx}].reason = this.value">
                    </td>
                    <td class="col-right">
                        <strong style="color: #0f172a; font-size: 13px; font-family: monospace;">LKR ${lineTotal.toFixed(2)}</strong>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        updateInvoiceTotals();
    }

    function updateInvoiceReturnQty(idx, val) {
        let q = Math.max(0, parseInt(val, 10) || 0);
        if (q > invoiceReturnCart[idx].available_qty) {
            alert(`Cannot return more than available quantity (${invoiceReturnCart[idx].available_qty}).`);
            q = invoiceReturnCart[idx].available_qty;
            const input = document.querySelector(`#invoiceItemsTbody tr:nth-child(${idx + 1}) .qty-input`);
            if (input) input.value = q > 0 ? q : '';
        }
        invoiceReturnCart[idx].return_qty = q;
        invoiceReturnCart[idx].selected = (q > 0);
        const row = document.querySelector(`#invoiceItemsTbody tr:nth-child(${idx + 1})`);
        if (row) {
            const cb = row.querySelector('.item-select-checkbox');
            if (cb) cb.checked = (q > 0);
            row.style.background = (q > 0) ? '#f0fdf4' : '';
            const lineTotalCell = row.querySelector('.col-right strong');
            if (lineTotalCell) lineTotalCell.textContent = 'LKR ' + (q * invoiceReturnCart[idx].unit_price).toFixed(2);
        }
        updateInvoiceTotals();
    }

    function updateInvoiceTotals() {
        let total = 0;
        invoiceReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
        total = round2(total);

        document.getElementById('invoiceReturnTotalVal').innerText = `LKR ${total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
        recalcInvoiceRefundSplits();
    }

    function onInvoiceSettlementModeChange(mode) {
        document.getElementById('invoiceModeExchangeLabel').classList.toggle('selected', mode === 'exchange');
        document.getElementById('invoiceModeCreditLabel').classList.toggle('selected', mode === 'credit');
        document.getElementById('invoiceModeRefundLabel').classList.toggle('selected', mode === 'refund');

        const box = document.getElementById('invoiceRefundBreakdownBox');
        if (mode === 'refund') {
            box.style.display = 'block';
            let total = 0;
            invoiceReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
            total = round2(total);

            if (invoiceRefundSplits.length === 0) {
                invoiceRefundSplits = [{
                    method: 'cash_drawer',
                    amount: total,
                    ref: ''
                }];
                renderInvoiceRefundSplits();
            } else {
                recalcInvoiceRefundSplits();
            }
        } else {
            box.style.display = 'none';
        }
    }

    function addInvoiceRefundSplitRow() {
        let total = 0;
        invoiceReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
        total = round2(total);

        const currentAlloc = invoiceRefundSplits.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
        const rem = Math.max(0, round2(total - currentAlloc));

        let defaultMethod = 'cash_drawer';
        if (invoiceRefundSplits.length === 1 && invoiceRefundSplits[0].method === 'cash_drawer') {
            if (window.returnBankAccounts && window.returnBankAccounts.length > 0) {
                defaultMethod = `Bank:${window.returnBankAccounts[0].id}`;
            } else {
                defaultMethod = 'cheque';
            }
        }

        invoiceRefundSplits.push({
            method: defaultMethod,
            amount: rem,
            ref: ''
        });

        renderInvoiceRefundSplits();
    }

    function removeInvoiceRefundSplitRow(idx) {
        if (invoiceRefundSplits.length <= 1) return;
        invoiceRefundSplits.splice(idx, 1);
        renderInvoiceRefundSplits();
    }

    function renderInvoiceRefundSplits() {
        const tbody = document.getElementById('invoiceRefundSplitsBody');
        if (!tbody) return;

        let html = '';
        invoiceRefundSplits.forEach((r, idx) => {
            html += `
                <tr style="border-bottom: 1px solid #f1f5f9; height: 38px;">
                    <td style="padding: 4px 10px;">
                        <select class="inv-select inv-input-sm" style="width: 100%; font-weight: 700;" onchange="invoiceRefundSplits[${idx}].method = this.value; renderInvoiceRefundSplits();">
                            ${getCOAPaymentMethodOptions(r.method)}
                        </select>
                    </td>
                    <td style="padding: 4px 10px;">
                        <input type="number" step="0.01" min="0" class="inv-input inv-input-sm" style="width: 100%; text-align: right; font-weight: 800; font-family: monospace;" value="${r.amount > 0 ? parseFloat(r.amount).toFixed(2) : ''}" placeholder="0.00" oninput="invoiceRefundSplits[${idx}].amount = Math.max(0, parseFloat(this.value)||0); recalcInvoiceRefundSplits();">
                    </td>
                    <td style="padding: 4px 10px;">
                        <input type="text" class="inv-input inv-input-sm" style="width: 100%;" value="${esc(r.ref || '')}" placeholder="Ref No / Notes..." oninput="invoiceRefundSplits[${idx}].ref = this.value;">
                    </td>
                    <td style="padding: 4px 6px; text-align: center;">
                        ${invoiceRefundSplits.length > 1 ? `
                            <button type="button" onclick="removeInvoiceRefundSplitRow(${idx})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px;">&times;</button>
                        ` : `-`}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        recalcInvoiceRefundSplits();
    }

    function recalcInvoiceRefundSplits() {
        const mode = document.querySelector('input[name="invoice_settlement_mode"]:checked')?.value || 'exchange';
        let total = 0;
        invoiceReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
        total = round2(total);

        const allocTot = round2(invoiceRefundSplits.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0));
        const diff = round2(total - allocTot);

        const textEl = document.getElementById('invoiceRefundAllocatedText');
        const badgeEl = document.getElementById('invoiceRefundDiffBadge');
        const submitBtn = document.getElementById('btnSubmitInvoiceReturn');

        if (textEl) {
            textEl.innerText = `Allocated: LKR ${allocTot.toFixed(2)} / Total Refund: LKR ${total.toFixed(2)}`;
        }

        if (mode === 'refund') {
            if (Math.abs(diff) < 0.01 && total > 0) {
                if (badgeEl) {
                    badgeEl.style.background = '#dcfce7';
                    badgeEl.style.color = '#15803d';
                    badgeEl.innerText = '✓ Balanced (100%)';
                }
                if (submitBtn) submitBtn.disabled = false;
            } else {
                if (badgeEl) {
                    badgeEl.style.background = '#fee2e2';
                    badgeEl.style.color = '#b91c1c';
                    badgeEl.innerText = (diff > 0) ? `Remaining: LKR ${diff.toFixed(2)}` : `Over-allocated: LKR ${Math.abs(diff).toFixed(2)}`;
                }
                if (submitBtn) submitBtn.disabled = (total <= 0 || Math.abs(diff) >= 0.01);
            }
        } else {
            if (submitBtn) submitBtn.disabled = (total <= 0);
        }
    }

    async function submitInvoiceReturn() {
        const activeItems = invoiceReturnCart.filter(i => (i.selected || i.return_qty > 0) && i.return_qty > 0);
        if (!activeItems.length) {
            alert('Please select at least one item to return with quantity greater than zero.');
            return;
        }

        const mode = document.querySelector('input[name="invoice_settlement_mode"]:checked')?.value || 'exchange';
        const customerId = activeInvoiceSale ? parseInt(activeInvoiceSale.customer_id) : 1;
        const notes = document.getElementById('invoiceReturnNotes').value.trim();

        if (mode === 'credit' && customerId <= 1) {
            alert('Customer Account Credit requires a registered customer account. This invoice was billed to Walk-in Customer.');
            return;
        }

        let refundsPayload = [];
        if (mode === 'refund') {
            let total = 0;
            activeItems.forEach(i => { total += (i.return_qty * i.unit_price); });
            total = round2(total);

            const allocTot = round2(invoiceRefundSplits.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0));
            if (Math.abs(total - allocTot) > 0.01) {
                alert(`Refund settlement splits (LKR ${allocTot.toFixed(2)}) must equal total return amount (LKR ${total.toFixed(2)}).`);
                return;
            }

            refundsPayload = invoiceRefundSplits.map(r => {
                let m = r.method;
                let bankId = null;
                if (m.startsWith('Bank:')) {
                    bankId = parseInt(m.replace('Bank:', ''));
                    m = 'bank';
                }
                return {
                    refund_method: m,
                    bank_account_id: bankId,
                    amount: parseFloat(r.amount) || 0,
                    reference_no: r.ref || ''
                };
            });
        }

        const btn = document.getElementById('btnSubmitInvoiceReturn');
        btn.disabled = true;

        const payload = {
            sale_id: activeInvoiceSale ? parseInt(activeInvoiceSale.id) : null,
            customer_id: customerId,
            return_type: 'return_only',
            settlement_mode: mode,
            items: activeItems.map(i => ({
                sale_item_id: parseInt(i.sale_item_id || i.id),
                product_id: parseInt(i.product_id),
                lot_id: parseInt(i.lot_id),
                quantity: parseInt(i.return_qty, 10),
                unit_price: parseFloat(i.unit_price) || 0,
                cost_price: parseFloat(i.cost_price) || 0,
                item_condition: i.item_condition || 'good',
                reason: i.reason || 'Customer Return'
            })),
            refunds: refundsPayload,
            notes: notes || (mode === 'exchange' ? 'POS Exchange Return (No cash payout)' : 'Invoice Return')
        };

        try {
            const res = await fetch('returns_api.php?action=process_return', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                alert(`SUCCESS: Sales Return ${data.return_no} completed successfully!`);
                location.reload();
            } else {
                alert(`RETURN ERROR: ${data.message}`);
                btn.disabled = false;
            }
        } catch (e) {
            alert('Server Communication Error: ' + e.message);
            btn.disabled = false;
        }
    }

    function resetInvoiceReturnForm() {
        clearSelectedInvoice();
        clearOmniSearch();
        document.getElementById('invoiceReturnNotes').value = '';
    }


    /* =========================================================================
     * SECTION 2: MANUAL / NO-INVOICE PRODUCT RETURN (Requirements 2, 3 & 4)
     * ========================================================================= */
    function onManualProductSearchInput(val) {
        clearTimeout(manualSearchDebounceTimer);
        const q = val.trim();
        const drop = document.getElementById('manualProductDropdown');

        if (!q || q.length < 1) {
            drop.style.display = 'none';
            drop.innerHTML = '';
            return;
        }

        manualSearchDebounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`returns_api.php?action=search_sold_products&query=${encodeURIComponent(q)}`);
                const data = await res.json();

                if (!data.success || !data.products || !data.products.length) {
                    drop.innerHTML = '<div style="padding: 10px 14px; color: #94a3b8; font-size: 12px;">No matching products found.</div>';
                    drop.style.display = 'block';
                    return;
                }

                let html = '';
                data.products.forEach((p, idx) => {
                    const lotsCount = (p.lots || []).length;
                    const samplePrice = lotsCount > 0 ? parseFloat(p.lots[0].selling_price).toFixed(2) : '0.00';

                    html += `
                        <div class="autocomplete-item manual-item" data-index="${idx}" onclick="openProductLotSelection(${idx}, ${JSON.stringify(p).replace(/"/g, '&quot;')})">
                            <div>
                                <strong style="color: #0f172a;">${esc(p.product_name)}</strong>
                                <span class="inv-code-pill" style="margin-left: 6px;">${esc(p.barcode)}</span>
                                <span style="font-size: 11px; color: #64748b; margin-left: 6px;">(${lotsCount} Lot${lotsCount > 1 ? 's' : ''})</span>
                            </div>
                            <div>
                                <strong style="color: #16a34a; font-family: monospace;">LKR ${samplePrice}</strong>
                                <button type="button" class="inv-btn inv-btn-ghost inv-btn-sm" style="margin-left: 8px;">Select Lot →</button>
                            </div>
                        </div>
                    `;
                });

                drop.innerHTML = html;
                drop.style.display = 'block';
                manualActiveItemIndex = -1;

            } catch (err) {
                console.error(err);
            }
        }, 200);
    }

    function onManualProductSearchKeydown(e) {
        const drop = document.getElementById('manualProductDropdown');
        if (drop.style.display === 'none') return;

        const items = drop.querySelectorAll('.manual-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            manualActiveItemIndex = (manualActiveItemIndex + 1) % items.length;
            highlightManualItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            manualActiveItemIndex = (manualActiveItemIndex - 1 + items.length) % items.length;
            highlightManualItem(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (manualActiveItemIndex >= 0 && items[manualActiveItemIndex]) {
                items[manualActiveItemIndex].click();
            } else if (items.length === 1) {
                items[0].click();
            }
        } else if (e.key === 'Escape') {
            drop.style.display = 'none';
        }
    }

    function highlightManualItem(items) {
        items.forEach((item, idx) => {
            item.classList.toggle('active', idx === manualActiveItemIndex);
            if (idx === manualActiveItemIndex) item.scrollIntoView({ block: 'nearest' });
        });
    }

    // Open Lot Selection Modal (Requirement 2)
    function openProductLotSelection(idx, product) {
        document.getElementById('manualProductDropdown').style.display = 'none';
        currentSelectedProductForLots = product;

        document.getElementById('lotModalProductName').innerText = `${product.product_name} (${product.barcode})`;
        const listEl = document.getElementById('lotModalList');

        const lots = product.lots || [];
        if (!lots.length) {
            alert('No lots found for this product.');
            return;
        }

        let html = '';
        lots.forEach((lot, lIdx) => {
            html += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'" onclick="confirmLotSelection(${lIdx})">
                    <div>
                        <span class="inv-code-pill" style="font-size: 12px;">Lot: ${esc(lot.lot_number)}</span>
                        <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Stock Balance: <strong>${Math.round(parseFloat(lot.remaining_quantity) || 0)}</strong> units</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <strong style="color: #16a34a; font-size: 13px; font-family: monospace;">LKR ${parseFloat(lot.selling_price).toFixed(2)}</strong>
                        <button type="button" class="inv-btn inv-btn-primary inv-btn-sm">Select Lot</button>
                    </div>
                </div>
            `;
        });

        listEl.innerHTML = html;
        document.getElementById('lotSelectionModal').style.display = 'flex';
    }

    function confirmLotSelection(lotIdx) {
        if (!currentSelectedProductForLots) return;
        const product = currentSelectedProductForLots;
        const lot = product.lots[lotIdx];

        addManualReturnCartItem(product.product_id, product.product_name, lot.lot_id, lot.lot_number, lot.selling_price, lot.cost);
        closeLotModal();
        document.getElementById('manualProductInput').value = '';
    }

    function closeLotModal() {
        document.getElementById('lotSelectionModal').style.display = 'none';
        currentSelectedProductForLots = null;
    }

    function addManualReturnCartItem(productId, productName, lotId, lotNumber, sellingPrice, cost) {
        const existing = manualReturnCart.find(i => i.product_id === productId && i.lot_id === lotId);
        if (existing) {
            existing.return_qty += 1;
        } else {
            manualReturnCart.push({
                sale_item_id: null,
                product_id: productId,
                product_name: productName,
                lot_id: lotId,
                lot_number: lotNumber,
                return_qty: 1,
                unit_price: parseFloat(sellingPrice) || 0,
                cost_price: parseFloat(cost) || 0,
                item_condition: 'good',
                reason: 'No-Invoice Return'
            });
        }
        renderManualReturnTable();
    }

    // Render Manual Return Table - Notice NO EDITABLE UNIT COST! (Requirement 3)
    function renderManualReturnTable() {
        const tbody = document.getElementById('manualItemsTbody');
        if (!manualReturnCart.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem;">Type product name or barcode above to add items for return.</td></tr>';
            updateManualTotals();
            return;
        }

        let html = '';
        manualReturnCart.forEach((item, idx) => {
            const lineTotal = item.return_qty * item.unit_price;

            html += `
                <tr>
                    <td>
                        <strong style="color: #0f172a;">${esc(item.product_name)}</strong><br>
                        <span class="inv-code-pill" style="margin-top: 2px; display: inline-block;">Lot: ${esc(item.lot_number)}</span>
                    </td>
                    <td>
                        <input type="number" step="1" min="1" class="inv-input inv-input-sm qty-input" style="width: 85px; font-weight: 800; font-family: monospace;" value="${parseInt(item.return_qty, 10) || 1}" oninput="updateManualReturnQty(${idx}, this.value)">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="inv-input inv-input-sm" style="width: 100px; font-weight: 700; font-family: monospace;" value="${item.unit_price.toFixed(2)}" oninput="updateManualUnitPrice(${idx}, this.value)">
                    </td>
                    <td>
                        <select class="inv-select inv-input-sm" style="width: 125px; font-weight: 700;" onchange="manualReturnCart[${idx}].item_condition = this.value">
                            <option value="good" ${item.item_condition === 'good' ? 'selected' : ''}>Good (Restock)</option>
                            <option value="damaged" ${item.item_condition === 'damaged' ? 'selected' : ''}>Damaged (Loss)</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="inv-input inv-input-sm" value="${esc(item.reason)}" placeholder="Reason..." oninput="manualReturnCart[${idx}].reason = this.value">
                    </td>
                    <td class="col-right">
                        <strong style="color: #0f172a; font-size: 13px; font-family: monospace;">LKR ${lineTotal.toFixed(2)}</strong>
                    </td>
                    <td class="col-center">
                        <button type="button" class="inv-btn inv-btn-danger inv-btn-sm" style="width: 26px; height: 26px; padding: 0; border-radius: 50%;" onclick="manualReturnCart.splice(${idx}, 1); renderManualReturnTable();">&times;</button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        updateManualTotals();
    }

    function updateManualReturnQty(idx, val) {
        let q = Math.max(1, parseInt(val, 10) || 1);
        manualReturnCart[idx].return_qty = q;
        const lineTotal = q * manualReturnCart[idx].unit_price;
        const row = document.querySelector(`#manualItemsTbody tr:nth-child(${idx + 1})`);
        if (row) {
            const lineTotalCell = row.querySelector('.col-right strong');
            if (lineTotalCell) lineTotalCell.textContent = 'LKR ' + lineTotal.toFixed(2);
        }
        updateManualTotals();
    }

    function updateManualUnitPrice(idx, val) {
        let p = Math.max(0, parseFloat(val) || 0);
        manualReturnCart[idx].unit_price = p;
        const lineTotal = manualReturnCart[idx].return_qty * p;
        const row = document.querySelector(`#manualItemsTbody tr:nth-child(${idx + 1})`);
        if (row) {
            const lineTotalCell = row.querySelector('.col-right strong');
            if (lineTotalCell) lineTotalCell.textContent = 'LKR ' + lineTotal.toFixed(2);
        }
        updateManualTotals();
    }

    function updateManualTotals() {
        let total = 0;
        manualReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
        total = round2(total);

        document.getElementById('manualReturnTotalVal').innerText = `LKR ${total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
        recalcManualRefundSplits();
    }

    function onManualSettlementModeChange(mode) {
        document.getElementById('manualModeExchangeLabel').classList.toggle('selected', mode === 'exchange');
        document.getElementById('manualModeCreditLabel').classList.toggle('selected', mode === 'credit');
        document.getElementById('manualModeRefundLabel').classList.toggle('selected', mode === 'refund');

        const box = document.getElementById('manualRefundBreakdownBox');
        if (mode === 'refund') {
            box.style.display = 'block';
            let total = 0;
            manualReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
            total = round2(total);

            if (manualRefundSplits.length === 0) {
                manualRefundSplits = [{
                    method: 'cash_drawer',
                    amount: total,
                    ref: ''
                }];
                renderManualRefundSplits();
            } else {
                recalcManualRefundSplits();
            }
        } else {
            box.style.display = 'none';
        }
    }

    function addManualRefundSplitRow() {
        let total = 0;
        manualReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
        total = round2(total);

        const currentAlloc = manualRefundSplits.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
        const rem = Math.max(0, round2(total - currentAlloc));

        let defaultMethod = 'cash_drawer';
        if (manualRefundSplits.length === 1 && manualRefundSplits[0].method === 'cash_drawer') {
            if (window.returnBankAccounts && window.returnBankAccounts.length > 0) {
                defaultMethod = `Bank:${window.returnBankAccounts[0].id}`;
            } else {
                defaultMethod = 'cheque';
            }
        }

        manualRefundSplits.push({
            method: defaultMethod,
            amount: rem,
            ref: ''
        });

        renderManualRefundSplits();
    }

    function removeManualRefundSplitRow(idx) {
        if (manualRefundSplits.length <= 1) return;
        manualRefundSplits.splice(idx, 1);
        renderManualRefundSplits();
    }

    function renderManualRefundSplits() {
        const tbody = document.getElementById('manualRefundSplitsBody');
        if (!tbody) return;

        let html = '';
        manualRefundSplits.forEach((r, idx) => {
            html += `
                <tr style="border-bottom: 1px solid #f1f5f9; height: 38px;">
                    <td style="padding: 4px 10px;">
                        <select class="inv-select inv-input-sm" style="width: 100%; font-weight: 700;" onchange="manualRefundSplits[${idx}].method = this.value; renderManualRefundSplits();">
                            ${getCOAPaymentMethodOptions(r.method)}
                        </select>
                    </td>
                    <td style="padding: 4px 10px;">
                        <input type="number" step="0.01" min="0" class="inv-input inv-input-sm" style="width: 100%; text-align: right; font-weight: 800; font-family: monospace;" value="${r.amount > 0 ? parseFloat(r.amount).toFixed(2) : ''}" placeholder="0.00" oninput="manualRefundSplits[${idx}].amount = Math.max(0, parseFloat(this.value)||0); recalcManualRefundSplits();">
                    </td>
                    <td style="padding: 4px 10px;">
                        <input type="text" class="inv-input inv-input-sm" style="width: 100%;" value="${esc(r.ref || '')}" placeholder="Ref No / Notes..." oninput="manualRefundSplits[${idx}].ref = this.value;">
                    </td>
                    <td style="padding: 4px 6px; text-align: center;">
                        ${manualRefundSplits.length > 1 ? `
                            <button type="button" onclick="removeManualRefundSplitRow(${idx})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px;">&times;</button>
                        ` : `-`}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        recalcManualRefundSplits();
    }

    function recalcManualRefundSplits() {
        const mode = document.querySelector('input[name="manual_settlement_mode"]:checked')?.value || 'exchange';
        let total = 0;
        manualReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
        total = round2(total);

        const allocTot = round2(manualRefundSplits.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0));
        const diff = round2(total - allocTot);

        const textEl = document.getElementById('manualRefundAllocatedText');
        const badgeEl = document.getElementById('manualRefundDiffBadge');
        const submitBtn = document.getElementById('btnSubmitManualReturn');

        if (textEl) {
            textEl.innerText = `Allocated: LKR ${allocTot.toFixed(2)} / Total Refund: LKR ${total.toFixed(2)}`;
        }

        if (mode === 'refund') {
            if (Math.abs(diff) < 0.01 && total > 0) {
                if (badgeEl) {
                    badgeEl.style.background = '#dcfce7';
                    badgeEl.style.color = '#15803d';
                    badgeEl.innerText = '✓ Balanced (100%)';
                }
                if (submitBtn) submitBtn.disabled = false;
            } else {
                if (badgeEl) {
                    badgeEl.style.background = '#fee2e2';
                    badgeEl.style.color = '#b91c1c';
                    badgeEl.innerText = (diff > 0) ? `Remaining: LKR ${diff.toFixed(2)}` : `Over-allocated: LKR ${Math.abs(diff).toFixed(2)}`;
                }
                if (submitBtn) submitBtn.disabled = (total <= 0 || Math.abs(diff) >= 0.01);
            }
        } else {
            if (submitBtn) submitBtn.disabled = (total <= 0);
        }
    }

    async function submitManualReturn() {
        if (!manualReturnCart.length) {
            alert('Please select at least one item to return.');
            return;
        }

        const mode = document.querySelector('input[name="manual_settlement_mode"]:checked')?.value || 'exchange';
        const customerId = parseInt(document.getElementById('manualCustomerSelect').value) || 1;
        const notes = document.getElementById('manualReturnNotes').value.trim();

        if (mode === 'credit' && customerId <= 1) {
            alert('Customer Account Credit requires a registered customer account.');
            return;
        }

        let refundsPayload = [];
        if (mode === 'refund') {
            let total = 0;
            manualReturnCart.forEach(i => { total += (i.return_qty * i.unit_price); });
            total = round2(total);

            const allocTot = round2(manualRefundSplits.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0));
            if (Math.abs(total - allocTot) > 0.01) {
                alert(`Refund settlement splits (LKR ${allocTot.toFixed(2)}) must equal total return amount (LKR ${total.toFixed(2)}).`);
                return;
            }

            refundsPayload = manualRefundSplits.map(r => {
                let m = r.method;
                let bankId = null;
                if (m.startsWith('Bank:')) {
                    bankId = parseInt(m.replace('Bank:', ''));
                    m = 'bank';
                }
                return {
                    refund_method: m,
                    bank_account_id: bankId,
                    amount: parseFloat(r.amount) || 0,
                    reference_no: r.ref || ''
                };
            });
        }

        const btn = document.getElementById('btnSubmitManualReturn');
        btn.disabled = true;

        const payload = {
            sale_id: null,
            customer_id: customerId,
            return_type: 'return_only',
            settlement_mode: mode,
            items: manualReturnCart.map(i => ({ ...i, quantity: parseInt(i.return_qty, 10) })),
            refunds: refundsPayload,
            notes: notes || (mode === 'exchange' ? 'Manual POS Exchange Return (No cash payout)' : 'Manual Return')
        };

        try {
            const res = await fetch('returns_api.php?action=process_return', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                alert(`SUCCESS: Manual Return ${data.return_no} completed successfully!`);
                location.reload();
            } else {
                alert(`RETURN ERROR: ${data.message}`);
                btn.disabled = false;
            }
        } catch (e) {
            alert('Server Communication Error: ' + e.message);
            btn.disabled = false;
        }
    }

    function resetManualForm() {
        manualReturnCart = [];
        document.getElementById('manualProductInput').value = '';
        document.getElementById('manualProductDropdown').style.display = 'none';
        document.getElementById('manualReturnNotes').value = '';
        renderManualReturnTable();
    }


    /* =========================================================================
     * SECTION 3: RETURN DETAILS MODAL
     * ========================================================================= */
    async function viewReturnDetails(returnId) {
        document.getElementById('returnDetailsModal').style.display = 'flex';
        document.getElementById('modalReturnBody').innerHTML = '<div style="padding: 2rem; text-align: center; color: #64748b;">Loading return details...</div>';

        try {
            const res = await fetch(`returns_api.php?action=get_return_details&id=${returnId}`);
            const data = await res.json();

            if (!data.success) {
                document.getElementById('modalReturnBody').innerHTML = `<div style="background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: 12px; font-weight: 600;">${data.message}</div>`;
                return;
            }

            const r = data.return;
            let html = `
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 1rem; font-size: 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px;">
                    <div><span style="color: #64748b; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">Return No:</span><br><strong style="font-family: monospace; font-size: 13px; color: #0f172a;">${esc(r.return_no)}</strong></div>
                    <div><span style="color: #64748b; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">Date:</span><br><strong>${esc(r.created_at)}</strong></div>
                    <div><span style="color: #64748b; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">Original Invoice:</span><br><span class="inv-code-pill">${esc(r.original_invoice_no || 'NO-INVOICE')}</span></div>
                    <div><span style="color: #64748b; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">Customer:</span><br><strong>${esc(r.customer_name || 'Walk-in')}</strong></div>
                    <div><span style="color: #64748b; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">Processed By:</span><br><strong>${esc(r.created_by_name)}</strong></div>
                </div>

                <div style="font-size: 11px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 6px;">Returned Items</div>
                <div class="return-table-wrap" style="margin-bottom: 1rem;">
                    <table class="return-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Lot</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th class="col-right">Total</th>
                                <th>Condition</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${r.items.map(i => `
                                <tr>
                                    <td><strong>${esc(i.product_name)}</strong></td>
                                    <td><span class="inv-code-pill">${esc(i.lot_number)}</span></td>
                                    <td><strong>${Math.round(parseFloat(i.quantity) || 0)}</strong></td>
                                    <td style="font-family: monospace;">LKR ${parseFloat(i.unit_price).toFixed(2)}</td>
                                    <td class="col-right"><strong style="font-family: monospace; color: #0f172a;">LKR ${parseFloat(i.total_amount).toFixed(2)}</strong></td>
                                    <td><span class="prod-badge ${i.item_condition === 'good' ? 'prod-badge-active' : ''}">${esc(i.item_condition.toUpperCase())}</span></td>
                                    <td style="color: #64748b; font-size: 11.5px;">${esc(i.reason || 'N/A')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                <div style="font-size: 11px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 6px;">Refund & Settlement Methods</div>
                <div class="return-table-wrap">
                    <table class="return-table">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th class="col-right">Amount</th>
                                <th>Reference / Notes</th>
                                <th>Replacement Voucher</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(r.refunds && r.refunds.length ? r.refunds.map(rf => `
                                <tr>
                                    <td><strong style="color: #0f172a;">${esc(rf.refund_method.toUpperCase())}</strong></td>
                                    <td class="col-right"><strong style="color: #16a34a; font-family: monospace;">LKR ${parseFloat(rf.amount).toFixed(2)}</strong></td>
                                    <td>${esc(rf.reference_no || 'N/A')}</td>
                                    <td>${rf.replacement_voucher_code ? `<span class="inv-code-pill">${esc(rf.replacement_voucher_code)}</span>` : 'N/A'}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #16a34a; font-weight: 700; padding: 1rem;">
                                        🔄 Store Credit / POS Exchange (No Cash Payout)
                                    </td>
                                </tr>
                            `)}
                        </tbody>
                    </table>
                </div>
            `;

            document.getElementById('modalReturnTitle').innerText = `Return Details: ${r.return_no}`;
            document.getElementById('modalReturnBody').innerHTML = html;

        } catch (err) {
            document.getElementById('modalReturnBody').innerHTML = `<div style="background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: 12px; font-weight: 600;">Error: ${err.message}</div>`;
        }
    }

    function closeReturnDetailsModal() {
        document.getElementById('returnDetailsModal').style.display = 'none';
    }
</script>
