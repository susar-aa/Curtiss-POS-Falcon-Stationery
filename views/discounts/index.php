<?php
/**
 * Curtiss POS - Advanced Discount & Promotion Center View
 * 
 * Complies 100% with Curtiss POS Master Rules:
 * - Fixed screen viewport height & internal table scroll
 * - Sticky headers & alternating zebra striping (48px row height)
 * - Floating top header actions container
 * - Interactive theme-matched clickable status badges (28px)
 * - Universal floating search integration (CurtissNav.enablePageSearch)
 * - Server-side pagination (25, 50, 75, 100, 500)
 * - Full target item, category, brand, supplier, and BOGO picker system
 */
?>

<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<style>
/* Modern Curtiss POS Promotion System Styling */
.disc-wrapper {
    width: 100%;
    max-width: 100%;
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    overflow: hidden;
    padding-bottom: 8px;
}

.disc-header {
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 0.85rem;
}

.disc-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.disc-title {
    font-size: 1.35rem;
    font-weight: 900;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.02em;
}

.disc-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.disc-table-panel {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
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
    text-align: left;
    white-space: nowrap;
}

.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background-color 0.15s ease;
    cursor: pointer;
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

.col-center { text-align: center !important; }
.col-right  { text-align: right !important; }

/* Status Badges */
.disc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    user-select: none;
    box-sizing: border-box;
}

.disc-badge-active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.disc-badge-active .dot { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; }

