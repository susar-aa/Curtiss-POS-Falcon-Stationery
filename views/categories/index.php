<?php
/**
 * Curtiss POS - Categories View (Product Master Inventory Theme Alignment with Pagination & Trash Bin)
 */
$isDeletedView = ($viewMode === 'deleted');
$activeCount = count($categories ?? []);
$deletedCount = count($deletedCategories ?? []);
$displayList = $isDeletedView ? $deletedCategories : $categories;
?>

<!-- Floating Top-Right Action Controls (Top Header Bar Top Right Corner) -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if ($isDeletedView): ?>
        <a href="categories" class="prod-btn prod-btn-ghost" style="text-decoration: none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Active Categories (<?= $activeCount ?>)
        </a>
    <?php else: ?>
        <a href="categories?view=deleted" class="prod-btn prod-btn-ghost" style="text-decoration: none; color: #dc2626; background: #fef2f2; border-color: #fca5a5;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Trash Bin (<?= $deletedCount ?>)
        </a>
        <button type="button" class="prod-btn prod-btn-primary" onclick="openCreateCategoryModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Add New Category
        </button>
    <?php endif; ?>
</div>

<style>
/* ── Inventory Shared Theme Styles ── */
.prod-header {
    margin-bottom: 0.85rem;
}
.prod-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
}

