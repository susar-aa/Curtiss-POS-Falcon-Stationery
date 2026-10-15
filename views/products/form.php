<?php
/**
 * Curtiss POS - Product Form View (Create & Edit)
 * Spacious Full-Width Glassmorphism Form Layout (With Searchable Selects & Quick Add)
 */

$title = $isEdit ? 'Edit Product #' . (int)$product['id'] : 'Add New Product';
$formAction = $isEdit ? 'product_edit?id=' . (int)$product['id'] : 'product_create';
?>

<!-- Include Searchable Select Styles & Assets -->
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Pill -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="products" class="btn btn-secondary" style="border-radius: 9999px; font-weight: 500; font-size: 0.875rem; padding: 0.5rem 1.15rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); background: #ffffff; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to Products List
    </a>
</div>

<style>
.form-fullscreen-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 18px;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
    padding: 28px 32px;
    width: 100%;
    box-sizing: border-box;
}

.sec-title-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}

.sec-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #0f172a;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
}

.f-label {
    display: block;
    font-size: 0.88rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
}

.f-input {
    width: 100%;
    height: 44px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0 14px;
    font-size: 0.92rem;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.f-input:focus {
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}

.f-input[readonly], .f-input[disabled] {
    background-color: #f8fafc;
    color: #64748b;
    border-color: #e2e8f0;
    cursor: not-allowed;
}

.f-subtext {
    display: block;
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 4px;
}

.grid-3-col {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 18px;
}

.grid-4-col {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 18px;
}

/* Glassmorphism Modal Styles */
.curtiss-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.curtiss-modal-card {
    background: rgba(255, 255, 255, 0.98);
    width: 100%;
    max-width: 440px;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(226, 232, 240, 0.9);
    animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(12px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 1024px) {
    .grid-4-col {
        grid-template-columns: repeat(2, 1fr);
    }
    .grid-3-col {
        grid-template-columns: repeat(2, 1fr);
    }
}

.curtiss-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    margin: 0;
    cursor: pointer;
}
.curtiss-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.curtiss-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .25s ease;
    border-radius: 24px;
}
.curtiss-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .25s ease;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.curtiss-switch input:checked + .curtiss-slider {
    background-color: #0f172a;
}
.curtiss-switch input:checked + .curtiss-slider:before {
    transform: translateX(20px);
}
.curtiss-switch:focus,
.curtiss-switch:focus-within,
.curtiss-switch.focused {
    outline: none;
}
.curtiss-switch:focus .curtiss-slider,
.curtiss-switch:focus-within .curtiss-slider,
.curtiss-switch.focused .curtiss-slider {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.45);
}
.expiry-toggle-row.focused {
    border-color: #2563eb !important;
    background: #eff6ff !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
}
</style>

