<?php
/**
 * Curtiss POS - Supplier Purchase Returns View (PO & GRN Form Style UI Redesign)
 */

$totalReturnsCount = count($history ?? []);
$totalReturnVal    = 0.00;
$totalApOffset     = 0.00;
$totalCreditVal    = 0.00;

foreach ($history as $r) {
    $totalReturnVal += (float)($r['total_return_amount'] ?? 0);
    $totalApOffset  += (float)($r['applied_to_payable'] ?? 0);
    $totalCreditVal += (float)($r['credit_amount'] ?? 0);
}
?>

<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Pill -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="prod-btn prod-btn-ghost" onclick="startExportCSV(event, 'supplier_return_export.php')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Export CSV
    </button>
    <?php if (Auth::hasPermission('create_supplier_return') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
    <a href="supplier_return_create" class="prod-btn prod-btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        Create Supplier Return
    </a>
    <?php endif; ?>
</div>

<style>
/* ── Shared Theme Styles ── */
.prod-header { margin-bottom: 0.85rem; }
.prod-title { font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.025em; line-height: 1.2; }

.prod-btn {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid transparent;
    cursor: pointer; text-decoration: none; transition: all 0.18s ease; white-space: nowrap; font-family: inherit;
}
.prod-btn-ghost { background: rgba(255,255,255,0.9); border-color: rgba(226,232,240,0.9); color: #0f172a; backdrop-filter: blur(12px); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.prod-btn-ghost:hover { background: #ffffff; box-shadow: 0 4px 14px rgba(0,0,0,0.1); transform: translateY(-1px); }
.prod-btn-primary { background: #0f172a; color: #ffffff; box-shadow: 0 2px 12px rgba(15,23,42,0.2); }
.prod-btn-primary:hover { background: #1e293b; box-shadow: 0 4px 16px rgba(15,23,42,0.3); transform: translateY(-1px); }

/* Main Datatable Panel */
.prod-table-panel { background: rgba(255,255,255,0.92); backdrop-filter: blur(16px); border: 1px solid rgba(226,232,240,0.85); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.04); flex: 1; min-height: 0; display: flex; flex-direction: column; }
.prod-table-scroll { overflow-x: auto; overflow-y: auto; flex: 1; min-height: 0; }
.prod-table { width: 100%; border-collapse: collapse; }
.prod-table thead { background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; }
.prod-table thead th { height: 40px; padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; white-space: nowrap; text-align: left; background: #f8fafc; position: sticky; top: 0; z-index: 10; box-sizing: border-box; }
.prod-table thead th.col-right { text-align: right; }
.prod-table tbody tr { height: 48px; min-height: 48px; border-bottom: 1px solid #f1f5f9; transition: background 0.12s ease; cursor: pointer; }
.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:hover            { background: #f1f5f9 !important; }
.prod-table td { height: 48px; padding: 0 14px; font-size: 12.5px; color: #1e293b; vertical-align: middle; text-align: left; box-sizing: border-box; }
.prod-table td.col-right { text-align: right; }

/* Badges & Action Buttons (Height locked to 28px) */
.ret-badge { display: inline-flex; align-items: center; justify-content: center; height: 28px; min-height: 28px; line-height: 26px; padding: 0 12px; border-radius: 999px; font-size: 11.5px; font-weight: 700; font-family: monospace; border: 1px solid #cbd5e1; background: #f1f5f9; color: #334155; box-sizing: border-box; }
.prod-act-btn { display: inline-flex; align-items: center; justify-content: center; height: 28px; min-height: 28px; line-height: 26px; padding: 0 12px; border-radius: 999px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer; transition: all 0.15s ease; font-family: inherit; box-sizing: border-box; }
.prod-act-btn:hover { background: #f1f5f9; color: #0f172a; }

/* Glassmorphism Modals & Form Cards */
.curtiss-modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9600; align-items: center; justify-content: center; padding: 1rem; }
.curtiss-modal-backdrop.active { display: flex; }
.curtiss-modal-card { background: rgba(255, 255, 255, 0.98); width: 100%; max-width: 960px; border-radius: 18px; padding: 24px; box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.18); border: 1px solid rgba(226, 232, 240, 0.9); animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards; max-height: 90vh; overflow-y: auto; }
@keyframes modalSlideUp { from { opacity: 0; transform: translateY(12px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }

/* Search Product Results Box */
.prod-search-results { position: absolute; top: 100%; left: 0; right: 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 250px; overflow-y: auto; z-index: 100; margin-top: 4px; display: none; }
.prod-search-item { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.12s ease; display: flex; align-items: center; justify-content: space-between; }
.prod-search-item:hover { background: #f8fafc; }
.prod-search-item:last-child { border-bottom: none; }

/* Fixed Bottom-Right Totals Bar (Same as PO & GRN Forms) */
.fixed-totals-bar { position: fixed; bottom: 20px; right: 20px; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(16px); color: #ffffff; border-radius: 999px; padding: 10px 22px; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); z-index: 9500; font-size: 13px; }
.fixed-totals-value { font-weight: 800; font-family: monospace; font-size: 1.1rem; color: #60a5fa; }

/* Export Progress Modal Popup */
.exp-backdrop { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(8px); z-index: 9600; transition: opacity 0.2s ease; }
.exp-backdrop.active { display: block; }
.exp-modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.96); background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(16px); border: 1px solid rgba(226, 232, 240, 0.9); box-shadow: 0 20px 50px -10px rgba(0,0,0,0.15); border-radius: 20px; width: 90%; max-width: 420px; padding: 28px; z-index: 9601; transition: all 0.2s ease; }
.exp-modal.active { display: block; transform: translate(-50%, -50%) scale(1); }
.exp-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
.exp-icon-box { width: 44px; height: 44px; border-radius: 14px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.exp-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0; }
.exp-subtitle { font-size: 0.8rem; color: #64748b; margin-top: 2px; }
.exp-progress-wrapper { margin-bottom: 12px; }
.exp-progress-bar-bg { width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
.exp-progress-bar-fill { width: 0%; height: 100%; background: #0f172a; border-radius: 999px; transition: width 0.18s ease; }
.exp-status-row { display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 700; color: #475569; margin-top: 6px; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.exp-spinner { animation: spin 1s linear infinite; }

/* Responsive adjustments */
@media (max-width: 900px) {
    .curtiss-modal-card { max-width: 95vw; padding: 16px; }
    .prod-title { font-size: 1.15rem; }
}
</style>

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header (Headline Only) -->
    <div class="prod-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="prod-title">Supplier Purchase Returns</h1>
        </div>
    </div>

    <!-- Main Inventory-Themed Table Panel -->
    <div class="prod-table-panel">
        <div class="prod-table-scroll">
            <table class="prod-table" id="returnTable">
                <thead>
                    <tr>
                        <th style="text-align: left; width: 130px;">Return #</th>
                        <th style="text-align: left; width: 120px;">Date</th>
                        <th style="text-align: left;">Supplier</th>
                        <th style="text-align: left; width: 140px;">GRN Ref</th>
                        <th class="col-right" style="text-align: right; width: 150px;">Total Return (LKR)</th>
                        <th class="col-right" style="text-align: right; width: 150px;">AP Offset (LKR)</th>
                        <th class="col-right" style="text-align: right; width: 160px;">Supplier Credit (LKR)</th>
                        <th style="text-align: left; width: 140px;">Logged By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr class="prod-empty"><td colspan="8" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No supplier returns recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $r): ?>
                            <?php
                            $searchParts = [
                                $r['return_number'] ?? '',
                                $r['return_date'] ?? '',
                                $r['supplier_name'] ?? '',
                                $r['grn_number'] ?: 'Direct Return',
                                $r['created_by_name'] ?? 'System',
                                $r['notes'] ?? ''
                            ];
                            foreach (['total_return_amount', 'applied_to_payable', 'credit_amount'] as $amtCol) {
                                $amtVal = (float)($r[$amtCol] ?? 0);
                                $amtFmt = number_format($amtVal, 2);
                                $searchParts[] = (string)$amtVal;
                                $searchParts[] = (string)round($amtVal);
                                $searchParts[] = $amtFmt;
                                $searchParts[] = str_replace(',', '', $amtFmt);
                                $searchParts[] = 'lkr ' . $amtFmt;
                                $searchParts[] = 'rs. ' . $amtFmt;
                                $searchParts[] = 'lkr ' . str_replace(',', '', $amtFmt);
                                $searchParts[] = 'rs. ' . str_replace(',', '', $amtFmt);
                            }
                            if (!empty($r['items'])) {
                                foreach ($r['items'] as $it) {
                                    $searchParts[] = $it['product_name'] ?? '';
                                    $searchParts[] = $it['barcode'] ?? '';
                                    $searchParts[] = $it['item_code'] ?? '';
                                    $searchParts[] = $it['lot_number'] ?? '';
                                }
                            }
                            $rowSearchStr = strtolower(implode(' ', array_filter($searchParts)));
                            ?>
                            <tr data-search="<?= htmlspecialchars($rowSearchStr, ENT_QUOTES, 'UTF-8') ?>" onclick='viewReturnDetails(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <td><span class="ret-badge" style="color: #2563eb; border-color: #bfdbfe; background: #eff6ff;"><?= View::escape($r['return_number']) ?></span></td>
                                <td><span style="font-weight: 600; color: #475569; font-size: 12px;"><?= View::escape($r['return_date']) ?></span></td>
                                <td style="font-weight: 700; color: #0f172a;"><?= View::escape($r['supplier_name']) ?></td>
                                <td><span class="ret-badge"><?= View::escape($r['grn_number'] ?: 'Direct Return') ?></span></td>
                                <td class="col-right" style="font-weight: 800; font-family: monospace; color: #0f172a;"><?= number_format((float)$r['total_return_amount'], 2) ?></td>
                                <td class="col-right" style="font-weight: 800; font-family: monospace; color: #16a34a;"><?= number_format((float)$r['applied_to_payable'], 2) ?></td>
                                <td class="col-right" style="font-weight: 800; font-family: monospace; color: #2563eb;"><?= number_format((float)$r['credit_amount'], 2) ?></td>
                                <td style="font-size: 12px; color: #64748b; font-weight: 500;"><?= View::escape($r['created_by_name'] ?? 'System') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="prod-table-footer" style="padding: 12px 18px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="paginationInfo">
                Showing <span id="pagStart">1</span> to <span id="pagEnd">25</span> of <span id="pagTotal">0</span> entries
            </div>

            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                    <span>Rows per page:</span>
                    <select id="pagPageSize" onchange="changePageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="75">75</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 4px;" id="pagNavBtns">
                    <!-- Dynamic page buttons -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Create Supplier Return (PO/GRN Form Style Panel) -->
<div id="createReturnModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Create Supplier Purchase Return</h3>
            </div>
            <button type="button" onclick="closeModal('createReturnModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; font-size: 1.1rem;">✕</button>
        </div>

        <form id="returnForm" onsubmit="handleReturnSubmit(event)">
            <!-- Header Grid -->
            <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 14px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">1. Select Supplier <span style="color: #ef4444;">*</span></label>
                    <select id="supplierSelect" onchange="onSupplierChange(this.value)" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;">
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= View::escape($sup['name']) ?> (<?= View::escape($sup['supplier_code'] ?? 'SUP-'.$sup['id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Return Date <span style="color: #ef4444;">*</span></label>
                    <input type="date" id="returnDate" value="<?= date('Y-m-d') ?>" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Notes / Remarks</label>
                    <input type="text" id="returnNotes" placeholder="e.g. Damaged goods returned to vendor" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <!-- Product Search Box (Filter ONLY products purchased from selected supplier) -->
            <div id="productSearchSection" style="margin-bottom: 16px; position: relative;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">2. Search Purchased Products (Filtered by Selected Supplier)</label>
                <div style="position: relative;">
                    <input type="text" id="prodSearchInput" oninput="searchSupplierProducts(this.value)" placeholder="Search products purchased from this supplier..." disabled style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 14px 0 38px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box; background: #ffffff;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" style="position: absolute; left: 12px; top: 12px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <div id="prodSearchResults" class="prod-search-results"></div>
            </div>

            <!-- Items Table Grid -->
            <div style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; margin-bottom: 8px;">
                3. Return Items List
            </div>
            <div style="overflow-x: auto; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; margin-bottom: 20px;">
                <table class="prod-table" style="font-size: 12px;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th>Product & Lot Details</th>
                            <th style="text-align: center; width: 120px;">Total Purchased</th>
                            <th style="text-align: center; width: 120px;">Returnable Stock</th>
                            <th style="text-align: right; width: 110px;">Unit Cost</th>
                            <th style="text-align: center; width: 110px;">Return Qty *</th>
                            <th style="text-align: right; width: 130px;">Line Total</th>
                            <th style="width: 150px;">Reason</th>
                            <th style="text-align: center; width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="returnItemsTbody">
                        <tr id="emptyItemsRow"><td colspan="8" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No products added yet. Select a supplier and search products above.</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Floating/Fixed Totals Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="prod-btn prod-btn-ghost" onclick="closeModal('createReturnModal')">Cancel</button>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">
                        Grand Total: <span id="grandTotalSpan" style="font-family: monospace; font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-left: 4px;">LKR 0.00</span>
                    </div>
                    <button type="submit" id="btnSubmitReturn" class="prod-btn prod-btn-primary">Process Supplier Return</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: View Return Details -->
<div id="detailsModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card" style="max-width: 780px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Supplier Return Details</h3>
            </div>
            <button type="button" onclick="closeModal('detailsModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; font-size: 1.1rem;">✕</button>
        </div>
        <div id="modalBody"></div>
        <div style="display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="prod-btn prod-btn-ghost" onclick="closeModal('detailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Export Progress Modal Popup -->
<div id="expBackdrop" class="exp-backdrop"></div>
<div id="expModal" class="exp-modal">
    <div class="exp-header">
        <div id="expIconBox" class="exp-icon-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5" class="exp-spinner"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        </div>
        <div>
            <h3 id="expTitle" class="exp-title">Exporting Supplier Returns</h3>
            <div id="expSubtitle" class="exp-subtitle">Preparing return records...</div>
        </div>
    </div>
    <div class="exp-progress-wrapper">
        <div class="exp-progress-bar-bg">
            <div id="expProgressBar" class="exp-progress-bar-fill"></div>
        </div>
        <div class="exp-status-row">
            <span>Progress</span>
            <span id="expStatusText">0%</span>
        </div>
    </div>
    <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
        <button type="button" id="expCloseBtn" class="prod-btn prod-btn-ghost" style="display: none;" onclick="closeExportModal()">Close</button>
    </div>
</div>

<script>
let selectedSupplierProducts = [];
let returnGridItems = [];

function esc(str) {
    if (!str) return '—';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function openCreateReturnModal() {
    document.getElementById('supplierSelect').value = '';
    document.getElementById('prodSearchInput').value = '';
    document.getElementById('prodSearchInput').disabled = true;
    document.getElementById('prodSearchResults').style.display = 'none';
    document.getElementById('returnNotes').value = '';
    returnGridItems = [];
    renderReturnGrid();
    document.getElementById('createReturnModal').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

async function onSupplierChange(supplierId) {
    const searchInput = document.getElementById('prodSearchInput');
    const resultsBox = document.getElementById('prodSearchResults');
    resultsBox.style.display = 'none';
    returnGridItems = [];
    renderReturnGrid();

    if (!supplierId) {
        searchInput.disabled = true;
        searchInput.placeholder = 'Select a supplier first...';
        return;
    }

    searchInput.disabled = false;
    searchInput.placeholder = 'Search products purchased from this supplier...';
    
    // Pre-fetch supplier purchased products
    try {
        const res = await fetch(`supplier_return_api.php?action=get_supplier_products&supplier_id=${supplierId}`);
        const data = await res.json();
        if (data.success) {
            selectedSupplierProducts = data.products || [];
        } else {
            selectedSupplierProducts = [];
        }
    } catch (e) {
        console.error(e);
        selectedSupplierProducts = [];
    }
}

function searchSupplierProducts(query) {
    const resultsBox = document.getElementById('prodSearchResults');
    const q = query.toLowerCase().trim();

    if (!q || !selectedSupplierProducts.length) {
        resultsBox.style.display = 'none';
        return;
    }

    const filtered = selectedSupplierProducts.filter(p => 
        (p.product_name && p.product_name.toLowerCase().includes(q)) ||
        (p.barcode && p.barcode.toLowerCase().includes(q)) ||
        (p.item_code && p.item_code.toLowerCase().includes(q)) ||
        (p.lot_number && p.lot_number.toLowerCase().includes(q))
    );

    if (!filtered.length) {
        resultsBox.innerHTML = `<div style="padding: 12px; color: #94a3b8; text-align: center; font-size: 0.8rem;">No purchased products match "${esc(query)}"</div>`;
        resultsBox.style.display = 'block';
        return;
    }

    let html = '';
    filtered.forEach(p => {
        const key = `${p.product_id}_${p.lot_id || 0}_${p.unit_cost}`;
        html += `
            <div class="prod-search-item" onclick='addSupplierProductToGrid(${JSON.stringify(p).replace(/'/g, "&#39;")})'>
                <div>
                    <strong style="color: #0f172a; font-size: 0.85rem;">${esc(p.product_name)}</strong>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                        SKU: ${esc(p.barcode || p.item_code || '-')} | Lot: ${esc(p.lot_number || 'N/A')}
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 800; font-family: monospace; color: #0f172a; font-size: 0.85rem;">LKR ${p.unit_cost.toFixed(2)}</div>
                    <div style="font-size: 0.72rem; color: #166534; font-weight: 700;">
                        Purchased: ${p.total_purchased_quantity} | Max Return: ${p.max_returnable}
                    </div>
                </div>
            </div>
        `;
    });

    resultsBox.innerHTML = html;
    resultsBox.style.display = 'block';
}

function addSupplierProductToGrid(p) {
    document.getElementById('prodSearchResults').style.display = 'none';
    document.getElementById('prodSearchInput').value = '';

    const key = `${p.product_id}_${p.lot_id || 0}_${p.unit_cost}`;
    const existing = returnGridItems.find(item => item.key === key);

    if (existing) {
        alert(`Product "${p.product_name}" is already in the return list.`);
        return;
    }

    returnGridItems.push({
        key: key,
        product_id: p.product_id,
        product_name: p.product_name,
        barcode: p.barcode || p.item_code || '-',
        lot_id: p.lot_id,
        lot_number: p.lot_number || 'N/A',
        total_purchased: p.total_purchased_quantity,
        max_returnable: p.max_returnable,
        unit_cost: p.unit_cost,
        quantity: 1,
        reason: 'Defective / Supplier Return'
    });

    renderReturnGrid();
}

function removeGridItem(key) {
    returnGridItems = returnGridItems.filter(item => item.key !== key);
    renderReturnGrid();
}

function updateGridItemQty(key, qtyVal) {
    const item = returnGridItems.find(i => i.key === key);
    if (!item) return;

    let qty = parseFloat(qtyVal) || 0;
    if (qty > item.max_returnable) {
        alert(`Return quantity cannot exceed max returnable limit (${item.max_returnable}).`);
        qty = item.max_returnable;
    }
    item.quantity = qty;
    renderReturnGrid(false);
}

function updateGridItemReason(key, reasonVal) {
    const item = returnGridItems.find(i => i.key === key);
    if (item) {
        item.reason = reasonVal;
    }
}

function renderReturnGrid(rebuildHtml = true) {
    const tbody = document.getElementById('returnItemsTbody');
    
    if (!returnGridItems.length) {
        tbody.innerHTML = `<tr id="emptyItemsRow"><td colspan="8" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No products added yet. Select a supplier and search products above.</td></tr>`;
        document.getElementById('grandTotalSpan').innerText = 'LKR 0.00';
        return;
    }

    if (rebuildHtml) {
        let html = '';
        returnGridItems.forEach(item => {
            const lineTotal = item.quantity * item.unit_cost;
            html += `
                <tr>
                    <td>
                        <strong style="color: #0f172a;">${esc(item.product_name)}</strong><br>
                        <span style="font-size: 0.75rem; color: #64748b;">SKU: ${esc(item.barcode)} | Lot: ${esc(item.lot_number)}</span>
                    </td>
                    <td style="text-align: center; font-weight: 600; color: #475569;">${item.total_purchased}</td>
                    <td style="text-align: center; font-weight: 700; color: #166534;">${item.max_returnable}</td>
                    <td style="text-align: right; font-weight: 700; font-family: monospace;">LKR ${item.unit_cost.toFixed(2)}</td>
                    <td style="text-align: center;">
                        <input type="number" step="0.01" min="0.01" max="${item.max_returnable}" 
                            value="${item.quantity}" 
                            oninput="updateGridItemQty('${item.key}', this.value)"
                            style="width: 80px; height: 32px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-weight: 700; color: #0f172a; outline: none;">
                    </td>
                    <td style="text-align: right; font-weight: 800; font-family: monospace; color: #0f172a;" id="line_total_${item.key}">
                        LKR ${lineTotal.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2})}
                    </td>
                    <td>
                        <input type="text" value="${esc(item.reason)}" onchange="updateGridItemReason('${item.key}', this.value)" style="width: 100%; height: 32px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0 8px; font-size: 12px; outline: none;">
                    </td>
                    <td style="text-align: center;">
                        <button type="button" onclick="removeGridItem('${item.key}')" style="background: none; border: none; color: #ef4444; font-weight: 800; cursor: pointer; padding: 4px;">✕</button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    } else {
        // Just update line totals
        returnGridItems.forEach(item => {
            const lineTotal = item.quantity * item.unit_cost;
            const el = document.getElementById(`line_total_${item.key}`);
            if (el) {
                el.innerText = `LKR ${lineTotal.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2})}`;
            }
        });
    }

    let grandTotal = 0;
    returnGridItems.forEach(i => { grandTotal += (i.quantity * i.unit_cost); });
    document.getElementById('grandTotalSpan').innerText = `LKR ${grandTotal.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2})}`;
}

async function handleReturnSubmit(e) {
    e.preventDefault();
    const supplierId = document.getElementById('supplierSelect').value;
    const returnDate = document.getElementById('returnDate').value;
    const notes      = document.getElementById('returnNotes').value;

    if (!supplierId) {
        alert('Please select a supplier.');
        return;
    }

    if (!returnGridItems.length) {
        alert('Please add at least one product item to return.');
        return;
    }

    const items = returnGridItems.map(i => ({
        product_id: i.product_id,
        lot_id: i.lot_id,
        unit_cost: i.unit_cost,
        quantity: i.quantity,
        reason: i.reason
    }));

    if (!confirm(`Are you sure you want to process this Supplier Return for ${items.length} item(s)?`)) {
        return;
    }

    const btn = document.getElementById('btnSubmitReturn');
    btn.disabled = true;
    btn.innerText = 'Processing...';

    try {
        const res = await fetch('supplier_return_api.php?action=process_return', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                supplier_id: supplierId,
                return_date: returnDate,
                notes: notes,
                items: items
            })
        });

        const data = await res.json();
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerText = 'Process Supplier Return';
        }
    } catch (err) {
        console.error(err);
        alert('Failed to process supplier return.');
        btn.disabled = false;
        btn.innerText = 'Process Supplier Return';
    }
}

function viewReturnDetails(r) {
    let html = `
        <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 12px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                <div><strong style="color: #64748b;">Return Number:</strong> <span style="color: #2563eb; font-weight: 800; font-family: monospace;">${esc(r.return_number)}</span></div>
                <div><strong style="color: #64748b;">Return Date:</strong> <span style="color: #0f172a; font-weight: 700;">${esc(r.return_date)}</span></div>
                <div><strong style="color: #64748b;">Supplier:</strong> <span style="color: #0f172a; font-weight: 700;">${esc(r.supplier_name)}</span></div>
                <div><strong style="color: #64748b;">GRN Ref:</strong> <span style="font-weight: 700; color: #0f172a;">${esc(r.grn_number || 'Direct Return')}</span></div>
                <div><strong style="color: #64748b;">Logged By:</strong> <span style="color: #475569;">${esc(r.created_by_name || 'System')}</span></div>
                <div><strong style="color: #64748b;">Notes:</strong> <span style="color: #475569;">${esc(r.notes)}</span></div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                <div style="background: #f1f5f9; border-radius: 10px; padding: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Return Value</div>
                    <div style="font-size: 1.1rem; font-weight: 800; font-family: monospace; color: #0f172a;">LKR ${parseFloat(r.total_return_amount).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                </div>
                <div style="background: #f0fdf4; border-radius: 10px; padding: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #166534; text-transform: uppercase;">Applied AP Offset</div>
                    <div style="font-size: 1.1rem; font-weight: 800; font-family: monospace; color: #16a34a;">LKR ${parseFloat(r.applied_to_payable).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                </div>
                <div style="background: #eff6ff; border-radius: 10px; padding: 12px;">
                    <div style="font-size: 0.7rem; font-weight: 800; color: #1e40af; text-transform: uppercase;">Supplier Credit Created</div>
                    <div style="font-size: 1.1rem; font-weight: 800; font-family: monospace; color: #2563eb;">LKR ${parseFloat(r.credit_amount).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                </div>
            </div>

            <div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Returned Line Items</div>
                <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <table class="prod-table" style="font-size: 12px;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th>Product Details</th>
                                <th>Lot Code</th>
                                <th style="text-align: center; width: 70px;">Qty</th>
                                <th style="text-align: right; width: 110px;">Unit Cost</th>
                                <th style="text-align: right; width: 120px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
    `;

    if (r.items && r.items.length > 0) {
        r.items.forEach(it => {
            html += `
                <tr>
                    <td style="font-weight: 700; color: #0f172a;">${esc(it.product_name)} <span style="font-weight: 500; color: #64748b;">(${esc(it.item_code)})</span></td>
                    <td><span class="ret-badge">${esc(it.lot_code || 'N/A')}</span></td>
                    <td style="text-align: center; font-weight: 700;">${it.quantity}</td>
                    <td style="text-align: right; font-weight: 700; font-family: monospace;">LKR ${parseFloat(it.unit_cost).toFixed(2)}</td>
                    <td style="text-align: right; font-weight: 800; font-family: monospace; color: #0f172a;">LKR ${parseFloat(it.total_amount).toFixed(2)}</td>
                </tr>
            `;
        });
    } else {
        html += `<tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 14px;">No itemized records available.</td></tr>`;
    }

    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('detailsModal').classList.add('active');
}

/* ── Pagination Logic ── */
let currentPage = 1;
let pageSize = 25;

function updatePagination() {
    const table = document.getElementById('returnTable');
    if (!table) return;

    const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.classList.contains('prod-empty'));
    const visibleRows = allRows.filter(tr => tr.dataset.searchMatched !== 'false');

    const totalItems = visibleRows.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIdx = totalItems > 0 ? (currentPage - 1) * pageSize : 0;
    const endIdx = Math.min(startIdx + pageSize, totalItems);

    allRows.forEach(tr => tr.style.display = 'none');
    for (let i = startIdx; i < endIdx; i++) {
        if (visibleRows[i]) {
            visibleRows[i].style.display = '';
        }
    }

    document.getElementById('pagStart').textContent = totalItems > 0 ? startIdx + 1 : 0;
    document.getElementById('pagEnd').textContent = endIdx;
    document.getElementById('pagTotal').textContent = totalItems;

    const navContainer = document.getElementById('pagNavBtns');
    navContainer.innerHTML = '';

    const prevBtn = document.createElement('button');
    prevBtn.className = 'prod-act-btn';
    prevBtn.style.padding = '4px 10px';
    prevBtn.disabled = currentPage === 1;
    prevBtn.innerHTML = `← Prev`;
    prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; updatePagination(); } };
    navContainer.appendChild(prevBtn);

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    for (let p = startPage; p <= endPage; p++) {
        const pBtn = document.createElement('button');
        pBtn.className = 'prod-act-btn';
        pBtn.style.padding = '4px 10px';
        pBtn.style.minWidth = '28px';
        pBtn.style.justifyContent = 'center';
        if (p === currentPage) {
            pBtn.style.background = '#0f172a';
            pBtn.style.color = '#ffffff';
            pBtn.style.borderColor = '#0f172a';
        }
        pBtn.textContent = p;
        pBtn.onclick = () => { currentPage = p; updatePagination(); };
        navContainer.appendChild(pBtn);
    }

    const nextBtn = document.createElement('button');
    nextBtn.className = 'prod-act-btn';
    nextBtn.style.padding = '4px 10px';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.innerHTML = `Next →`;
    nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; updatePagination(); } };
    navContainer.appendChild(nextBtn);
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pagPageSize').value) || 25;
    currentPage = 1;
    updatePagination();
}

document.addEventListener('DOMContentLoaded', updatePagination);

/* Realtime Bottom Search */
function registerBottomSearch() {
    if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search returns across all columns, numbers & amounts...',
            initialValue: <?= json_encode($currentSearch ?? '') ?>,
            onSearch: function(query) {
                filterReturnsRealtime(query);
            },
            onEnter: function(query) {
                const cleanQ = encodeURIComponent(query.trim());
                window.location.href = 'supplier_returns' + (cleanQ ? '?search=' + cleanQ : '');
            }
        });
    } else {
        setTimeout(registerBottomSearch, 40);
    }
}
registerBottomSearch();

function filterReturnsRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#returnTable tbody tr');
    rows.forEach(tr => {
        if (tr.classList.contains('prod-empty')) return;
        const text = (tr.dataset.search || tr.textContent).toLowerCase();
        if (!q || text.includes(q)) {
            tr.dataset.searchMatched = 'true';
        } else {
            tr.dataset.searchMatched = 'false';
        }
    });
    currentPage = 1;
    updatePagination();
}

/* CSV Export Modal Handler */
function startExportCSV(event, exportUrl) {
    if (event) event.preventDefault();

    const backdrop = document.getElementById('expBackdrop');
    const modal = document.getElementById('expModal');
    const bar = document.getElementById('expProgressBar');
    const statusText = document.getElementById('expStatusText');
    const title = document.getElementById('expTitle');
    const subtitle = document.getElementById('expSubtitle');
    const iconBox = document.getElementById('expIconBox');
    const closeBtn = document.getElementById('expCloseBtn');

    title.textContent = 'Exporting Returns';
    subtitle.textContent = 'Preparing return records...';
    statusText.textContent = '0%';
    bar.style.width = '0%';
    closeBtn.style.display = 'none';
    iconBox.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5" class="exp-spinner"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`;

    backdrop.classList.add('active');
    modal.classList.add('active');

    let currentProgress = 0;
    const progressInterval = setInterval(() => {
        if (currentProgress < 85) {
            currentProgress += Math.floor(Math.random() * 12) + 6;
            if (currentProgress > 85) currentProgress = 85;
            bar.style.width = currentProgress + '%';
            statusText.textContent = currentProgress + '%';
        }
    }, 180);

    const xhr = new XMLHttpRequest();
    xhr.open('GET', exportUrl, true);
    xhr.responseType = 'blob';

    xhr.onload = function() {
        clearInterval(progressInterval);
        if (xhr.status === 200) {
            bar.style.width = '100%';
            statusText.textContent = '100%';
            title.textContent = 'Export Complete';
            subtitle.textContent = 'Downloading CSV file...';
            iconBox.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>`;

            let filename = 'supplier_returns_export.csv';
            const disposition = xhr.getResponseHeader('Content-Disposition');
            if (disposition && disposition.indexOf('filename=') !== -1) {
                const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                if (matches != null && matches[1]) {
                    filename = matches[1].replace(/['"]/g, '');
                }
            }

            const blob = xhr.response;
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            setTimeout(() => { closeExportModal(); }, 1100);
        } else {
            title.textContent = 'Export Failed';
            subtitle.textContent = 'Could not generate CSV file.';
            iconBox.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;
            closeBtn.style.display = 'block';
        }
    };

    xhr.onerror = function() {
        clearInterval(progressInterval);
        title.textContent = 'Export Failed';
        subtitle.textContent = 'Network error.';
        closeBtn.style.display = 'block';
    };

    xhr.send();
}

function closeExportModal() {
    document.getElementById('expBackdrop').classList.remove('active');
    document.getElementById('expModal').classList.remove('active');
}
</script>
