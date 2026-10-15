<?php
/**
 * Falcon POS - Quick Product & Stock Editor View
 */
?>
<style>
/* Quick Editor Specific Styles */
.qe-container {
    width: 100%;
    margin: 0 auto;
}
.qe-header-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.qe-search-box {
    position: relative;
    flex: 1;
    min-width: 280px;
}
.qe-search-input {
    width: 100%;
    height: 44px;
    padding: 0 42px 0 42px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.92rem;
    color: #0f172a;
    background: #f8fafc;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.qe-search-input:focus {
    background: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    outline: none;
}
.qe-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}
.qe-clear-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    display: none;
}
.qe-clear-icon:hover { color: #0f172a; }

.qe-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
}
.qe-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    text-align: left;
}
.qe-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.76rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.85rem 0.75rem;
    border-bottom: 1.5px solid #e2e8f0;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}
.qe-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}
.qe-table tbody tr:hover {
    background: #f8fafc;
}
.qe-table tbody tr.row-dirty {
    background: #fffbeb !important;
}
.qe-table tbody tr.row-saved {
    background: #f0fdf4 !important;
    animation: flashGreen 1.5s ease;
}
.qe-table td {
    padding: 0.65rem 0.75rem;
    vertical-align: middle;
}

/* Inline Inputs */
.qe-input {
    width: 100%;
    height: 36px;
    padding: 0 10px;
    border: 1.5px solid #cbd5e1;
    border-radius: 7px;
    font-size: 0.88rem;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.qe-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.15);
    outline: none;
    background: #ffffff;
}
.qe-input-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.qe-input-stock {
    font-weight: 800;
    color: #0f172a;
}
.qe-input.dirty {
    border-color: #f59e0b;
    background: #fffdf5;
}

/* Action Buttons */
.qe-btn-update {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 36px;
    padding: 0 16px;
    border-radius: 7px;
    background: #0f172a;
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.qe-btn-update:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(15,23,42,0.2);
}
.qe-btn-update:active {
    transform: translateY(0);
}
.qe-btn-update.btn-saved {
    background: #16a34a !important;
}
.qe-btn-update.btn-error {
    background: #dc2626 !important;
}

