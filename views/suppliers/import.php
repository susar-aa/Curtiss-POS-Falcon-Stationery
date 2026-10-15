<?php
/**
 * Curtiss POS - Supplier Import View
 * Modernized Floating Glassmorphism Theme (Matches Product Import)
 */
?>

<!-- Floating Top-Right Action Pill -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="supplier_import?action=template" class="btn btn-secondary" style="border-radius: 9999px; font-weight: 500; font-size: 0.85rem; padding: 0.45rem 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); background: #ffffff; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download CSV Template
    </a>
    <a href="suppliers" class="btn btn-secondary" style="border-radius: 9999px; font-weight: 500; font-size: 0.85rem; padding: 0.45rem 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); background: #ffffff; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to Supplier Master
    </a>
</div>

<style>
.imp-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 18px;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
    padding: 28px 32px;
    margin-bottom: 24px;
}

.imp-step-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 20px;
}

.imp-step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #0f172a;
    color: #ffffff;
    font-size: 0.8rem;
    font-weight: 700;
}

.imp-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin: 18px 0;
}

.imp-stat-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    text-align: center;
}

.imp-stat-val {
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}

.imp-stat-lbl {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    margin-top: 4px;
    letter-spacing: 0.03em;
}

/* Modal Styles */
.imp-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(8px);
    z-index: 9600;
    transition: opacity 0.2s ease;
}
.imp-modal-backdrop.active { display: block; }
.imp-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.96);
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 20px 50px -10px rgba(0,0,0,0.15);
    border-radius: 20px;
    width: 90%;
    max-width: 420px;
    padding: 28px;
    z-index: 9601;
    transition: all 0.2s ease;
}
.imp-modal.active { display: block; transform: translate(-50%, -50%) scale(1); }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.imp-spinner { animation: spin 1s linear infinite; }
</style>