.prod-btn {
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
.prod-btn-ghost {
    background: rgba(255,255,255,0.9);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    backdrop-filter: blur(12px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.prod-btn-ghost:hover {
    background: #ffffff;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}
.prod-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 2px 12px rgba(15,23,42,0.2);
}
.prod-btn-primary:hover {
    background: #1e293b;
    box-shadow: 0 4px 16px rgba(15,23,42,0.3);
    transform: translateY(-1px);
}

/* Alert Notices */
.prod-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 12.5px;
    font-weight: 600;
    margin-bottom: 14px;
    animation: slideDown 0.2s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.prod-alert-success { background: rgba(240,253,244,0.95); border: 1px solid rgba(22,163,74,0.25); color: #16a34a; }
.prod-alert-danger  { background: rgba(254,242,242,0.95); border: 1px solid rgba(220,38,38,0.25); color: #dc2626; }

/* Main Inventory Table Panel & Fixed Viewport System */
.prod-table-panel {
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226,232,240,0.85);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}
.prod-table-scroll { 
    overflow-x: auto;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    position: relative;
}
.prod-table {
    width: 100%;
    border-collapse: collapse;
}
.prod-table thead { 
    background: #f8fafc; 
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
}
.prod-table thead th {
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    white-space: nowrap;
    background: #f8fafc;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    text-align: left;
}
.prod-table thead th.col-right,
.prod-table td.col-right { text-align: right; }
.prod-table thead th.col-center,
.prod-table td.col-center { text-align: center; }

.prod-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
}
.prod-table tbody tr:nth-child(even) {
    background: rgba(248, 250, 252, 0.75);
}
.prod-table tbody tr:nth-child(odd) {
    background: #ffffff;
}
.prod-table tbody tr:hover {
    background: #f1f5f9 !important;
}
.prod-table td {
    padding: 8px 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
}
.cat-name-cell {
    font-weight: 700;
    color: #0f172a;
    font-size: 13px;
}

/* Status Badges Interactive Trigger */
.prod-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.2;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
}
.prod-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.prod-badge-active   { background: #dcfce7; color: #15803d; border: 1px solid rgba(22,163,74,0.3); }
.prod-badge-inactive { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220,38,38,0.3); }
.prod-badge .dot {
    width: 6px; height: 6px; border-radius: 50%; display: inline-block;
}
.prod-badge-active   .dot { background: #16a34a; }
.prod-badge-inactive .dot { background: #dc2626; }

/* Action Buttons */
.prod-act-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    text-decoration: none;
}
.prod-act-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.prod-act-edit {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
}
.prod-act-edit:hover:not(:disabled) {
    background: #e2e8f0;
    color: #0f172a;
}
.prod-act-delete {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #dc2626;
}
.prod-act-delete:hover:not(:disabled) {
    background: #fee2e2;
    color: #991b1b;
}
.prod-act-restore {
    background: #f0fdf4;
    border-color: #86efac;
    color: #16a34a;
}
.prod-act-restore:hover:not(:disabled) {
    background: #dcfce7;
    color: #15803d;
}

/* Glassmorphism Modal */
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
</style>

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header (Headline Only) -->
    <div class="prod-header" style="flex-shrink: 0;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="prod-title"><?= $isDeletedView ? 'Deleted Categories Trash Bin' : 'Category Master' ?></h1>
        </div>
    </div>

    <!-- Alert Notices -->
    <?php if (!empty($successMsg)): ?>
        <div class="prod-alert prod-alert-success" style="flex-shrink: 0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= View::escape($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="prod-alert prod-alert-danger" style="flex-shrink: 0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Main Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="catTable">
                <thead>
                    <tr>
                        <th style="width: 90px; text-align: left;">ID</th>
                        <th style="text-align: left;">Category Name</th>
                        <th style="width: 140px; text-align: left;">Status</th>
                        <th style="width: 160px; text-align: left;"><?= $isDeletedView ? 'Deleted Date' : 'Created Date' ?></th>
                        <th class="col-right" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($displayList)): ?>
                        <tr class="prod-empty"><td colspan="5" style="text-align: center; color: #94a3b8; padding: 32px 16px;"><?= $isDeletedView ? 'No soft-deleted categories in Trash Bin.' : 'No categories found.' ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($displayList as $c): ?>
                            <tr <?= !$isDeletedView ? "onclick='openEditCategoryModal(" . json_encode($c) . ")' style='cursor: pointer;'" : "" ?>>
                                <td style="text-align: left;"><strong style="color: #64748b; font-size: 11.5px;">#<?= (int)$c['id'] ?></strong></td>
                                <td style="text-align: left;" class="cat-name-cell"><?= View::escape($c['name']) ?></td>
                                <td style="text-align: left;">
                                    <?php if ($isDeletedView): ?>
                                        <span class="prod-badge prod-badge-inactive"><span class="dot"></span>Deleted</span>
                                    <?php else: ?>
                                        <form method="POST" action="categories" id="toggleForm_<?= $c['id'] ?>" style="display: inline; margin: 0;" onsubmit="handleFormSubmit(this)">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <span class="prod-badge <?= $c['status'] === 'active' ? 'prod-badge-active' : 'prod-badge-inactive' ?>" 
                                                  onclick="event.stopPropagation(); document.getElementById('toggleForm_<?= $c['id'] ?>').submit();"
                                                  title="Click to toggle status (Currently <?= $c['status'] === 'active' ? 'Active' : 'Deactive' ?>)">
                                                <span class="dot"></span><?= $c['status'] === 'active' ? 'Active' : 'Deactive' ?>
                                            </span>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: left; color: #64748b; font-size: 12px; font-weight: 500;"><?= View::formatDate($isDeletedView ? $c['deleted_at'] : $c['created_at'], 'Y-m-d') ?></td>
                                <td class="col-right" onclick="event.stopPropagation();">
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <?php if ($isDeletedView): ?>
                                            <form method="POST" action="categories" onsubmit="handleFormSubmit(this)" style="display: inline; margin: 0;">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="prod-act-btn prod-act-restore">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                                    Restore
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="prod-act-btn prod-act-edit" onclick='openEditCategoryModal(<?= json_encode($c) ?>)'>
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit
                                            </button>
                                            <form method="POST" action="categories" onsubmit="return confirm('Move category \'<?= View::escape($c['name']) ?>\' to Trash Bin?') && handleFormSubmit(this);" style="display: inline; margin: 0;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="prod-act-btn prod-act-delete">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="prod-table-footer" style="padding: 10px 16px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; flex-shrink: 0;">
            <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="paginationInfo">
                Showing <span id="pagStart">1</span> to <span id="pagEnd">25</span> of <span id="pagTotal">0</span> entries
            </div>

            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                    <span>Rows:</span>
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

<!-- Modal for Create/Edit Category -->
<div id="catModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 id="catModalTitle" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Add New Category</h3>
            </div>
            <button type="button" onclick="closeCategoryModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; transition: color 0.15s ease;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form method="POST" action="categories" onsubmit="handleFormSubmit(this)">
            <input type="hidden" name="action" id="catAction" value="create">
            <input type="hidden" name="id" id="catId" value="">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em;">Category Name <span style="color: #ef4444;">*</span></label>
                <input type="text" name="name" id="catName" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box; transition: all 0.15s ease;" placeholder="e.g. Ball Pen" required>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="prod-btn prod-btn-ghost" onclick="closeCategoryModal()">Cancel</button>
                <button type="submit" class="prod-btn prod-btn-primary" id="btnSubmitCat">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function handleFormSubmit(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'wait';
    }
}

function openCreateCategoryModal() {
    document.getElementById('catModalTitle').innerText = 'Add New Category';
    document.getElementById('catAction').value = 'create';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catModal').style.display = 'flex';
    setTimeout(() => document.getElementById('catName')?.focus(), 50);
}

function openEditCategoryModal(c) {
    document.getElementById('catModalTitle').innerText = 'Edit Category';
    document.getElementById('catAction').value = 'update';
    document.getElementById('catId').value = c.id;
    document.getElementById('catName').value = c.name;
    document.getElementById('catModal').style.display = 'flex';
    setTimeout(() => document.getElementById('catName')?.focus(), 50);
}

function closeCategoryModal() {
    document.getElementById('catModal').style.display = 'none';
}

/* ── Keyboard Enter Navigation System ── */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.matches('input:not([type="hidden"]), select, textarea')) {
        const form = e.target.form;
        if (!form) return;

        const visibleInputs = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])'))
            .filter(el => el.offsetWidth > 0 || el.offsetHeight > 0 || el.parentElement?.style.display !== 'none');

        const index = visibleInputs.indexOf(e.target);
        if (index > -1 && index < visibleInputs.length - 1) {
            e.preventDefault();
            const nextEl = visibleInputs[index + 1];
            nextEl.focus();
            if (typeof nextEl.select === 'function') {
                nextEl.select();
            }
        }
    }
});

