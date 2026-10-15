<?php
/**
 * Curtiss POS - Customer Profile & Credit Foundation View (Stage 5)
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Customer Profile: <?= View::escape($customer['name']) ?></h1>
        <p class="page-subtitle">Customer code: <strong><?= View::escape($customer['customer_code']) ?></strong> | Registered on <?= date('d M Y', strtotime($customer['created_at'])) ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <?php if (Auth::hasPermission('customers.manage')): ?>
            <a href="customer_edit.php?id=<?= $customer['id'] ?>" class="btn btn-primary">Edit Details</a>
        <?php endif; ?>
        <a href="customers.php" class="btn btn-secondary">Back to Customers</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Customer Master Details Card -->
    <div class="card">
        <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.5rem;">Customer Details</h3>
        
        <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.9rem;">
            <div>
                <span style="color: var(--color-text-muted); font-size: 0.8rem; display: block;">Full Name</span>
                <strong><?= View::escape($customer['name']) ?></strong>
                <?php if (!empty($customer['is_walkin'])): ?>
                    <span class="badge badge-info" style="font-size: 0.65rem;">Default Walk-in</span>
                <?php endif; ?>
            </div>

            <div>
                <span style="color: var(--color-text-muted); font-size: 0.8rem; display: block;">Customer Code / ID</span>
                <span style="font-family: monospace; font-weight: 600;"><?= View::escape($customer['customer_code']) ?></span> (ID: #<?= (int)$customer['id'] ?>)
            </div>

            <div>
                <span style="color: var(--color-text-muted); font-size: 0.8rem; display: block;">Card Barcode Scanner Code</span>
                <?php if (!empty($customer['card_barcode'])): ?>
                    <span style="font-family: monospace; font-weight: 600; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; border: 1px solid #cbd5e1;">
                        <?= View::escape($customer['card_barcode']) ?>
                    </span>
                <?php else: ?>
                    <span style="color: var(--color-text-muted);">No card scanner barcode assigned</span>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <div>
                    <span style="color: var(--color-text-muted); font-size: 0.8rem; display: block;">Phone</span>
                    <span><?= View::escape($customer['phone'] ?? 'N/A') ?></span>
                </div>
                <div>
                    <span style="color: var(--color-text-muted); font-size: 0.8rem; display: block;">WhatsApp</span>
                    <span><?= View::escape($customer['whatsapp'] ?? 'N/A') ?></span>
                </div>
            </div>

            <div>
                <span style="color: var(--color-text-muted); font-size: 0.8rem; display: block;">Address</span>
                <span><?= View::escape($customer['address'] ?? 'No address provided') ?></span>
            </div>

            <div>
                <span style="color: var(--color-text-muted); font-size: 0.8rem; display: block;">Account Status</span>
                <?php if ($customer['status'] === 'active'): ?>
                    <span class="badge badge-success">Active Account</span>
                <?php else: ?>
                    <span class="badge badge-danger">Inactive / Deactivated Account</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Credit Outstanding & Balance Overview -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="card" style="padding: 1.25rem;">
                <span style="font-size: 0.8rem; color: var(--color-text-muted); text-transform: uppercase; font-weight: 600;">Credit Limit</span>
                <div style="font-size: 1.35rem; font-weight: 700; color: var(--color-primary); margin-top: 0.25rem;">
                    LKR <?= number_format((float)$creditData['credit_limit'], 2) ?>
                </div>
            </div>

            <div class="card" style="padding: 1.25rem;">
                <span style="font-size: 0.8rem; color: var(--color-text-muted); text-transform: uppercase; font-weight: 600;">Outstanding Balance</span>
                <div style="font-size: 1.35rem; font-weight: 700; color: <?= $creditData['credit_balance'] > 0 ? '#dc2626' : '#16a34a' ?>; margin-top: 0.25rem;">
                    LKR <?= number_format((float)$creditData['credit_balance'], 2) ?>
                </div>
            </div>

            <div class="card" style="padding: 1.25rem;">
                <span style="font-size: 0.8rem; color: var(--color-text-muted); text-transform: uppercase; font-weight: 600;">Available Credit</span>
                <div style="font-size: 1.35rem; font-weight: 700; color: #16a34a; margin-top: 0.25rem;">
                    LKR <?= number_format((float)$creditData['available_credit'], 2) ?>
                </div>
            </div>
        </div>

        <!-- Customer Credit Ledger Foundation -->
        <div class="card">
            <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.5rem;">
                Customer Credit & Outstanding Ledger History
            </h3>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Reference No</th>
                            <th>Invoice Total</th>
                            <th>Amount Paid</th>
                            <th>Amount Due</th>
                            <th>Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($creditData['ledger'])): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--color-text-muted); padding: 1.5rem;">
                                    No credit transactions recorded for this customer yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($creditData['ledger'] as $l): ?>
                                <tr>
                                    <td><?= date('d M Y H:i', strtotime($l['created_at'])) ?></td>
                                    <td><span class="badge badge-info"><?= View::escape($l['transaction_type']) ?></span></td>
                                    <td><?= View::escape($l['reference_no'] ?? '-') ?></td>
                                    <td>LKR <?= number_format((float)$l['invoice_total'], 2) ?></td>
                                    <td>LKR <?= number_format((float)$l['amount_paid'], 2) ?></td>
                                    <td>LKR <?= number_format((float)$l['amount_due'], 2) ?></td>
                                    <td><strong>LKR <?= number_format((float)$l['balance_after'], 2) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Purchase History Placeholder Structure -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.5rem;">
        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">POS Sales & Purchase History (Foundation Placeholder)</h3>
        <span style="font-size: 0.85rem; color: var(--color-text-muted);">Ready for Stage 6 POS Sales module</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Date</th>
                    <th>Branch</th>
                    <th>Total Items</th>
                    <th>Invoice Amount</th>
                    <th>Payment Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchaseData['invoices'])): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--color-text-muted); padding: 1.5rem;">
                            No POS sales history available. (Will be populated when POS billing is introduced in Stage 6).
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($purchaseData['invoices'] as $inv): ?>
                        <tr>
                            <td><strong><?= View::escape($inv['invoice_no']) ?></strong></td>
                            <td><?= View::escape($inv['date']) ?></td>
                            <td><?= View::escape($inv['branch_name']) ?></td>
                            <td><?= (int)$inv['total_items'] ?></td>
                            <td>LKR <?= number_format((float)$inv['amount'], 2) ?></td>
                            <td><span class="badge badge-success"><?= View::escape($inv['status']) ?></span></td>
                            <td><button class="btn btn-secondary btn-sm" disabled>View</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