<div style="width: 100%; max-width: 100%; padding-bottom: 30px;">
    <!-- Page Header -->
    <div style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
            <div style="width: 10px; height: 10px; border-radius: 50%; background: #0f172a;"></div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em;">Import Supplier Master Catalog</h1>
        </div>
        <p style="font-size: 0.875rem; color: #64748b; margin: 0 0 0 20px;">Batch create or update suppliers via CSV master file with stable Supplier Code preservation</p>
    </div>

    <!-- Alert Notices -->
    <?php if (!empty($successMsg)): ?>
        <div style="background: rgba(240, 253, 244, 0.95); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #166534; font-size: 0.88rem; font-weight: 600; backdrop-filter: blur(8px); display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= View::escape($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div style="background: rgba(254, 242, 242, 0.95); border: 1px solid #fca5a5; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #991b1b; font-size: 0.88rem; font-weight: 600; backdrop-filter: blur(8px); display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <?php if (!$previewData): ?>
        <!-- STEP 1: Upload File & Mode Selection -->
        <div class="imp-card" style="max-width: 800px; margin: 0 auto;">
            <div class="imp-step-title">
                <span class="imp-step-num">1</span>
                Upload Supplier Master CSV File & Options
            </div>

            <form method="POST" action="supplier_import" enctype="multipart/form-data" onsubmit="runImportProgress(event, 'Validating CSV File...')">
                <input type="hidden" name="action" value="preview">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 6px;">Select Supplier CSV File <span style="color: #ef4444;">*</span></label>
                        <input type="file" name="csv_file" accept=".csv" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 6px 12px; font-size: 0.88rem; background: #fff;" required>
                        <span style="font-size: 0.78rem; color: #64748b; margin-top: 4px; display: block;">Expected headers: <code>Supplier Code, Supplier Name, Phone, WhatsApp, Address, Status</code></span>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 6px;">Import Mode <span style="color: #ef4444;">*</span></label>
                        <select name="mode" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 0.88rem; background: #fff;">
                            <option value="create_and_update">Create New & Update Existing (Default)</option>
                            <option value="update_existing_only">Update Existing Only (Skip New)</option>
                            <option value="create_new_only">Create New Only (Skip Existing)</option>
                        </select>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="ignore_blank" value="1" checked style="margin-top: 3px; accent-color: #0f172a;">
                        <div>
                            <strong style="font-size: 0.88rem; color: #0f172a;">Ignore blank cells when updating existing suppliers</strong>
                            <p style="font-size: 0.78rem; color: #64748b; margin: 2px 0 0 0;">Default ON. Prevents erasing existing Phone, WhatsApp, or Address values if spreadsheet cell is left blank.</p>
                        </div>
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" style="border-radius: 9999px; font-weight: 600; font-size: 0.88rem; padding: 11px 28px; background: #0f172a; border: none; color: #ffffff; cursor: pointer; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2); transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;">
                        Preview & Validate CSV File →
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- STEP 2: Validation Summary & Parsed Table -->
        <div class="imp-card">
            <div class="imp-step-title">
                <span class="imp-step-num">2</span>
                Supplier Import Preview & Validation Summary
            </div>

            <div class="imp-stat-grid">
                <div class="imp-stat-card">
                    <div class="imp-stat-val"><?= $previewData['summary']['total'] ?></div>
                    <div class="imp-stat-lbl">Total Rows</div>
                </div>
                <div class="imp-stat-card">
                    <div class="imp-stat-val" style="color: #16a34a;"><?= $previewData['summary']['will_create'] ?></div>
                    <div class="imp-stat-lbl">Will Create</div>
                </div>
                <div class="imp-stat-card">
                    <div class="imp-stat-val" style="color: #2563eb;"><?= $previewData['summary']['will_update'] ?></div>
                    <div class="imp-stat-lbl">Will Update</div>
                </div>
                <div class="imp-stat-card">
                    <div class="imp-stat-val" style="color: #64748b;"><?= $previewData['summary']['skipped'] ?></div>
                    <div class="imp-stat-lbl">Skipped</div>
                </div>
                <div class="imp-stat-card">
                    <div class="imp-stat-val" style="color: #dc2626;"><?= $previewData['summary']['invalid'] ?></div>
                    <div class="imp-stat-lbl">Invalid</div>
                </div>
            </div>

            <form method="POST" action="supplier_import" onsubmit="runImportProgress(event, 'Committing Supplier Import Batch...')">
                <input type="hidden" name="action" value="commit">
                <input type="hidden" name="temp_file" value="<?= View::escape($previewData['temp_file']) ?>">
                <input type="hidden" name="mode" value="<?= View::escape($previewData['mode']) ?>">
                <input type="hidden" name="ignore_blank" value="<?= $previewData['ignore_blank_cells'] ? '1' : '0' ?>">

                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                    <a href="supplier_import" style="border-radius: 9999px; font-weight: 600; font-size: 0.88rem; padding: 10px 22px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; text-decoration: none;">
                        Cancel
                    </a>
                    <button type="submit" style="border-radius: 9999px; font-weight: 600; font-size: 0.88rem; padding: 10px 30px; background: #0f172a; border: none; color: #ffffff; cursor: pointer; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2);" <?= ($previewData['summary']['will_create'] + $previewData['summary']['will_update']) === 0 ? 'disabled' : '' ?>>
                        Confirm & Commit Supplier Import
                    </button>
                </div>
            </form>
        </div>

        <!-- Parsed Rows Preview Table -->
        <div class="imp-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: #0f172a; font-size: 0.95rem;">
                Parsed CSV Row Details
            </div>
            <div style="overflow-x: auto;">
                <table class="prod-table" style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b;">Row</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b;">Code</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b;">Supplier Name</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b;">Phone</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b;">Status</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b;">Action</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b;">Diagnostic Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previewData['rows'] as $r): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 14px; font-size: 0.85rem; font-weight: 700; color: #64748b;">#<?= $r['row_index'] ?></td>
                                <td style="padding: 12px 14px; font-size: 0.85rem;"><span style="font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?= View::escape($r['supplier_code'] ?: '(New)') ?></span></td>
                                <td style="padding: 12px 14px; font-size: 0.88rem; font-weight: 700; color: #0f172a;"><?= View::escape($r['name']) ?></td>
                                <td style="padding: 12px 14px; font-size: 0.85rem; color: #475569;"><?= View::escape($r['phone'] ?: '-') ?></td>
                                <td style="padding: 12px 14px; font-size: 0.85rem; color: #475569;"><?= View::escape($r['status']) ?></td>
                                <td style="padding: 12px 14px; font-size: 0.85rem;">
                                    <?php if ($r['preview_action'] === 'create'): ?>
                                        <span style="background: #dcfce7; color: #15803d; border-radius: 9999px; padding: 3px 10px; font-size: 0.75rem; font-weight: 700;">Create</span>
                                    <?php elseif ($r['preview_action'] === 'update'): ?>
                                        <span style="background: #dbeafe; color: #1d4ed8; border-radius: 9999px; padding: 3px 10px; font-size: 0.75rem; font-weight: 700;">Update</span>
                                    <?php else: ?>
                                        <span style="background: #f1f5f9; color: #64748b; border-radius: 9999px; padding: 3px 10px; font-size: 0.75rem; font-weight: 700;">Skip</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.82rem; color: #64748b;"><?= View::escape($r['message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Import Progress Modal Popup -->
<div id="impBackdrop" class="imp-modal-backdrop"></div>
<div id="impModal" class="imp-modal">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
        <div style="width: 44px; height: 44px; border-radius: 14px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5" class="imp-spinner"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        </div>
        <div>
            <h3 id="impTitle" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Processing Import</h3>
            <div id="impSubtitle" style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Parsing & validating supplier records...</div>
        </div>
    </div>
    <div style="margin-bottom: 12px;">
        <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
            <div id="impProgressBar" style="width: 0%; height: 100%; background: #0f172a; border-radius: 999px; transition: width 0.18s ease;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 700; color: #475569; margin-top: 6px;">
            <span>Progress</span>
            <span id="impStatusText">0%</span>
        </div>
    </div>
</div>

<script>
function runImportProgress(event, titleText) {
    const backdrop = document.getElementById('impBackdrop');
    const modal = document.getElementById('impModal');
    const bar = document.getElementById('impProgressBar');
    const statusText = document.getElementById('impStatusText');
    const title = document.getElementById('impTitle');

    if (titleText) title.textContent = titleText;
    backdrop.classList.add('active');
    modal.classList.add('active');

    let currentProgress = 0;
    const interval = setInterval(() => {
        if (currentProgress < 90) {
            currentProgress += Math.floor(Math.random() * 12) + 8;
            if (currentProgress > 90) currentProgress = 90;
            bar.style.width = currentProgress + '%';
            statusText.textContent = currentProgress + '%';
        } else {
            clearInterval(interval);
        }
    }, 120);
}
</script>