@keyframes flashGreen {
    0% { background: #dcfce7; }
    100% { background: #ffffff; }
}

/* Toast alert */
.qe-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 9999;
    display: none;
    align-items: center;
    gap: 10px;
    animation: slideUp 0.25s ease;
}
@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<div class="qe-container">
    <!-- Header Title & Active Operating Branch -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 1.25rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1 style="margin: 0; font-size: 1.55rem; font-weight: 800; color: #0f172a;">Quick Product & Stock Editor</h1>
                <span class="badge" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: 6px;">
                    Branch: <?= View::escape($activeBranchName) ?>
                </span>
            </div>
            <p style="margin: 0.35rem 0 0 0; color: #64748b; font-size: 0.88rem;">
                Edit product names, barcodes, prices, and update <strong>live branch inventory</strong> directly in real time.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="products.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Product Master
            </a>
            <a href="product_import.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Import CSV
            </a>
        </div>
    </div>

    <!-- Search & Controls Bar -->
    <div class="qe-header-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <!-- Realtime Search Bar -->
            <div class="qe-search-box">
                <svg class="qe-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="qeSearchInput" class="qe-search-input" placeholder="Type to search by Product Name, Barcode, or SKU in real-time..." autofocus>
                <span id="qeClearBtn" class="qe-clear-icon" onclick="clearSearch()">&times;</span>
            </div>

            <!-- Stats & Quick Actions -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">
                    Showing <strong id="qeRowCount" style="color: #0f172a;"><?= count($products) ?></strong> of <strong id="qeTotalCount" style="color: #0f172a;"><?= $totalCount ?></strong> products
                </div>
                <button type="button" id="qeSaveAllBtn" class="btn" onclick="saveAllModified()" style="display: none; background: #f59e0b; color: #ffffff; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                    ⚡ Save All Modified (<span id="dirtyCount">0</span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Editable Products Table -->
    <div class="qe-table-card">
        <div class="table-responsive" style="max-height: 72vh; overflow-y: auto;">
            <table class="qe-table" id="qeProductTable">
                <thead>
                    <tr>
                        <th style="width: 70px;"># ID</th>
                        <th style="min-width: 260px;">Product Name *</th>
                        <th style="width: 170px;">Barcode *</th>
                        <th style="width: 130px; text-align: right;">Cost (LKR)</th>
                        <th style="width: 140px; text-align: right;">Selling Price (LKR)</th>
                        <th style="width: 130px; text-align: right;">Stock (Live)</th>
                        <th style="width: 110px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="qeTableBody">
                    <?php if (empty($products)): ?>
                        <tr id="noRowsNotice">
                            <td colspan="7" style="padding: 3rem; text-align: center; color: #64748b;">
                                <div style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem;">No products found</div>
                                <div style="font-size: 0.85rem;">Import products or add new items to get started.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr id="row_<?= (int)$p['id'] ?>" data-id="<?= (int)$p['id'] ?>">
                                <!-- ID / SKU -->
                                <td>
                                    <span style="font-weight: 800; color: #475569;">#<?= (int)$p['id'] ?></span>
                                    <?php if (!empty($p['sku']) && $p['sku'] !== $p['barcode']): ?>
                                        <div style="font-size: 0.7rem; color: #94a3b8; font-family: monospace;"><?= View::escape($p['sku']) ?></div>
                                    <?php endif; ?>
                                </td>

                                <!-- Product Name -->
                                <td>
                                    <input type="text" class="qe-input field-name" value="<?= View::escape($p['name']) ?>" 
                                           data-original="<?= View::escape($p['name']) ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Barcode -->
                                <td>
                                    <input type="text" class="qe-input field-barcode" style="font-family: monospace;" value="<?= View::escape($p['barcode']) ?>" 
                                           data-original="<?= View::escape($p['barcode']) ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Cost Price -->
                                <td>
                                    <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-cost" value="<?= number_format((float)$p['cost'], 2, '.', '') ?>" 
                                           data-original="<?= number_format((float)$p['cost'], 2, '.', '') ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Selling Price -->
                                <td>
                                    <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-price" value="<?= number_format((float)$p['selling_price'], 2, '.', '') ?>" 
                                           data-original="<?= number_format((float)$p['selling_price'], 2, '.', '') ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Live Branch Inventory Stock -->
                                <td>
                                    <input type="number" step="1" class="qe-input qe-input-num qe-input-stock field-stock" value="<?= (float)$p['stock'] ?>" 
                                           data-original="<?= (float)$p['stock'] ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Action Button -->
                                <td style="text-align: center;">
                                    <button type="button" class="qe-btn-update" id="btn_<?= (int)$p['id'] ?>" onclick="saveProduct(<?= (int)$p['id'] ?>)">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Toast Alert Box -->
<div id="qeToast" class="qe-toast">
    <span id="qeToastIcon">✓</span>
    <span id="qeToastMsg">Update saved successfully!</span>
</div>

<script>
let searchTimeout = null;
const modifiedRows = new Set();

function markDirty(id) {
    const row = document.getElementById('row_' + id);
    if (!row) return;

    const name = row.querySelector('.field-name');
    const barcode = row.querySelector('.field-barcode');
    const cost = row.querySelector('.field-cost');
    const price = row.querySelector('.field-price');
    const stock = row.querySelector('.field-stock');

    const isChanged = (
        name.value !== name.dataset.original ||
        barcode.value !== barcode.dataset.original ||
        parseFloat(cost.value || 0) !== parseFloat(cost.dataset.original || 0) ||
        parseFloat(price.value || 0) !== parseFloat(price.dataset.original || 0) ||
        parseFloat(stock.value || 0) !== parseFloat(stock.dataset.original || 0)
    );

    if (isChanged) {
        row.classList.add('row-dirty');
        modifiedRows.add(id);
    } else {
        row.classList.remove('row-dirty');
        modifiedRows.delete(id);
    }

    updateSaveAllBadge();
}

function updateSaveAllBadge() {
    const saveAllBtn = document.getElementById('qeSaveAllBtn');
    const countSpan = document.getElementById('dirtyCount');
    if (modifiedRows.size > 0) {
        saveAllBtn.style.display = 'inline-flex';
        countSpan.textContent = modifiedRows.size;
    } else {
        saveAllBtn.style.display = 'none';
    }
}

function checkEnter(event, id) {
    if (event.key === 'Enter') {
        event.preventDefault();
        saveProduct(id);
    }
}

function saveProduct(id) {
    const row = document.getElementById('row_' + id);
    if (!row) return;

    const btn = document.getElementById('btn_' + id);
    const nameInput = row.querySelector('.field-name');
    const barcodeInput = row.querySelector('.field-barcode');
    const costInput = row.querySelector('.field-cost');
    const priceInput = row.querySelector('.field-price');
    const stockInput = row.querySelector('.field-stock');

    const name = nameInput.value.trim();
    const barcode = barcodeInput.value.trim();
    const cost = parseFloat(costInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    const stock = parseFloat(stockInput.value) || 0;

    if (!name) {
        showToast('Product name cannot be empty', true);
        nameInput.focus();
        return;
    }
    if (!barcode) {
        showToast('Barcode cannot be empty', true);
        barcodeInput.focus();
        return;
    }

    // Button loading state
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="animation: spin 0.8s linear infinite;"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/></svg> Saving...';

    const formData = new FormData();
    formData.append('action', 'update_product');
    formData.append('id', id);
    formData.append('name', name);
    formData.append('barcode', barcode);
    formData.append('cost', cost);
    formData.append('selling_price', price);
    formData.append('stock', stock);

    fetch('quick_products.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.success) {
            btn.classList.add('btn-saved');
            btn.innerHTML = '✓ Saved!';

            // Update originals
            nameInput.dataset.original = name;
            barcodeInput.dataset.original = barcode;
            costInput.dataset.original = cost.toFixed(2);
            priceInput.dataset.original = price.toFixed(2);
            stockInput.dataset.original = stock;

            row.classList.remove('row-dirty');
            row.classList.add('row-saved');
            modifiedRows.delete(id);
            updateSaveAllBadge();

            showToast(res.message || 'Product & stock updated successfully!');

            setTimeout(() => {
                btn.classList.remove('btn-saved');
                btn.innerHTML = 'Update';
                row.classList.remove('row-saved');
            }, 1800);
        } else {
            btn.classList.add('btn-error');
            btn.innerHTML = '✕ Error';
            showToast(res.message || 'Failed to update product', true);

            setTimeout(() => {
                btn.classList.remove('btn-error');
                btn.innerHTML = 'Update';
            }, 2500);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = 'Update';
        showToast('Network error while saving product.', true);
        console.error(err);
    });
}

function saveAllModified() {
    if (modifiedRows.size === 0) return;
    const ids = Array.from(modifiedRows);
    ids.forEach(id => {
        saveProduct(id);
    });
}

// ─── Real-Time Search Engine ──────────────────────────────────────────────────
const searchInput = document.getElementById('qeSearchInput');
const clearBtn = document.getElementById('qeClearBtn');

searchInput.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length > 0) {
        clearBtn.style.display = 'block';
    } else {
        clearBtn.style.display = 'none';
    }

    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performRealtimeSearch(q);
    }, 180);
});

