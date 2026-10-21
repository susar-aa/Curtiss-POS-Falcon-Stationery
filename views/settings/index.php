<?php
/**
 * Falcon POS - System Settings View
 * Includes General Business Details and Security Controls
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">System Configuration</h1>
        <p class="page-subtitle">Central settings repository architecture and payment gateway parameters</p>
    </div>
</div>

<?php if (!empty($successMsg)): ?>
    <div class="alert alert-success">
        <span><?= View::escape($successMsg) ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem;">
        <span><?= View::escape($errorMsg) ?></span>
    </div>
<?php endif; ?>

<form method="POST" action="settings.php" enctype="multipart/form-data" onsubmit="handleFormSubmit(this)">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
        
        <!-- Left Column: Business & Security Settings -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- General Business Settings -->
            <div class="card">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem;">General Business Details</h3>
                
                <!-- Company Logo Upload -->
                <div class="form-group" style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px dashed #e2e8f0;">
                    <label class="form-label" style="font-weight: 700;">Company Brand Logo (Receipts, Invoices & Prints)</label>
                    <div style="display: flex; align-items: center; gap: 1.25rem; margin-top: 0.5rem; flex-wrap: wrap;">
                        <?php $currentLogo = Settings::get('company_logo', ''); ?>
                        <div style="width: 86px; height: 86px; border: 2px dashed #cbd5e1; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden; position: relative; flex-shrink: 0;">
                            <?php if (!empty($currentLogo) && file_exists(__DIR__ . '/../../' . $currentLogo)): ?>
                                <img src="<?= View::escape($currentLogo) ?>" id="logoPreview" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <span id="logoPreviewPlaceholder" style="font-size: 0.72rem; color: #94a3b8; font-weight: 700; text-align: center; padding: 4px; display: none;">No Logo</span>
                            <?php else: ?>
                                <span id="logoPreviewPlaceholder" style="font-size: 0.72rem; color: #94a3b8; font-weight: 700; text-align: center; padding: 4px;">No Logo</span>
                                <img src="" id="logoPreview" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; min-width: 220px;">
                            <input type="file" name="company_logo" id="companyLogoInput" accept="image/png, image/jpeg, image/jpg, image/webp, image/svg+xml" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.85rem;" onchange="previewCompanyLogo(this)">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 0.35rem; font-size: 0.78rem;">
                                Recommended: Transparent PNG, JPG, or SVG (Max 4MB). Auto-scaled for thermal receipts (88mm) and A4 documents.
                            </small>
                            <?php if (!empty($currentLogo)): ?>
                                <div style="margin-top: 0.5rem;">
                                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #dc2626; cursor: pointer; font-weight: 600;">
                                        <input type="checkbox" name="remove_logo" value="1">
                                        <i class="fa-solid fa-trash-can"></i> Remove Current Logo
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Business Name (Brand) <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="settings[business_name]" class="form-control" value="<?= View::escape(Settings::get('business_name', 'Falcon Stationery')) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Currency Symbol <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="settings[currency_symbol]" class="form-control" value="<?= View::escape(Settings::get('currency_symbol', 'LKR')) ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-phone me-1" style="color: #0284c7;"></i> Business Phone (Hotline)
                        </label>
                        <input type="text" name="settings[business_phone]" class="form-control" placeholder="+94 81 222 3344" value="<?= View::escape(Settings::get('business_phone', '+94 81 222 3344')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-brands fa-whatsapp me-1" style="color: #16a34a;"></i> WhatsApp Number
                        </label>
                        <input type="text" name="settings[business_whatsapp]" class="form-control" placeholder="+94 77 123 4567" value="<?= View::escape(Settings::get('business_whatsapp', '+94 77 123 4567')) ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-envelope me-1" style="color: #64748b;"></i> Business Email
                        </label>
                        <input type="email" name="settings[business_email]" class="form-control" placeholder="info@falconstationery.lk" value="<?= View::escape(Settings::get('business_email', 'info@falconstationery.lk')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-globe me-1" style="color: #6366f1;"></i> Website URL
                        </label>
                        <input type="text" name="settings[business_website]" class="form-control" placeholder="www.falconstationery.lk" value="<?= View::escape(Settings::get('business_website', 'www.falconstationery.lk')) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-location-dot me-1" style="color: #ef4444;"></i> Physical Business Address
                    </label>
                    <textarea name="settings[business_address]" class="form-control" rows="2" placeholder="e.g. Falcon Stationery, Main Street"><?= View::escape(Settings::get('business_address', 'Falcon Stationery')) ?></textarea>
                    <small style="color: var(--color-text-secondary); display: block; margin-top: 0.25rem;">
                        Printed on receipts, invoices, day-closing sheets, and official company documentation.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-id-card me-1" style="color: #8b5cf6;"></i> Business Registration / Tax / VAT Number
                    </label>
                    <input type="text" name="settings[business_reg_no]" class="form-control" placeholder="e.g. PV-123456 / VAT123456789" value="<?= View::escape(Settings::get('business_reg_no', '')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-comment-dots me-1" style="color: #f59e0b;"></i> Customer Receipt Footer Note (Return Policy / Greetings)
                    </label>
                    <input type="text" name="settings[receipt_footer_note]" class="form-control" placeholder="e.g. Thank You For Shopping With Us! Exchanges accepted within 7 days." value="<?= View::escape(Settings::get('receipt_footer_note', 'Thank You For Shopping With Us!')) ?>">
                </div>
            </div>

            <!-- Security & Visibility Settings -->
            <div class="card">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem;">Security & Visibility Controls</h3>
                
                <div class="form-group">
                    <label class="form-label">
                        Special User Financial Display Percentage (%)
                    </label>
                    <input type="number" step="0.01" name="settings[special_user_display_percentage]" class="form-control" min="0" max="100" value="<?= View::escape(Settings::get('special_user_display_percentage', '60.00')) ?>" <?= Auth::hasRole(ROLE_SUPER_ADMIN) ? '' : 'disabled' ?> required>
                    <small style="color: var(--color-text-secondary); display: block; margin-top: 0.25rem;">
                        <?= Auth::hasRole(ROLE_SUPER_ADMIN) ? 'Configures presentation layer display percentage for Special User role without modifying database values.' : 'Only Super Admin can update the Special User display percentage.' ?>
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Session Inactivity Timeout (Seconds)</label>
                    <input type="number" name="settings[session_inactivity_timeout]" class="form-control" min="60" max="86400" value="<?= View::escape(Settings::get('session_inactivity_timeout', '1800')) ?>" required>
                    <small style="color: var(--color-text-secondary); display: block; margin-top: 0.25rem;">
                        Time in seconds before inactive sessions automatically log out (Default 1800 = 30 mins).
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Default Register Opening Cash</label>
                    <input type="text" name="settings[default_opening_cash]" class="form-control" value="<?= View::escape(Settings::get('default_opening_cash', '5000.00')) ?>">
                </div>
            </div>

            <!-- Customer Loyalty Configuration -->
            <div class="card" style="border-left: 4px solid #8b5cf6;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 1.25rem;">
                    <span style="background: #8b5cf6; color: #fff; font-size: 0.75rem; font-weight: 900; padding: 2px 8px; border-radius: 6px;">LOYALTY</span>
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0;">Customer Loyalty Configuration</h3>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Default Loyalty Discount Percentage (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="settings[loyalty_discount_percentage]" class="form-control" value="<?= View::escape(Settings::get('loyalty_discount_percentage', '5.00')) ?>" required>
                    <small style="color: var(--color-text-secondary); display: block; margin-top: 0.25rem;">
                        Default bill discount % offered at checkout when a loyalty member is selected on the POS screen.
                    </small>
                </div>
            </div>
        </div>

        <!-- Right Column: Hardware & System Integrations -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">

            <!-- TSC TE244 Thermal Label Printer Configuration -->
            <div class="card" id="barcode_printer_settings" style="border-left: 4px solid #0f172a;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.85rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #0f172a; color: #fff; font-size: 0.75rem; font-weight: 900; padding: 2px 8px; border-radius: 6px; letter-spacing: 0.5px;">HARDWARE</span>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0;">TSC TE244 Label Printer &amp; Layout</h3>
                        </div>
                        <small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 2px;">
                            Thermal transfer label dimensions, continuous web layout, and element visibility
                        </small>
                    </div>
                    <div>
                        <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 800; font-size: 0.75rem; padding: 4px 10px; border-radius: 999px;">
                            25&times;40mm &bull; 2 per row
                        </span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Thermal Printer Model</label>
                        <input type="text" name="settings[label_printer_model]" class="form-control" value="<?= View::escape(Settings::get('label_printer_model', 'TSC TE244')) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Shop Name on Label</label>
                        <input type="text" name="settings[label_shop_name_custom]" class="form-control" value="<?= View::escape(Settings::get('label_shop_name_custom', Settings::get('business_name', 'Falcon Stationery'))) ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Label Width (mm)</label>
                        <input type="number" step="0.5" min="10" max="100" name="settings[label_width_mm]" class="form-control" value="<?= View::escape(Settings::get('label_width_mm', '25')) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Label Height (mm)</label>
                        <input type="number" step="0.5" min="10" max="100" name="settings[label_height_mm]" class="form-control" value="<?= View::escape(Settings::get('label_height_mm', '40')) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Labels Per Row</label>
                        <input type="number" step="1" min="1" max="4" name="settings[labels_per_row]" class="form-control" value="<?= View::escape(Settings::get('labels_per_row', '2')) ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Column Gap (mm)</label>
                        <input type="number" step="0.5" min="0" max="20" name="settings[label_column_gap_mm]" class="form-control" value="<?= View::escape(Settings::get('label_column_gap_mm', '2')) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Row Gap (mm)</label>
                        <input type="number" step="0.5" min="0" max="20" name="settings[label_row_gap_mm]" class="form-control" value="<?= View::escape(Settings::get('label_row_gap_mm', '2')) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Currency Prefix</label>
                        <input type="text" name="settings[label_price_prefix]" class="form-control" value="<?= View::escape(Settings::get('label_price_prefix', 'Rs.')) ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Barcode Height (mm)</label>
                        <input type="number" step="1" min="6" max="30" name="settings[label_barcode_height_mm]" class="form-control" value="<?= View::escape(Settings::get('label_barcode_height_mm', '13')) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Print Orientation</label>
                        <select name="settings[label_rotation_deg]" class="form-control" style="font-weight: 700;">
                            <option value="0" <?= Settings::get('label_rotation_deg', '0') === '0' ? 'selected' : '' ?>>Normal (0&deg; Feed Direction)</option>
                            <option value="180" <?= Settings::get('label_rotation_deg', '0') === '180' ? 'selected' : '' ?>>Inverted (180&deg; Reverse)</option>
                        </select>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-top: 6px;">
                    <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #475569; margin-bottom: 8px;">Print Elements Visibility</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                        <div>
                            <label class="form-label" style="font-size: 11px;">Shop Header</label>
                            <select name="settings[label_show_shop_name]" class="form-control" style="font-weight: 700;">
                                <option value="1" <?= Settings::get('label_show_shop_name', '1') === '1' ? 'selected' : '' ?>>Show</option>
                                <option value="0" <?= Settings::get('label_show_shop_name', '1') === '0' ? 'selected' : '' ?>>Hide</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 11px;">Product Code</label>
                            <select name="settings[label_show_product_code]" class="form-control" style="font-weight: 700;">
                                <option value="1" <?= Settings::get('label_show_product_code', '1') === '1' ? 'selected' : '' ?>>Show</option>
                                <option value="0" <?= Settings::get('label_show_product_code', '1') === '0' ? 'selected' : '' ?>>Hide</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 11px;">Selling Price</label>
                            <select name="settings[label_show_price]" class="form-control" style="font-weight: 700;">
                                <option value="1" <?= Settings::get('label_show_price', '1') === '1' ? 'selected' : '' ?>>Show</option>
                                <option value="0" <?= Settings::get('label_show_price', '1') === '0' ? 'selected' : '' ?>>Hide</option>
                            </select>
                        </div>
                    </div>
                </div>
                <small style="color: #64748b; font-size: 0.78rem; display: block; margin-top: 0.65rem;">
                    Calibrated for TSC TE244 direct thermal transfer continuous label roll (2 across, 25&times;40mm each). In browser print dialog, select <strong>Margins: None</strong> and <strong>Scale: 100%</strong>.
                </small>
            </div>

            <!-- Global Submit Button -->
            <div class="card" style="text-align: right; padding: 1.25rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.8rem; font-size: 0.95rem; font-weight: 700;">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save All Settings Configuration
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function previewCompanyLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logoPreview');
            const placeholder = document.getElementById('logoPreviewPlaceholder');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }
}


function handleFormSubmit(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Saving...';
    }
}
</script>