/* ── Client-Side Dynamic Table Pagination Controller ── */
let currentPage = 1;
let pageSize = 25;

function updatePagination() {
    const table = document.getElementById('catTable');
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
    prevBtn.className = 'prod-act-btn prod-act-edit';
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
        pBtn.className = 'prod-act-btn ' + (p === currentPage ? 'prod-act-primary' : 'prod-act-edit');
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
    nextBtn.className = 'prod-act-btn prod-act-edit';
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

/* ── Realtime Search in Floating Bottom Nav Bar ── */
function registerBottomSearch() {
    if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search categories...',
            onSearch: function(query) {
                filterCategoriesRealtime(query);
            }
        });
    } else {
        setTimeout(registerBottomSearch, 40);
    }
}
registerBottomSearch();

function filterCategoriesRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#catTable tbody tr');
    rows.forEach(tr => {
        if (tr.classList.contains('prod-empty')) return;
        const searchableCells = Array.from(tr.querySelectorAll('td:not(.col-right)'));
        const text = searchableCells.map(td => td.textContent).join(' ').toLowerCase();
        if (!q || text.includes(q)) {
            tr.dataset.searchMatched = 'true';
        } else {
            tr.dataset.searchMatched = 'false';
        }
    });
    currentPage = 1;
    updatePagination();
}
</script>