function clearSearch() {
    searchInput.value = '';
    clearBtn.style.display = 'none';
    performRealtimeSearch('');
    searchInput.focus();
}

function performRealtimeSearch(query) {
    const qLower = query.toLowerCase();

    fetch('quick_products.php?action=search&q=' + encodeURIComponent(query))
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        renderSearchResults(res.products, query);
    })
    .catch(err => {
        // Fallback: Client-side row hide/show if server fetch fails
        filterClientRows(qLower);
    });
}

function filterClientRows(qLower) {
    const rows = document.querySelectorAll('#qeTableBody tr[data-id]');
    let visibleCount = 0;
    rows.forEach(row => {
        const name = row.querySelector('.field-name').value.toLowerCase();
        const barcode = row.querySelector('.field-barcode').value.toLowerCase();
        if (name.includes(qLower) || barcode.includes(qLower)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    document.getElementById('qeRowCount').textContent = visibleCount;
}

function renderSearchResults(products, query) {
    const tbody = document.getElementById('qeTableBody');
    document.getElementById('qeRowCount').textContent = products.length;

    if (products.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="padding: 3rem; text-align: center; color: #64748b;">
                    <div style="font-size: 1.05rem; font-weight: 700; color: #1e293b;">No matching products found</div>
                    <div style="font-size: 0.82rem; margin-top: 4px;">No products match "${escapeHtml(query)}"</div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    products.forEach(p => {
        const isDirty = modifiedRows.has(parseInt(p.id));
        html += `
            <tr id="row_${p.id}" data-id="${p.id}" class="${isDirty ? 'row-dirty' : ''}">
                <td>
                    <span style="font-weight: 800; color: #475569;">#${p.id}</span>
                    ${p.sku && p.sku !== p.barcode ? `<div style="font-size: 0.7rem; color: #94a3b8; font-family: monospace;">${escapeHtml(p.sku)}</div>` : ''}
                </td>
                <td>
                    <input type="text" class="qe-input field-name" value="${escapeHtml(p.name)}" 
                           data-original="${escapeHtml(p.name)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="text" class="qe-input field-barcode" style="font-family: monospace;" value="${escapeHtml(p.barcode)}" 
                           data-original="${escapeHtml(p.barcode)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-cost" value="${parseFloat(p.cost || 0).toFixed(2)}" 
                           data-original="${parseFloat(p.cost || 0).toFixed(2)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-price" value="${parseFloat(p.selling_price || 0).toFixed(2)}" 
                           data-original="${parseFloat(p.selling_price || 0).toFixed(2)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="number" step="1" class="qe-input qe-input-num qe-input-stock field-stock" value="${parseFloat(p.stock || 0)}" 
                           data-original="${parseFloat(p.stock || 0)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td style="text-align: center;">
                    <button type="button" class="qe-btn-update" id="btn_${p.id}" onclick="saveProduct(${p.id})">
                        Update
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function showToast(msg, isError = false) {
    const toast = document.getElementById('qeToast');
    const icon = document.getElementById('qeToastIcon');
    const text = document.getElementById('qeToastMsg');

    text.textContent = msg;
    if (isError) {
        toast.style.background = '#dc2626';
        icon.textContent = '✕';
    } else {
        toast.style.background = '#0f172a';
        icon.textContent = '✓';
    }

    toast.style.display = 'flex';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 2800);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