.disc-badge-inactive { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.disc-badge-inactive .dot { width: 6px; height: 6px; border-radius: 50%; background: #dc2626; }

.disc-badge-scheduled { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.disc-badge-scheduled .dot { width: 6px; height: 6px; border-radius: 50%; background: #d97706; }

.disc-badge-expired { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.disc-badge-expired .dot { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; }

.type-pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
}

/* Action buttons */
.disc-act-btn {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease;
    text-decoration: none;
}

.disc-act-edit { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
.disc-act-edit:hover { background: #f1f5f9; border-color: #94a3b8; }

.disc-act-delete { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
.disc-act-delete:hover { background: #fee2e2; border-color: #f87171; }

.disc-act-restore { background: #f0fdf4; color: #16a34a; border-color: #86efac; }
.disc-act-restore:hover { background: #dcfce7; }

/* Modals */
.disc-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
}

.disc-modal-overlay.active { display: flex; }

.disc-modal-box {
    background: #ffffff;
    border-radius: 14px;
    width: 95%;
    max-width: 780px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}

.disc-modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.disc-modal-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; }
.disc-modal-close { background: none; border: none; font-size: 1.4rem; color: #64748b; cursor: pointer; }

.disc-modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.disc-modal-footer {
    padding: 14px 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8fafc;
}

.disc-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.disc-form-full { grid-column: 1 / -1; }

.disc-label {
    display: block;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 5px;
    letter-spacing: 0.04em;
}

.disc-input, .disc-select, .disc-textarea {
    width: 100%;
    height: 38px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.15s ease;
}

.disc-textarea { height: 60px; padding: 8px 12px; resize: vertical; }
.disc-input:focus, .disc-select:focus, .disc-textarea:focus { border-color: #0f172a; }

/* Item Search Dropdown & Selected Items Container */
.item-search-wrapper {
    position: relative;
    width: 100%;
}

.item-search-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    max-height: 220px;
    overflow-y: auto;
    z-index: 100;
    display: none;
}

.item-search-result-row {
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.1s;
}

.item-search-result-row:hover {
    background: #f8fafc;
}

.item-search-result-row:last-child {
    border-bottom: none;
}

.selected-items-panel {
    margin-top: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
}

.selected-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
}

.selected-items-table th {
    background: #e2e8f0;
    padding: 6px 10px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    text-align: left;
}

.selected-items-table td {
    padding: 6px 10px;
    border-top: 1px solid #e2e8f0;
    vertical-align: middle;
}

/* Buttons */
.inv-btn {
    height: 34px;
    min-height: 34px;
    padding: 0 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.inv-btn-primary { background: #0f172a; color: #ffffff; }
.inv-btn-primary:hover { background: #1e293b; }
.inv-btn-ghost { background: #ffffff; color: #0f172a; border-color: #cbd5e1; }
.inv-btn-ghost:hover { background: #f8fafc; border-color: #94a3b8; }
.inv-btn-trash { color: #dc2626; background: #fef2f2; border-color: #fca5a5; }
.inv-btn-trash:hover { background: #fee2e2; border-color: #f87171; }
</style>

<div class="disc-wrapper">
    <!-- Header row (Headline Only) -->
    <div class="disc-header">
        <div class="disc-title-row">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="disc-title">Discount & Promotions</h1>
        </div>

        <!-- Inline Filter Selects -->
        <div class="disc-filter-group">
            <?php if (count($branches) > 1): ?>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Branch:</span>
                <select id="filterBranch" onchange="loadRules(1)" style="height: 34px; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 0 12px; border: 1px solid #cbd5e1;">
                    <option value="all">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                    <option value="<?= (int)$b['id'] ?>" <?= ((int)$b['id'] === $branchId) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Type:</span>
                <select id="filterType" onchange="loadRules(1)" style="height: 34px; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 0 12px; border: 1px solid #cbd5e1;">
                    <option value="">All Types</option>
                    <option value="item">Item-Wise</option>
                    <option value="category">Category-Wise</option>
                    <option value="brand">Brand-Wise</option>
                    <option value="supplier">Supplier-Wise</option>
                    <option value="quantity">Quantity Tiers</option>
                    <option value="bogo">Buy X Get Y (BOGO)</option>
                    <option value="invoice">Invoice Subtotal</option>
                    <option value="coupon">Coupon Code</option>
                    <option value="customer">Customer Offer</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterStatus" onchange="loadRules(1)" style="height: 34px; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 0 12px; border: 1px solid #cbd5e1;">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Data Table Panel & Internal Scroll -->
    <div class="disc-table-panel">
        <div class="prod-table-scroll">
            <table class="prod-table" id="rulesTable">
                <thead>
                    <tr>
                        <th style="width: 85px;">Rule #</th>
                        <th style="min-width: 170px;">Promotion Name</th>
                        <th style="width: 110px;">Type</th>
                        <th style="min-width: 190px;">Applicable Scope</th>
                        <th style="width: 110px;">Discount</th>
                        <th style="width: 65px;" class="col-center">Priority</th>
                        <th style="width: 75px;" class="col-center">Usage</th>
                        <th style="width: 135px;">Validity Period</th>
                        <th style="width: 95px;" class="col-center">Status</th>
                        <th style="width: 120px;" class="col-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="rulesTableBody">
                    <tr><td colspan="10" class="col-center" style="padding: 2.5rem; color: #64748b;">Loading promotions...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Footer Pagination Controls -->
        <div style="flex-shrink: 0; padding: 10px 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b;" id="paginationInfo">Showing 0 of 0</span>
                <select id="pageSizeSelect" onchange="changePageSize(this.value)" style="height: 28px; border-radius: 6px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; padding: 0 8px;">
                    <option value="25" selected>25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="75">75 per page</option>
                    <option value="100">100 per page</option>
                    <option value="500">500 per page</option>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <button type="button" class="inv-btn inv-btn-ghost" id="btnPrevPage" onclick="prevPage()" style="height: 28px; min-height: 28px; padding: 0 10px;">&larr; Prev</button>
                <span style="font-size: 12px; font-weight: 800; color: #0f172a; padding: 0 6px;" id="pageNumberDisplay">1 / 1</span>
                <button type="button" class="inv-btn inv-btn-ghost" id="btnNextPage" onclick="nextPage()" style="height: 28px; min-height: 28px; padding: 0 10px;">Next &rarr;</button>
            </div>
        </div>
    </div>
</div>

<!-- FLOATING TOP-RIGHT HEADER ACTIONS -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="inv-btn inv-btn-ghost" onclick="exportDiscountsCsv()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </button>
    <button type="button" class="inv-btn inv-btn-trash" id="btnToggleTrash" onclick="toggleTrashView()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        <span id="trashBtnText">Trash Bin</span>
    </button>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openNewRuleModal()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        + Create Promotion
    </button>
</div>

<!-- MODAL: CREATE / EDIT PROMOTION RULE -->
<div class="disc-modal-overlay" id="ruleModal">
    <div class="disc-modal-box">
        <div class="disc-modal-header">
            <h3 class="disc-modal-title" id="ruleModalTitle">+ Create New Promotion</h3>
            <button type="button" class="disc-modal-close" onclick="closeRuleModal()">&times;</button>
        </div>
        <form id="ruleForm" onsubmit="submitRuleForm(); return false;">
            <input type="hidden" id="ruleId" name="id" value="">
            <div class="disc-modal-body">
                <div class="disc-form-grid">
                    <div class="disc-form-group disc-form-full">
                        <label class="disc-label">Promotion Name *</label>
                        <input type="text" id="ruleName" name="name" class="disc-input" placeholder="e.g. 15% OFF Stationery or Buy 1 Get 1 Free" required>
                    </div>

                    <div class="disc-form-group">
                        <label class="disc-label">Rule Type *</label>
                        <select id="ruleType" name="rule_type" class="disc-select" required onchange="handleRuleTypeChange(this.value)">
                            <option value="item">Item-Wise</option>
                            <option value="category">Category-Wise</option>
                            <option value="brand">Brand-Wise</option>
                            <option value="supplier">Supplier-Wise</option>
                            <option value="quantity">Quantity Tiers</option>
                            <option value="bogo">Buy X Get Y (BOGO)</option>
                            <option value="invoice">Invoice Subtotal</option>
                            <option value="coupon">Coupon Code</option>
                            <option value="customer">Customer Offer</option>
                        </select>
                    </div>

                    <div class="disc-form-group">
                        <label class="disc-label">Discount Mode *</label>
                        <select id="discMode" name="discount_mode" class="disc-select" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed_amount">Fixed Amount (LKR)</option>
                            <option value="fixed_price">Fixed Promotional Price (LKR)</option>
                        </select>
                    </div>

                    <div class="disc-form-group">
                        <label class="disc-label">Discount Value *</label>
                        <input type="number" step="0.01" min="0" id="discValue" name="discount_value" class="disc-input" placeholder="e.g. 15 for 15% or 500 for LKR 500" required>
                    </div>

                    <div class="disc-form-group">
                        <label class="disc-label">Priority (1 - 100) *</label>
                        <input type="number" id="rulePriority" name="priority" class="disc-input" value="10" min="1" max="100" required>
                    </div>

                    <!-- 1. ITEM-WISE SELECTION SECTION -->
                    <div class="disc-form-group disc-form-full" id="sectionItemTargets" style="display: block;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label class="disc-label" style="margin: 0;">Select Applicable Items / Products *</label>
                            <label style="font-size: 11px; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                                <input type="checkbox" id="chkStorewideItem" onchange="toggleStorewideItems(this.checked)"> Apply to All Products (Storewide)
                            </label>
                        </div>

                        <div id="itemPickerControls">
                            <div class="item-search-wrapper">
                                <input type="text" id="itemSearchInput" class="disc-input" placeholder="Type product name, SKU or barcode to search & add..." autocomplete="off" oninput="onItemSearchInput(this.value)">
                                <div id="itemSearchResults" class="item-search-results"></div>
                            </div>

                            <div class="selected-items-panel" id="selectedItemsPanel" style="display: none;">
                                <div style="padding: 6px 10px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 11px; font-weight: 800; color: #475569;" id="selectedItemsCount">0 Items Selected</span>
                                    <a href="javascript:void(0)" onclick="clearAllSelectedItems()" style="font-size: 11px; color: #dc2626; font-weight: 700; text-decoration: none;">Clear All</a>
                                </div>
                                <div style="max-height: 160px; overflow-y: auto;">
                                    <table class="selected-items-table">
                                        <thead>
                                            <tr>
                                                <th>Product Name</th>
                                                <th>SKU / Barcode</th>
                                                <th class="col-right">Selling Price</th>
                                                <th style="width: 40px;" class="col-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="selectedItemsTbody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. CATEGORY-WISE SELECTION SECTION -->
                    <div class="disc-form-group disc-form-full" id="sectionCategoryTargets" style="display: none;">
                        <label class="disc-label">Select Applicable Categories *</label>
                        <select id="catTargetSelect" class="disc-select" onchange="addCategoryTarget(this.value)">
                            <option value="">Choose category to add...</option>
                        </select>
                        <div id="selectedCategoryPills" style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;"></div>
                    </div>

                    <!-- 3. BRAND-WISE SELECTION SECTION -->
                    <div class="disc-form-group disc-form-full" id="sectionBrandTargets" style="display: none;">
                        <label class="disc-label">Select Applicable Brands *</label>
                        <select id="brandTargetSelect" class="disc-select" onchange="addBrandTarget(this.value)">
                            <option value="">Choose brand to add...</option>
                        </select>
                        <div id="selectedBrandPills" style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;"></div>
                    </div>

                    <!-- 4. SUPPLIER-WISE SELECTION SECTION -->
                    <div class="disc-form-group disc-form-full" id="sectionSupplierTargets" style="display: none;">
                        <label class="disc-label">Select Applicable Suppliers *</label>
                        <select id="supplierTargetSelect" class="disc-select" onchange="addSupplierTarget(this.value)">
                            <option value="">Choose supplier to add...</option>
                        </select>
                        <div id="selectedSupplierPills" style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;"></div>
                    </div>

                    <!-- 5. BUY X GET Y (BOGO) SECTION -->
                    <div class="disc-form-group disc-form-full" id="sectionBogoControls" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                        <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #0f172a; margin-bottom: 8px;">Buy X Get Y Configuration</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="disc-label">Buy Quantity (X) *</label>
                                <input type="number" min="1" step="1" id="bogoBuyQty" name="min_quantity" class="disc-input" value="1">
                            </div>
                            <div>
                                <label class="disc-label">Get Quantity (Y) *</label>
                                <input type="number" min="1" step="1" id="bogoGetQty" name="get_quantity" class="disc-input" value="1">
                            </div>
                            <div class="disc-form-full">
                                <label class="disc-label">Discount on 'Get' Item</label>
                                <select id="bogoGetDiscountMode" name="get_discount_mode" class="disc-select">
                                    <option value="free">100% Free (Complimentary)</option>
                                    <option value="percentage">Percentage Discount (e.g. 50% OFF)</option>
                                    <option value="fixed_amount">Fixed Amount Discount</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 6. QUANTITY TIERS & INVOICE SUBTOTAL INPUTS -->
                    <div class="disc-form-group" id="groupMinSubtotal" style="display: none;">
                        <label class="disc-label">Min. Invoice Subtotal (LKR)</label>
                        <input type="number" step="0.01" min="0" id="minInvoiceSubtotal" name="min_invoice_subtotal" class="disc-input" placeholder="0.00">
                    </div>

                    <div class="disc-form-group" id="groupMinQuantity" style="display: none;">
                        <label class="disc-label">Min. Qualifying Quantity</label>
                        <input type="number" step="1" min="1" id="minQuantity" name="min_quantity" class="disc-input" value="1">
                    </div>

                    <!-- 7. COUPON CODE & CUSTOMER SELECTION -->
                    <div class="disc-form-group" id="groupCouponCode" style="display: none;">
                        <label class="disc-label">Coupon / Promo Code *</label>
                        <input type="text" id="couponCode" name="coupon_code" class="disc-input" placeholder="e.g. SAVE15" style="text-transform: uppercase;">
                    </div>

                    <div class="disc-form-group" id="groupCustomerOffer" style="display: none;">
                        <label class="disc-label">Applicable Customer</label>
                        <select id="customerSelect" name="customer_id" class="disc-select">
                            <option value="">All Registered Customers</option>
                        </select>
                    </div>

                    <!-- DATES & LIMITS -->
                    <div class="disc-form-group">
                        <label class="disc-label">Start Date</label>
                        <input type="date" id="startDate" name="start_date" class="disc-input">
                    </div>

                    <div class="disc-form-group">
                        <label class="disc-label">End Date</label>
                        <input type="date" id="endDate" name="end_date" class="disc-input">
                    </div>

                    <div class="disc-form-group">
                        <label class="disc-label">Max Discount Cap (LKR)</label>
                        <input type="number" step="0.01" min="0" id="maxDiscountAmount" name="max_discount_amount" class="disc-input" placeholder="Optional cap">
                    </div>

                    <div class="disc-form-group">
                        <label class="disc-label">Usage Limit (Total)</label>
                        <input type="number" min="1" id="maxUsageTotal" name="max_usage_total" class="disc-input" placeholder="Unlimited">
                    </div>

                    <div class="disc-form-group disc-form-full">
                        <label class="disc-label">Description / Internal Notes</label>
                        <textarea id="ruleDescription" name="description" class="disc-textarea" placeholder="Promotion objectives or campaign details..."></textarea>
                    </div>
                </div>
            </div>
            <div class="disc-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeRuleModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSaveRule">Save Promotion</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;
let currentLimit = 25;
let currentView = 'active'; // 'active' or 'deleted'
let currentSearchQuery = '';
let masterEntities = { categories: [], brands: [], suppliers: [], products: [], customers: [], branches: [] };

// Target states
let selectedItems = [];      // Array of { id, name, sku, price }
let selectedCategories = []; // Array of { id, name }
let selectedBrands = [];     // Array of { id, name }
let selectedSuppliers = [];  // Array of { id, name }

let searchTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    // Attach CurtissSearchableSelect to inline filter dropdowns
    if (window.CurtissSearchableSelect) {
        document.querySelectorAll('#filterBranch, #filterType, #filterStatus').forEach(el => {
            CurtissSearchableSelect.attach(el);
        });
    }

    // Connect to Floating Bottom Navigation Realtime Search
    if (window.CurtissNav && typeof window.CurtissNav.enablePageSearch === 'function') {
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search promotions by code, name, type, discount, scope...',
            onSearch: function(query) {
                currentSearchQuery = query;
                loadRules(1);
            }
        });
    }

    // Load master entities for targets selector
    fetch('discount_api.php?action=entities')
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                masterEntities = res;
                populateEntityDropdowns();
            }
        });

    // Close search dropdown on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.item-search-wrapper')) {
            const results = document.getElementById('itemSearchResults');
            if (results) results.style.display = 'none';
        }
    });

    loadRules(1);
});

function populateEntityDropdowns() {
    // Categories
    const catSel = document.getElementById('catTargetSelect');
    if (catSel) {
        catSel.innerHTML = '<option value="">Choose category to add...</option>';
        (masterEntities.categories || []).forEach(c => {
            catSel.innerHTML += `<option value="${c.id}">${escapeHtml(c.name)}</option>`;
        });
    }

    // Brands
    const brandSel = document.getElementById('brandTargetSelect');
    if (brandSel) {
        brandSel.innerHTML = '<option value="">Choose brand to add...</option>';
        (masterEntities.brands || []).forEach(b => {
            brandSel.innerHTML += `<option value="${b.id}">${escapeHtml(b.name)}</option>`;
        });
    }

    // Suppliers
    const supSel = document.getElementById('supplierTargetSelect');
    if (supSel) {
        supSel.innerHTML = '<option value="">Choose supplier to add...</option>';
        (masterEntities.suppliers || []).forEach(s => {
            supSel.innerHTML += `<option value="${s.id}">${escapeHtml(s.name)}</option>`;
        });
    }

    // Customers
    const custSel = document.getElementById('customerSelect');
    if (custSel) {
        custSel.innerHTML = '<option value="">All Registered Customers</option>';
        (masterEntities.customers || []).forEach(cu => {
            custSel.innerHTML += `<option value="${cu.id}">${escapeHtml(cu.name)} ${cu.phone ? `(${cu.phone})` : ''}</option>`;
        });
    }
}

function loadRules(page = 1) {
    currentPage = page;
    const branch = document.getElementById('filterBranch') ? document.getElementById('filterBranch').value : '<?= $branchId ?>';
    const type = document.getElementById('filterType').value;
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        limit: currentLimit,
        view: currentView,
        branch_id: branch,
        rule_type: type,
        status: status,
        search: currentSearchQuery
    });

    fetch('discount_api.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Failed to load rules.');
                return;
            }
            renderTable(data.items);
            renderPagination(data);
        })
        .catch(err => {
            document.getElementById('rulesTableBody').innerHTML = `<tr><td colspan="10" class="col-center" style="color: #ef4444; padding: 2rem;">Error connecting to server.</td></tr>`;
        });
}

