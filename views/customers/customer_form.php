<?php
/**
 * Curtiss POS - Customer Create / Edit Form View (Stage 5)
 */
$isEdit = ($mode === 'edit');
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Edit Customer Account' : 'Create New Customer' ?></h1>
        <p class="page-subtitle"><?= $isEdit ? 'Update details for customer code ' . View::escape($formData['customer_code'] ?? '') : 'Register a new customer account and assign barcode cards' ?></p>
    </div>
    <div>
        <a href="customers.php" class="btn btn-secondary">Back to Customers</a>
    </div>
</div>

<?php if (!empty($errorMsg)): ?>
    <div class="alert alert-danger"><span><?= View::escape($errorMsg) ?></span></div>
<?php endif; ?>

<div class="card" style="max-width: 750px;">
    <form method="POST" action="<?= $isEdit ? "customer_edit.php?id={$id}" : "customer_create.php" ?>">
        <div class="form-group">
            <label class="form-label">Full Customer Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Perera Electronics & Stationary" value="<?= View::escape($formData['name'] ?? '') ?>" required autofocus>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="e.g. +94 77 123 4567" value="<?= View::escape($formData['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">WhatsApp Number</label>
                <input type="text" name="whatsapp" class="form-control" placeholder="e.g. +94 77 123 4567" value="<?= View::escape($formData['whatsapp'] ?? '') ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Customer Card / Scanner Barcode (Optional)</label>
                <input type="text" name="card_barcode" class="form-control" placeholder="Scan or type customer card barcode..." value="<?= View::escape($formData['card_barcode'] ?? '') ?>">
                <small style="color: var(--color-text-muted); display: block; margin-top: 0.25rem;">Must be unique across active customer cards if used.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Credit Limit (LKR)</label>
                <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" placeholder="0.00" value="<?= View::escape($formData['credit_limit'] ?? '0.00') ?>">
                <small style="color: var(--color-text-muted); display: block; margin-top: 0.25rem;">Maximum allowed customer credit balance foundation.</small>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3" placeholder="Customer mailing address or location details"><?= View::escape($formData['address'] ?? '') ?></textarea>
        </div>

        <!-- Loyalty Program Setting -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #0f172a; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="is_loyalty" value="1" id="is_loyalty_checkbox" <?= !empty($formData['is_loyalty']) ? 'checked' : '' ?> onchange="document.getElementById('loyalty_override_wrap').style.display = this.checked ? 'block' : 'none';" style="width: 18px; height: 18px; accent-color: #7c3aed;">
                    <span>★ Mark as Loyalty Customer (Eligible for Bill Discount)</span>
                </label>
            </div>
            <div id="loyalty_override_wrap" style="display: <?= !empty($formData['is_loyalty']) ? 'block' : 'none' ?>; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1;">
                <label class="form-label" style="font-size: 0.8rem; font-weight: 700; color: #475569;">
                    Custom Loyalty Discount % (Optional Override)
                </label>
                <input type="number" step="0.01" min="0" max="100" name="loyalty_discount_override" class="form-control" placeholder="Leave empty to use company default (<?= View::escape(Settings::get('loyalty_discount_percentage', '5.00')) ?>%)" value="<?= View::escape($formData['loyalty_discount_override'] ?? '') ?>">
                <small style="color: #64748b; display: block; margin-top: 0.25rem;">
                    If left empty, the POS system automatically suggests the default company percentage (<?= View::escape(Settings::get('loyalty_discount_percentage', '5.00')) ?>%).
                </small>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Account Status</label>
            <select name="status" class="form-control" <?= !empty($formData['is_walkin']) ? 'disabled' : '' ?>>
                <option value="active" <?= ($formData['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($formData['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <?php if (!empty($formData['is_walkin'])): ?>
                <small style="color: var(--color-text-muted); display: block; margin-top: 0.25rem;">Walk-in default customer status cannot be changed.</small>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
            <a href="customers.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Customer' ?></button>
        </div>
    </form>
</div>