<div style="width: 100%; max-width: 100%; padding-bottom: 24px;">
    <!-- Page Header -->
    <div style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 10px; height: 10px; border-radius: 50%; background: #0f172a;"></div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em;"><?= View::escape($title) ?></h1>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div style="background: rgba(254, 242, 242, 0.95); border: 1px solid #fca5a5; border-radius: 14px; padding: 14px 20px; margin-bottom: 20px; color: #991b1b; backdrop-filter: blur(8px);">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Please correct the following errors:
            </div>
            <ul style="margin: 0; padding-left: 24px; font-size: 0.85rem; line-height: 1.5;">
                <?php foreach ($errors as $err): ?>
                    <li><?= View::escape($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Full Width Glassmorphism Card -->
    <div class="form-fullscreen-card">
        <form method="POST" action="<?= $formAction ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
            <?php endif; ?>

            <!-- SECTION 1: IDENTIFICATION & CATEGORIZATION -->
            <div class="sec-title-bar">
                <span class="sec-badge">1</span>
                Product Identification & Categorization
            </div>

            <!-- Row 1: Barcode, SKU, Brand -->
            <div class="grid-3-col">
                <div>
                    <label class="f-label" for="barcode">1. Barcode <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="barcode" id="barcode" class="f-input" value="<?= View::escape($product['barcode'] ?? '') ?>" placeholder="e.g. 893450001001" required autofocus>
                </div>

                <div>
                    <label class="f-label" for="sku">SKU / Product Code</label>
                    <input type="text" name="sku" id="sku" class="f-input" value="<?= View::escape($product['sku'] ?? $product['barcode'] ?? '') ?>" placeholder="e.g. SKU001">
                </div>

                <div>
                    <label class="f-label" for="brand_id">2. Brand <span style="color: #ef4444;">*</span></label>
                    <select name="brand_id" id="brand_id" class="f-input curtiss-select-enhance" data-create-type="brand" required>
                        <option value="">-- Select Brand --</option>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= $b['id'] ?>" data-name="<?= View::escape($b['name']) ?>" <?= ((int)($product['brand_id'] ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                                <?= View::escape($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 2: Category, Supplier -->
            <div class="grid-2-col">
                <div>
                    <label class="f-label" for="category_id">3. Category <span style="color: #ef4444;">*</span></label>
                    <select name="category_id" id="category_id" class="f-input curtiss-select-enhance" data-create-type="category" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" data-name="<?= View::escape($c['name']) ?>" <?= ((int)($product['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= View::escape($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="f-label" for="supplier_id">5. Supplier <span style="color: #ef4444;">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="f-input curtiss-select-enhance" data-create-type="supplier" required>
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ((int)($product['supplier_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                                <?= View::escape($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 3: Product Name (Full Width Across Grid) -->
            <div style="margin-bottom: 24px;">
                <label class="f-label" for="name">4. Product Name <span style="color: #ef4444;">*</span></label>
                <input type="text" name="name" id="name" class="f-input" data-cursor-end="true" autocomplete="off" value="<?= View::escape($product['name'] ?? '') ?>" placeholder="e.g. Atlas Ball Pen Blue 0.5mm" required>
                <span class="f-subtext">Auto-starts with Brand + Category when selected. Fully editable.</span>
            </div>

            <!-- SECTION 2: PRICING & INVENTORY -->
            <div class="sec-title-bar" style="margin-top: 10px;">
                <span class="sec-badge">2</span>
                Pricing & Inventory
            </div>

            <!-- Row 4: Cost, Selling Price, Opening Stock, Low Stock Threshold -->
            <?php
            $displayCost = '';
            if (isset($product['cost']) && $product['cost'] !== '' && (float)$product['cost'] > 0) {
                $displayCost = $product['cost'];
            } elseif ($isEdit && isset($product['cost']) && $product['cost'] !== '') {
                $displayCost = $product['cost'];
            }

            $displayPrice = '';
            if (isset($product['selling_price']) && $product['selling_price'] !== '' && (float)$product['selling_price'] > 0) {
                $displayPrice = $product['selling_price'];
            } elseif ($isEdit && isset($product['selling_price']) && $product['selling_price'] !== '') {
                $displayPrice = $product['selling_price'];
            }

            $displayOpeningStock = '';
            if (isset($product['opening_stock']) && $product['opening_stock'] !== '' && (float)$product['opening_stock'] > 0) {
                $displayOpeningStock = $product['opening_stock'];
            } elseif ($isEdit && isset($product['opening_stock']) && $product['opening_stock'] !== '') {
                $displayOpeningStock = $product['opening_stock'];
            }
            ?>
            <div class="grid-4-col" style="margin-bottom: 24px;">
                <div>
                    <label class="f-label" for="cost">6. Cost Price (LKR) <span style="color: #ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0" name="cost" id="cost" class="f-input" value="<?= View::escape($displayCost) ?>" required>
                </div>

                <div>
                    <label class="f-label" for="selling_price">7. Selling Price (LKR) <span style="color: #ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0" name="selling_price" id="selling_price" class="f-input" value="<?= View::escape($displayPrice) ?>" required>
                </div>

                <div>
                    <label class="f-label" for="opening_stock">8. Opening Stock <span style="color: #ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0" name="opening_stock" id="opening_stock" class="f-input" value="<?= View::escape($displayOpeningStock) ?>" <?= $isEdit ? 'readonly' : 'required' ?> oninput="checkExpiryRequirement()">
                    <?php if ($isEdit): ?>
                        <span class="f-subtext">Set at creation. Use Stock Adjustments for live changes.</span>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="f-label" for="low_stock_threshold">Low Stock Warning</label>
                    <input type="number" step="0.01" min="0" name="low_stock_threshold" id="low_stock_threshold" class="f-input" value="<?= View::escape($product['low_stock_threshold'] ?? '10.00') ?>" required>
                </div>
            </div>

            <!-- SECTION 3: EXPIRY & BATCH TRACKING (Moved to Bottom) -->
            <div class="sec-title-bar" style="margin-top: 10px;">
                <span class="sec-badge">3</span>
                Expiry & Batch Tracking
            </div>

            <!-- Expiry Tracking Toggle Row -->
            <div id="expiryToggleContainer" class="expiry-toggle-row" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; transition: all 0.2s ease;">
                <div>
                    <div style="font-size: 0.92rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Track Expiry Dates & Batches for this Product
                    </div>
                    <div style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">
                        When enabled, incoming stock requires an Expiry Date. POS uses FEFO (First Expiring, First Out) and blocks expired batches.
                    </div>
                </div>
                <label class="curtiss-switch" id="expirySwitchLabel" tabindex="0" role="switch" aria-checked="<?= (!empty($product['has_expiry'])) ? 'true' : 'false' ?>" title="Press Left Arrow to turn OFF, Right Arrow to turn ON">
                    <input type="checkbox" name="has_expiry" id="has_expiry" value="1" <?= (!empty($product['has_expiry'])) ? 'checked' : '' ?> onchange="toggleExpirySection(this.checked)">
                    <span class="curtiss-slider"></span>
                </label>
            </div>

            <!-- Opening Stock Expiry & Batch Inputs (Visible when has_expiry is ON during Creation) -->
            <?php if (!$isEdit): ?>
            <div id="expiry_opening_section" style="display: <?= (!empty($product['has_expiry'])) ? 'block' : 'none' ?>; margin-bottom: 24px; padding: 16px 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px;">
                <div style="font-size: 0.88rem; font-weight: 700; color: #1e40af; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Opening Stock Expiry Date & Batch
                </div>
                <div class="grid-2-col">
                    <div>
                        <label class="f-label" for="expiry_date">Expiry Date <span id="expiry_req_star" style="color: #ef4444;">*</span></label>
                        <input type="date" name="expiry_date" id="expiry_date" class="f-input" min="<?= date('Y-m-d') ?>" value="<?= View::escape($product['expiry_date'] ?? '') ?>">
                        <span class="f-subtext">Mandatory when opening stock quantity is greater than zero</span>
                    </div>
                    <div>
                        <label class="f-label" for="batch_number">Batch Reference (Optional)</label>
                        <input type="text" name="batch_number" id="batch_number" class="f-input" placeholder="e.g. BATCH-2026-001" value="<?= View::escape($product['batch_number'] ?? '') ?>">
                        <span class="f-subtext">Manufacturer lot number or supplier batch code</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bottom Action Buttons Footer -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 14px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                <a href="products" style="border-radius: 9999px; font-weight: 600; font-size: 0.88rem; padding: 11px 24px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; text-decoration: none; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;">
                    Cancel
                </a>
                <button type="submit" id="btnSaveProduct" style="border-radius: 9999px; font-weight: 600; font-size: 0.88rem; padding: 11px 32px; background: #0f172a; border: none; color: #ffffff; cursor: pointer; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2); transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
                    <?= $isEdit ? 'Update Product' : 'Create Product' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Quick Adding Brand / Category / Supplier -->
<div id="quickAddModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card" style="max-width: 420px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 id="quickAddTitle" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Quick Add Item</h3>
            </div>
            <button type="button" onclick="closeQuickAddModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; transition: color 0.15s ease;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="quickAddForm" onsubmit="handleQuickAddSubmit(event)">
            <input type="hidden" id="quickAddType" value="">

            <div style="margin-bottom: 16px;">
                <label id="quickAddLabel" style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em;">Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="quickAddName" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="e.g. Atlas" required>
            </div>

            <div id="quickAddExtraField" style="display: none; margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em;">Phone Number</label>
                <input type="text" id="quickAddPhone" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="e.g. 0771234567">
            </div>

            <div id="quickAddError" style="display: none; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 14px; font-weight: 600;"></div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="btn btn-secondary" style="border-radius: 9999px; font-size: 0.85rem; padding: 8px 18px;" onclick="closeQuickAddModal()">Cancel</button>
                <button type="submit" id="quickAddSubmitBtn" class="btn btn-primary" style="border-radius: 9999px; font-size: 0.85rem; padding: 8px 22px; background: #0f172a; border: none; color: #fff; cursor: pointer;">Save & Select</button>
            </div>
        </form>
    </div>
</div>

<!-- Searchable Select & Auto-Generation Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Searchable Select Components
    const brandSelectEl = document.getElementById('brand_id');
    const catSelectEl = document.getElementById('category_id');
    const supSelectEl = document.getElementById('supplier_id');

    if (window.CurtissSearchableSelect) {
        window.CurtissSearchableSelect.attach(brandSelectEl, { placeholder: '-- Select Brand --', searchPlaceholder: 'Search brand...' });
        window.CurtissSearchableSelect.attach(catSelectEl, { placeholder: '-- Select Category --', searchPlaceholder: 'Search category...' });
        window.CurtissSearchableSelect.attach(supSelectEl, { placeholder: '-- Select Supplier --', searchPlaceholder: 'Search supplier...' });
    }

    // 2. Product Name Auto-Generation & Auto-Capitalization Logic
    const nameInput = document.getElementById('name');
    if (!brandSelectEl || !catSelectEl || !nameInput) return;

    function capitalizeWords(str) {
        return (str || '').replace(/(^|[\s\-\/\(])([a-z])/g, function(match, prefix, char) {
            return prefix + char.toUpperCase();
        });
    }

    let previousPrefix = '';

    function updateNameAutoPrefix() {
        const selectedBrandOpt = brandSelectEl.options[brandSelectEl.selectedIndex];
        const selectedCatOpt = catSelectEl.options[catSelectEl.selectedIndex];

        const brandName = selectedBrandOpt && selectedBrandOpt.value ? (selectedBrandOpt.getAttribute('data-name') || selectedBrandOpt.text || '') : '';
        const catName = selectedCatOpt && selectedCatOpt.value ? (selectedCatOpt.getAttribute('data-name') || selectedCatOpt.text || '') : '';

        let newPrefix = '';
        if (brandName && catName) {
            newPrefix = brandName + ' ' + catName;
        } else if (brandName) {
            newPrefix = brandName;
        } else if (catName) {
            newPrefix = catName;
        }

        newPrefix = capitalizeWords(newPrefix).trim();

        const currentNameVal = nameInput.value.trim();

        if (!currentNameVal) {
            nameInput.value = newPrefix ? (newPrefix + ' ') : '';
        } else if (previousPrefix && currentNameVal.startsWith(previousPrefix)) {
            const extraText = currentNameVal.substring(previousPrefix.length).trimStart();
            nameInput.value = (newPrefix ? (newPrefix + ' ') : '') + extraText;
        } else if (newPrefix && !currentNameVal.includes(newPrefix)) {
            if (currentNameVal === previousPrefix || !previousPrefix) {
                nameInput.value = newPrefix + ' ';
            }
        }

        previousPrefix = newPrefix;
    }

    brandSelectEl.addEventListener('change', updateNameAutoPrefix);
    catSelectEl.addEventListener('change', updateNameAutoPrefix);

    // Focus without highlighting: place cursor at end after space
    nameInput.addEventListener('focus', function() {
        if (this.value && !this.value.endsWith(' ')) {
            this.value = this.value.trim() + ' ';
        }
        const len = this.value.length;
        setTimeout(() => {
            if (typeof this.setSelectionRange === 'function') {
                this.setSelectionRange(len, len);
            }
        }, 10);
    });

    // Automatically capitalize first letter of all words while typing
    nameInput.addEventListener('input', function() {
        const selStart = this.selectionStart;
        const selEnd = this.selectionEnd;
        const oldVal = this.value;
        const newVal = capitalizeWords(oldVal);
        if (oldVal !== newVal) {
            this.value = newVal;
            if (typeof this.setSelectionRange === 'function') {
                this.setSelectionRange(selStart, selEnd);
            }
        }
    });
});

/* ── Quick Add Modal Script ── */
let currentQuickAddType = '';

function openQuickAddModal(type) {
    currentQuickAddType = type;
    document.getElementById('quickAddType').value = type;
    document.getElementById('quickAddName').value = '';
    document.getElementById('quickAddPhone').value = '';
    document.getElementById('quickAddError').style.display = 'none';

    const titleEl = document.getElementById('quickAddTitle');
    const labelEl = document.getElementById('quickAddLabel');
    const extraEl = document.getElementById('quickAddExtraField');

    if (type === 'brand') {
        titleEl.textContent = 'Quick Add New Brand';
        labelEl.innerHTML = 'Brand Name <span style="color: #ef4444;">*</span>';
        extraEl.style.display = 'none';
    } else if (type === 'category') {
        titleEl.textContent = 'Quick Add New Category';
        labelEl.innerHTML = 'Category Name <span style="color: #ef4444;">*</span>';
        extraEl.style.display = 'none';
    } else if (type === 'supplier') {
        titleEl.textContent = 'Quick Add New Supplier';
        labelEl.innerHTML = 'Supplier Name <span style="color: #ef4444;">*</span>';
        extraEl.style.display = 'block';
    }

    document.getElementById('quickAddModal').style.display = 'flex';
    setTimeout(() => document.getElementById('quickAddName').focus(), 60);
}

function closeQuickAddModal() {
    document.getElementById('quickAddModal').style.display = 'none';
}

function handleQuickAddSubmit(e) {
    e.preventDefault();
    const type = document.getElementById('quickAddType').value;
    const name = document.getElementById('quickAddName').value.trim();
    const phone = document.getElementById('quickAddPhone').value.trim();
    const errDiv = document.getElementById('quickAddError');
    const submitBtn = document.getElementById('quickAddSubmitBtn');

    if (!name) return;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    errDiv.style.display = 'none';

    fetch('api/quick_create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type, name, phone })
    })
    .then(r => r.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save & Select';
        if (data.success) {
            closeQuickAddModal();
            let targetEl = null;
            if (type === 'brand') targetEl = document.getElementById('brand_id');
            else if (type === 'category') targetEl = document.getElementById('category_id');
            else if (type === 'supplier') targetEl = document.getElementById('supplier_id');

            if (targetEl && targetEl._curtissSelect) {
                targetEl._curtissSelect.addItem(data.id, data.name, true);
            }
        } else {
            errDiv.textContent = data.message || 'Error saving item.';
            errDiv.style.display = 'block';
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save & Select';
        errDiv.textContent = 'Network error while saving.';
        errDiv.style.display = 'block';
    });
}

function toggleExpirySection(checked) {
    const sec = document.getElementById('expiry_opening_section');
    if (sec) {
        sec.style.display = checked ? 'block' : 'none';
    }
    checkExpiryRequirement();
}

function checkExpiryRequirement() {
    const hasExp = document.getElementById('has_expiry')?.checked;
    const openStock = parseFloat(document.getElementById('opening_stock')?.value || 0);
    const expInput = document.getElementById('expiry_date');
    if (expInput) {
        if (hasExp && openStock > 0) {
            expInput.required = true;
        } else {
            expInput.required = false;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
        if (window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(el);
        }
    });
    checkExpiryRequirement();

    // ── Keyboard Enter & Arrow Key Navigation Loop ──
    const nameInput = document.getElementById('name');
    const costInput = document.getElementById('cost');
    const priceInput = document.getElementById('selling_price');
    const openStockInput = document.getElementById('opening_stock');
    const lowStockInput = document.getElementById('low_stock_threshold');
    const switchInput = document.getElementById('has_expiry');
    const switchLabel = document.getElementById('expirySwitchLabel');
    const expDateInput = document.getElementById('expiry_date');
    const batchInput = document.getElementById('batch_number');
    const saveBtn = document.getElementById('btnSaveProduct');

    nameInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            costInput?.focus();
            costInput?.select?.();
        }
    });

    costInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            priceInput?.focus();
            priceInput?.select?.();
        }
    });

    priceInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            openStockInput?.focus();
            openStockInput?.select?.();
        }
    });

    openStockInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            lowStockInput?.focus();
            lowStockInput?.select?.();
        }
    });

    // Tap Enter from "Low Stock Warning" -> Focus on the Expiry trigger
    lowStockInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (switchLabel) {
                switchLabel.focus();
            } else if (switchInput) {
                switchInput.focus();
            }
        }
    });

    // Expiry trigger keyboard controller: Right/Left arrow keys to change status, Enter to advance
    function handleSwitchKeydown(e) {
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            if (!switchInput.checked) {
                switchInput.checked = true;
                switchLabel?.setAttribute('aria-checked', 'true');
                toggleExpirySection(true);
            }
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            if (switchInput.checked) {
                switchInput.checked = false;
                switchLabel?.setAttribute('aria-checked', 'false');
                toggleExpirySection(false);
            }
        } else if (e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            switchInput.checked = !switchInput.checked;
            switchLabel?.setAttribute('aria-checked', switchInput.checked ? 'true' : 'false');
            toggleExpirySection(switchInput.checked);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (switchInput.checked) {
                // If turned ON, focus on Expiry Date *
                if (expDateInput && expDateInput.offsetParent !== null) {
                    expDateInput.focus();
                    if (typeof expDateInput.select === 'function') expDateInput.select();
                } else if (saveBtn) {
                    saveBtn.focus();
                }
            } else {
                // If keep OFF, focus on Save Product
                if (saveBtn) {
                    saveBtn.focus();
                }
            }
        }
    }

    if (switchLabel) {
        switchLabel.addEventListener('keydown', handleSwitchKeydown);
        switchLabel.addEventListener('focus', () => {
            document.getElementById('expiryToggleContainer')?.classList.add('focused');
        });
        switchLabel.addEventListener('blur', () => {
            document.getElementById('expiryToggleContainer')?.classList.remove('focused');
        });
    }

    if (switchInput) {
        switchInput.addEventListener('keydown', handleSwitchKeydown);
        switchInput.addEventListener('focus', () => {
            document.getElementById('expiryToggleContainer')?.classList.add('focused');
        });
        switchInput.addEventListener('blur', () => {
            document.getElementById('expiryToggleContainer')?.classList.remove('focused');
        });
    }

    // Expiry Date Enter handler -> Focus Batch Reference or Save Product
    if (expDateInput) {
        expDateInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (batchInput && batchInput.offsetParent !== null) {
                    batchInput.focus();
                    if (typeof batchInput.select === 'function') batchInput.select();
                } else if (saveBtn) {
                    saveBtn.focus();
                }
            }
        });
    }

    // Batch Reference Enter handler -> Focus Save Product
    if (batchInput) {
        batchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (saveBtn) {
                    saveBtn.focus();
                }
            }
        });
    }
});
</script>