function renderTable(items) {
    const tbody = document.getElementById('rulesTableBody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="col-center" style="padding: 3rem; color: #64748b; font-weight: 600;">No promotions found matching criteria.</td></tr>`;
        return;
    }

    let html = '';
    items.forEach(it => {
        let statusBadge = '';
        if (it.computed_status === 'active') {
            statusBadge = `<span class="disc-badge disc-badge-active" onclick="event.stopPropagation(); toggleRuleStatus(${it.id});" title="Click to toggle status"><span class="dot"></span>Active</span>`;
        } else if (it.computed_status === 'scheduled') {
            statusBadge = `<span class="disc-badge disc-badge-scheduled" onclick="event.stopPropagation(); toggleRuleStatus(${it.id});" title="Scheduled"><span class="dot"></span>Scheduled</span>`;
        } else if (it.computed_status === 'expired') {
            statusBadge = `<span class="disc-badge disc-badge-expired" onclick="event.stopPropagation(); toggleRuleStatus(${it.id});" title="Expired"><span class="dot"></span>Expired</span>`;
        } else {
            statusBadge = `<span class="disc-badge disc-badge-inactive" onclick="event.stopPropagation(); toggleRuleStatus(${it.id});" title="Click to toggle status"><span class="dot"></span>Inactive</span>`;
        }

        let discDisplay = '';
        if (it.discount_mode === 'percentage') {
            discDisplay = `<strong>${parseFloat(it.discount_value)}%</strong>`;
        } else if (it.discount_mode === 'fixed_amount') {
            discDisplay = `<strong>LKR ${parseFloat(it.discount_value).toFixed(2)}</strong>`;
        } else {
            discDisplay = `Price: <strong>LKR ${parseFloat(it.discount_value).toFixed(2)}</strong>`;
        }

        const dateRange = (it.start_date || it.end_date) ? `${it.start_date || 'Always'} &rarr; ${it.end_date || 'No End'}` : 'Always Active';
        const scopeText = it.target_summary || 'Storewide';

        let actHtml = '';
        if (currentView === 'deleted') {
            actHtml = `<button type="button" class="disc-act-btn disc-act-restore" onclick="event.stopPropagation(); restoreRule(${it.id});">Restore</button>`;
        } else {
            actHtml = `
                <div style="display: inline-flex; align-items: center; gap: 6px;">
                    <button type="button" class="disc-act-btn disc-act-edit" onclick="event.stopPropagation(); editRule(${it.id});">Edit</button>
                    <button type="button" class="disc-act-btn disc-act-delete" onclick="event.stopPropagation(); deleteRule(${it.id});">Delete</button>
                </div>
            `;
        }

        html += `
            <tr onclick="editRule(${it.id})">
                <td style="font-family: monospace; font-weight: 800; color: #0f172a;">${escapeHtml(it.rule_code)}</td>
                <td>
                    <div style="font-weight: 800; color: #0f172a;">${escapeHtml(it.name)}</div>
                    ${it.coupon_code ? `<span style="font-size: 10px; background: #e0e7ff; color: #4338ca; padding: 1px 6px; border-radius: 4px; font-weight: 800;">CODE: ${escapeHtml(it.coupon_code)}</span>` : ''}
                </td>
                <td><span class="type-pill">${escapeHtml(it.rule_type)}</span></td>
                <td style="font-size: 12px; color: #334155;">${escapeHtml(scopeText)}</td>
                <td>${discDisplay}</td>
                <td class="col-center"><strong>${it.priority}</strong></td>
                <td class="col-center">${it.usage_count} ${it.max_usage_total ? `<span style="color:#64748b;">/ ${it.max_usage_total}</span>` : ''}</td>
                <td style="font-size: 11px; color: #475569;">${dateRange}</td>
                <td class="col-center">${statusBadge}</td>
                <td class="col-right">${actHtml}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderPagination(data) {
    const total = data.total_count || 0;
    const start = total === 0 ? 0 : (currentPage - 1) * currentLimit + 1;
    const end = Math.min(currentPage * currentLimit, total);
    document.getElementById('paginationInfo').textContent = `Showing ${start} to ${end} of ${total} entries`;
    document.getElementById('pageNumberDisplay').textContent = `${currentPage} / ${data.total_pages || 1}`;
    document.getElementById('btnPrevPage').disabled = (currentPage <= 1);
    document.getElementById('btnNextPage').disabled = (currentPage >= (data.total_pages || 1));
}

function prevPage() {
    if (currentPage > 1) loadRules(currentPage - 1);
}
function nextPage() {
    loadRules(currentPage + 1);
}
function changePageSize(val) {
    currentLimit = parseInt(val, 10);
    loadRules(1);
}

function toggleTrashView() {
    if (currentView === 'active') {
        currentView = 'deleted';
        document.getElementById('trashBtnText').textContent = 'Active Promotions';
        document.getElementById('btnToggleTrash').className = 'inv-btn inv-btn-ghost';
    } else {
        currentView = 'active';
        document.getElementById('trashBtnText').textContent = 'Trash Bin';
        document.getElementById('btnToggleTrash').className = 'inv-btn inv-btn-trash';
    }
    loadRules(1);
}

// ==========================================
// TARGETS & RULE TYPE HANDLING
// ==========================================

function handleRuleTypeChange(type) {
    // Hide all target sections first
    document.getElementById('sectionItemTargets').style.display     = 'none';
    document.getElementById('sectionCategoryTargets').style.display = 'none';
    document.getElementById('sectionBrandTargets').style.display    = 'none';
    document.getElementById('sectionSupplierTargets').style.display = 'none';
    document.getElementById('sectionBogoControls').style.display    = 'none';
    document.getElementById('groupMinSubtotal').style.display       = 'none';
    document.getElementById('groupMinQuantity').style.display       = 'none';
    document.getElementById('groupCouponCode').style.display        = 'none';
    document.getElementById('groupCustomerOffer').style.display     = 'none';

    if (type === 'item') {
        document.getElementById('sectionItemTargets').style.display = 'block';
    } else if (type === 'category') {
        document.getElementById('sectionCategoryTargets').style.display = 'block';
    } else if (type === 'brand') {
        document.getElementById('sectionBrandTargets').style.display = 'block';
    } else if (type === 'supplier') {
        document.getElementById('sectionSupplierTargets').style.display = 'block';
    } else if (type === 'quantity') {
        document.getElementById('groupMinQuantity').style.display = 'block';
        document.getElementById('sectionItemTargets').style.display = 'block';
    } else if (type === 'bogo') {
        document.getElementById('sectionBogoControls').style.display = 'block';
        document.getElementById('sectionItemTargets').style.display = 'block';
    } else if (type === 'invoice') {
        document.getElementById('groupMinSubtotal').style.display = 'block';
    } else if (type === 'coupon') {
        document.getElementById('groupCouponCode').style.display = 'block';
        document.getElementById('sectionItemTargets').style.display = 'block';
    } else if (type === 'customer') {
        document.getElementById('groupCustomerOffer').style.display = 'block';
        document.getElementById('sectionItemTargets').style.display = 'block';
    }
}

// 1. Item Picker Logic
function onItemSearchInput(val) {
    clearTimeout(searchTimeout);
    const q = val.trim();
    const results = document.getElementById('itemSearchResults');

    if (q.length === 0) {
        results.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch('discount_api.php?action=search_products&q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.products && data.products.length > 0) {
                    renderItemSearchResults(data.products);
                } else {
                    results.innerHTML = '<div style="padding: 10px; font-size: 12px; color: #64748b;">No matching items found.</div>';
                    results.style.display = 'block';
                }
            })
            .catch(() => {
                results.style.display = 'none';
            });
    }, 250);
}

function renderItemSearchResults(products) {
    const results = document.getElementById('itemSearchResults');
    let html = '';
    products.forEach(p => {
        const isAdded = selectedItems.some(x => x.id === parseInt(p.id, 10));
        html += `
            <div class="item-search-result-row" onclick="addItemFromSearch(${p.id}, '${escapeHtml(p.name)}', '${escapeHtml(p.sku || '')}', ${p.selling_price || 0})">
                <div>
                    <div style="font-weight: 700; color: #0f172a;">${escapeHtml(p.name)}</div>
                    <div style="font-size: 10.5px; color: #64748b;">SKU: ${escapeHtml(p.sku || '-')} | Price: LKR ${parseFloat(p.selling_price || 0).toFixed(2)}</div>
                </div>
                <div>
                    ${isAdded ? '<span style="font-size: 11px; font-weight: 800; color: #16a34a;">Added &check;</span>' : '<button type="button" class="inv-btn inv-btn-ghost" style="height: 24px; min-height: 24px; padding: 0 8px; font-size: 11px;">+ Add</button>'}
                </div>
            </div>
        `;
    });
    results.innerHTML = html;
    results.style.display = 'block';
}

function addItemFromSearch(id, name, sku, price) {
    id = parseInt(id, 10);
    if (!selectedItems.some(x => x.id === id)) {
        selectedItems.push({ id, name, sku, price });
        renderSelectedItems();
    }
    document.getElementById('itemSearchResults').style.display = 'none';
    document.getElementById('itemSearchInput').value = '';
    document.getElementById('itemSearchInput').focus();
}

function removeItem(id) {
    id = parseInt(id, 10);
    selectedItems = selectedItems.filter(x => x.id !== id);
    renderSelectedItems();
}

function clearAllSelectedItems() {
    selectedItems = [];
    renderSelectedItems();
}

function toggleStorewideItems(checked) {
    const picker = document.getElementById('itemPickerControls');
    if (checked) {
        picker.style.opacity = '0.4';
        picker.style.pointerEvents = 'none';
    } else {
        picker.style.opacity = '1';
        picker.style.pointerEvents = 'auto';
    }
}

function renderSelectedItems() {
    const panel = document.getElementById('selectedItemsPanel');
    const tbody = document.getElementById('selectedItemsTbody');
    const countEl = document.getElementById('selectedItemsCount');

    if (selectedItems.length === 0) {
        panel.style.display = 'none';
        return;
    }

    panel.style.display = 'block';
    countEl.textContent = `${selectedItems.length} Item${selectedItems.length > 1 ? 's' : ''} Selected`;

    let html = '';
    selectedItems.forEach(it => {
        html += `
            <tr>
                <td style="font-weight: 700; color: #0f172a;">${escapeHtml(it.name)}</td>
                <td style="font-family: monospace; color: #475569;">${escapeHtml(it.sku || '-')}</td>
                <td class="col-right" style="font-weight: 600;">LKR ${parseFloat(it.price || 0).toFixed(2)}</td>
                <td class="col-center">
                    <span onclick="removeItem(${it.id})" style="cursor: pointer; color: #dc2626; font-size: 14px; font-weight: 800;" title="Remove Item">&times;</span>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// 2. Category Targets
function addCategoryTarget(val) {
    const id = parseInt(val, 10);
    if (id > 0 && !selectedCategories.some(x => x.id === id)) {
        const cat = (masterEntities.categories || []).find(x => x.id == id);
        if (cat) {
            selectedCategories.push({ id: cat.id, name: cat.name });
            renderCategoryPills();
        }
    }
    document.getElementById('catTargetSelect').value = '';
}

function removeCategory(id) {
    selectedCategories = selectedCategories.filter(x => x.id !== id);
    renderCategoryPills();
}

function renderCategoryPills() {
    const container = document.getElementById('selectedCategoryPills');
    let html = '';
    selectedCategories.forEach(c => {
        html += `
            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; background: #e2e8f0; border-radius: 999px; font-size: 11.5px; font-weight: 700; color: #0f172a;">
                ${escapeHtml(c.name)}
                <span onclick="removeCategory(${c.id})" style="cursor: pointer; color: #dc2626; font-weight: 800; margin-left: 4px;">&times;</span>
            </span>
        `;
    });
    container.innerHTML = html;
}

// 3. Brand Targets
function addBrandTarget(val) {
    const id = parseInt(val, 10);
    if (id > 0 && !selectedBrands.some(x => x.id === id)) {
        const br = (masterEntities.brands || []).find(x => x.id == id);
        if (br) {
            selectedBrands.push({ id: br.id, name: br.name });
            renderBrandPills();
        }
    }
    document.getElementById('brandTargetSelect').value = '';
}

function removeBrand(id) {
    selectedBrands = selectedBrands.filter(x => x.id !== id);
    renderBrandPills();
}

function renderBrandPills() {
    const container = document.getElementById('selectedBrandPills');
    let html = '';
    selectedBrands.forEach(b => {
        html += `
            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; background: #e2e8f0; border-radius: 999px; font-size: 11.5px; font-weight: 700; color: #0f172a;">
                ${escapeHtml(b.name)}
                <span onclick="removeBrand(${b.id})" style="cursor: pointer; color: #dc2626; font-weight: 800; margin-left: 4px;">&times;</span>
            </span>
        `;
    });
    container.innerHTML = html;
}

// 4. Supplier Targets
function addSupplierTarget(val) {
    const id = parseInt(val, 10);
    if (id > 0 && !selectedSuppliers.some(x => x.id === id)) {
        const sup = (masterEntities.suppliers || []).find(x => x.id == id);
        if (sup) {
            selectedSuppliers.push({ id: sup.id, name: sup.name });
            renderSupplierPills();
        }
    }
    document.getElementById('supplierTargetSelect').value = '';
}

function removeSupplier(id) {
    selectedSuppliers = selectedSuppliers.filter(x => x.id !== id);
    renderSupplierPills();
}

function renderSupplierPills() {
    const container = document.getElementById('selectedSupplierPills');
    let html = '';
    selectedSuppliers.forEach(s => {
        html += `
            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; background: #e2e8f0; border-radius: 999px; font-size: 11.5px; font-weight: 700; color: #0f172a;">
                ${escapeHtml(s.name)}
                <span onclick="removeSupplier(${s.id})" style="cursor: pointer; color: #dc2626; font-weight: 800; margin-left: 4px;">&times;</span>
            </span>
        `;
    });
    container.innerHTML = html;
}

// ==========================================
// MODAL WORKFLOWS
// ==========================================

function openNewRuleModal() {
    document.getElementById('ruleForm').reset();
    document.getElementById('ruleId').value = '';
    document.getElementById('ruleModalTitle').textContent = '+ Create New Promotion';
    
    selectedItems = [];
    selectedCategories = [];
    selectedBrands = [];
    selectedSuppliers = [];

    renderSelectedItems();
    renderCategoryPills();
    renderBrandPills();
    renderSupplierPills();

    document.getElementById('chkStorewideItem').checked = false;
    toggleStorewideItems(false);

    document.getElementById('ruleType').value = 'item';
    handleRuleTypeChange('item');

    document.getElementById('ruleModal').classList.add('active');
    setTimeout(() => document.getElementById('ruleName').focus(), 100);
}

function closeRuleModal() {
    document.getElementById('ruleModal').classList.remove('active');
}

function submitRuleForm() {
    const form = document.getElementById('ruleForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const type = document.getElementById('ruleType').value;
    const isStorewide = document.getElementById('chkStorewideItem').checked;

    // Validate target selection when rule type requires it
    if (type === 'item' && !isStorewide && selectedItems.length === 0) {
        alert('Please search and add at least one item, or check "Apply to All Products (Storewide)".');
        document.getElementById('itemSearchInput').focus();
        return;
    }
    if (type === 'category' && selectedCategories.length === 0) {
        alert('Please select at least one applicable category.');
        document.getElementById('catTargetSelect').focus();
        return;
    }
    if (type === 'brand' && selectedBrands.length === 0) {
        alert('Please select at least one applicable brand.');
        document.getElementById('brandTargetSelect').focus();
        return;
    }
    if (type === 'supplier' && selectedSuppliers.length === 0) {
        alert('Please select at least one applicable supplier.');
        document.getElementById('supplierTargetSelect').focus();
        return;
    }

    const fd = new FormData(form);
    const id = document.getElementById('ruleId').value;
    fd.append('action', id ? 'update' : 'create');

    // Attach targets based on rule type
    if (type === 'item' || type === 'coupon' || type === 'customer') {
        fd.append('target_type', 'product');
        if (!isStorewide) {
            selectedItems.forEach(it => fd.append('target_ids[]', it.id));
        }
    } else if (type === 'category') {
        fd.append('target_type', 'category');
        selectedCategories.forEach(c => fd.append('target_ids[]', c.id));
    } else if (type === 'brand') {
        fd.append('target_type', 'brand');
        selectedBrands.forEach(b => fd.append('target_ids[]', b.id));
    } else if (type === 'supplier') {
        fd.append('target_type', 'supplier');
        selectedSuppliers.forEach(s => fd.append('target_ids[]', s.id));
    } else if (type === 'bogo') {
        fd.append('target_type', 'product');
        selectedItems.forEach(it => fd.append('target_ids[]', it.id));
    } else if (type === 'quantity') {
        fd.append('target_type', 'product');
        selectedItems.forEach(it => fd.append('target_ids[]', it.id));
    }

    const submitBtn = document.getElementById('btnSaveRule');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';

    fetch('discount_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Promotion';
            if (!data.success) {
                alert(data.message || 'Operation failed.');
                return;
            }
            closeRuleModal();
            loadRules(currentPage);
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Promotion';
            alert('Server error.');
        });
}

function editRule(id) {
    fetch('discount_api.php?action=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.data) {
                alert('Could not load rule.');
                return;
            }
            const r = data.data;
            document.getElementById('ruleId').value = r.id;
            document.getElementById('ruleModalTitle').textContent = `Edit Promotion #${r.rule_code}`;
            document.getElementById('ruleName').value = r.name;
            document.getElementById('ruleType').value = r.rule_type;
            document.getElementById('discMode').value = r.discount_mode;
            document.getElementById('discValue').value = r.discount_value;
            document.getElementById('rulePriority').value = r.priority;
            document.getElementById('minInvoiceSubtotal').value = r.min_invoice_subtotal || '';
            document.getElementById('minQuantity').value = r.min_quantity || 1;
            document.getElementById('bogoBuyQty').value = r.min_quantity || 1;
            document.getElementById('bogoGetQty').value = r.get_quantity || 1;
            document.getElementById('bogoGetDiscountMode').value = r.get_discount_mode || 'free';
            document.getElementById('couponCode').value = r.coupon_code || '';
            document.getElementById('startDate').value = r.start_date || '';
            document.getElementById('endDate').value = r.end_date || '';
            document.getElementById('maxDiscountAmount').value = r.max_discount_amount || '';
            document.getElementById('maxUsageTotal').value = r.max_usage_total || '';
            document.getElementById('ruleDescription').value = r.description || '';
            if (document.getElementById('customerSelect') && r.customer_id) {
                document.getElementById('customerSelect').value = r.customer_id;
            }

            handleRuleTypeChange(r.rule_type);

            // Populate Targets
            selectedItems = [];
            selectedCategories = [];
            selectedBrands = [];
            selectedSuppliers = [];

            if (r.targets && r.targets.length > 0) {
                document.getElementById('chkStorewideItem').checked = false;
                toggleStorewideItems(false);

                r.targets.forEach(t => {
                    const tid = parseInt(t.target_id, 10);
                    if (t.target_type === 'product' || t.target_type === 'get_product') {
                        selectedItems.push({
                            id: tid,
                            name: t.target_name || `Product #${tid}`,
                            sku: t.target_sku || '',
                            price: parseFloat(t.target_price || 0)
                        });
                    } else if (t.target_type === 'category') {
                        selectedCategories.push({ id: tid, name: t.target_name || `Category #${tid}` });
                    } else if (t.target_type === 'brand') {
                        selectedBrands.push({ id: tid, name: t.target_name || `Brand #${tid}` });
                    } else if (t.target_type === 'supplier') {
                        selectedSuppliers.push({ id: tid, name: t.target_name || `Supplier #${tid}` });
                    }
                });
            } else {
                if (r.rule_type === 'item') {
                    document.getElementById('chkStorewideItem').checked = true;
                    toggleStorewideItems(true);
                }
            }

            renderSelectedItems();
            renderCategoryPills();
            renderBrandPills();
            renderSupplierPills();

            document.getElementById('ruleModal').classList.add('active');
        });
}

function toggleRuleStatus(id) {
    const fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('id', id);

    fetch('discount_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Failed to toggle status.');
                return;
            }
            loadRules(currentPage);
        });
}

function deleteRule(id) {
    if (!confirm('Move this promotion rule to Trash Bin?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    fetch('discount_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Failed to delete rule.');
                return;
            }
            loadRules(currentPage);
        });
}

function restoreRule(id) {
    const fd = new FormData();
    fd.append('action', 'restore');
    fd.append('id', id);

    fetch('discount_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Failed to restore rule.');
                return;
            }
            loadRules(currentPage);
        });
}

function exportDiscountsCsv() {
    window.location.href = 'discount_api.php?action=export_csv';
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
