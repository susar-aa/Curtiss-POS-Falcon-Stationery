<?php
/**
 * Curtiss POS - Dashboard View (Stage 1 + Stage 2 + Stage 3 Inventory)
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">System Dashboard</h1>
        <p class="page-subtitle">Multi-Branch Retail POS — Stage 3 Branch Inventory & Price-Lot Foundation Active</p>
    </div>
    <div>
        <span class="badge badge-teal" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;">
            Active Branch: <strong><?= View::escape($activeBranchName) ?></strong> (<?= View::escape($activeBranchCode) ?>)
        </span>
    </div>
</div>

<?php 
$hasExpired = !empty($expirySummary['expired_count']) && $expirySummary['expired_count'] > 0;
$hasNearExpiry = !empty($expirySummary['near_expiry_count']) && $expirySummary['near_expiry_count'] > 0;
if ($hasExpired || $hasNearExpiry): 
?>
<div style="background: <?= $hasExpired ? 'linear-gradient(135deg, #fff1f2 0%, #fef2f2 100%)' : 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)' ?>; border: 1px solid <?= $hasExpired ? '#fca5a5' : '#fcd34d' ?>; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: <?= $hasExpired ? '#fee2e2' : '#fef3c7' ?>; color: <?= $hasExpired ? '#dc2626' : '#d97706' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <div>
            <div style="font-size: 0.95rem; font-weight: 800; color: <?= $hasExpired ? '#991b1b' : '#92400e' ?>; margin-bottom: 2px;">
                <?= $hasExpired ? 'Expired Inventory Batches Detected' : 'Near-Expiry Inventory Alert' ?>
            </div>
            <div style="font-size: 0.8rem; color: #475569; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <?php if ($hasExpired): ?>
                <span><strong><?= (int)$expirySummary['expired_count'] ?></strong> batch<?= $expirySummary['expired_count'] > 1 ? 'es' : '' ?> (<?= number_format($expirySummary['expired_quantity'], 0) ?> units) expired — Loss: <strong style="color: #dc2626;">LKR <?= number_format($expirySummary['expired_loss_value'], 2) ?></strong></span>
                <?php endif; ?>
                <?php if ($hasNearExpiry): ?>
                <span style="color: #b45309;">⚠️ <strong><?= (int)$expirySummary['near_expiry_count'] ?></strong> batch<?= $expirySummary['near_expiry_count'] > 1 ? 'es' : '' ?> expiring within <?= (int)$expirySummary['alert_days_soon'] ?> days (LKR <?= number_format($expirySummary['near_expiry_value'], 2) ?> at risk)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="expired_stock.php" class="btn btn-sm" style="background: <?= $hasExpired ? '#dc2626' : '#d97706' ?>; color: #ffffff; font-weight: 700; border-radius: 999px; padding: 6px 14px; text-decoration: none;">
            Manage Expired Stock &rarr;
        </a>
        <a href="reports.php/expired_stock" class="btn btn-sm btn-secondary" style="font-weight: 700; border-radius: 999px; padding: 6px 14px; text-decoration: none;">
            Expiry Reports
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Quick Stat Cards -->
<div class="grid-cards">
    <div class="card stat-card">
        <div class="stat-info">
            <div class="stat-label"><?= !empty($isAllBranches) ? 'Total Stock Qty (All Branches)' : 'Branch Stock Qty' ?></div>
            <div class="stat-value"><?= number_format((float)$branchStockTotal, 0) ?></div>
        </div>
        <div class="stat-icon" style="background-color: var(--color-primary-light); color: var(--color-primary);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            </svg>
        </div>
    </div>

    <div class="card stat-card">
        <div class="stat-info">
            <div class="stat-label">Active Price Lots</div>
            <div class="stat-value"><?= (int)$activeLotsCount ?></div>
        </div>
        <div class="stat-icon" style="background-color: var(--color-teal-light); color: var(--color-teal);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>
        </div>
    </div>

    <div class="card stat-card">
        <div class="stat-info">
            <div class="stat-label">Stock Ledger Records</div>
            <div class="stat-value"><?= (int)$ledgerEntriesCount ?></div>
        </div>
        <div class="stat-icon" style="background-color: #f1f5f9; color: var(--color-secondary);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="20" x2="12" y2="10"></line>
                <line x1="18" y1="20" x2="18" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="16"></line>
            </svg>
        </div>
    </div>

    <div class="card stat-card">
        <div class="stat-info">
            <div class="stat-label">Low Stock Alerts</div>
            <div class="stat-value" style="color: <?= $lowStockCount > 0 ? 'var(--color-danger)' : 'var(--color-text-main)' ?>;">
                <?= (int)$lowStockCount ?>
            </div>
        </div>
        <div class="stat-icon" style="background-color: var(--color-warning-bg); color: var(--color-warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
    </div>
</div>

<!-- System Status Overview -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem;">
    <div class="card">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem;">Stage 3 Inventory & Price-Lot Status</h3>
        <p style="font-size: 0.875rem; color: var(--color-text-secondary); margin-bottom: 1rem;">
            Branch Inventory, Price/Cost Lots, and Append-Only Stock Movement Ledger are active for <strong><?= View::escape($activeBranchName) ?></strong>. Products remain global master records while stock levels and price lots are branch-isolated.
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; font-size: 0.85rem;">
            <div style="padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                <strong>🏢 Branch-Specific Stock:</strong><br>Products are global master records; stock quantities are branch-isolated.
            </div>
            <div style="padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                <strong>🏷 Price & Cost Lots:</strong><br>Supports multiple cost & selling price batches per product. Zero stock lots auto-deplete.
            </div>
            <div style="padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                <strong>📜 Append-Only Stock Ledger:</strong><br>Immutable movement trail capturing opening stock, GRN, sales, and adjustments.
            </div>
            <div style="padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                <strong>🛑 Negative Stock Prevention:</strong><br>Transactions strictly enforce non-negative stock rules across all branches.
            </div>
        </div>
    </div>

    <div class="card">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem;">Quick Navigation</h3>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
            <a href="inventory.php" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">📦 Branch Inventory</a>
            <a href="inventory_lots.php" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">🏷 Price & Cost Lots</a>
            <a href="expired_stock.php" class="btn btn-secondary btn-sm" style="justify-content: flex-start; <?= $hasExpired ? 'color: #dc2626; font-weight: 700;' : '' ?>">⏳ Expired & Near-Expiry Stock</a>
            <a href="inventory_ledger.php" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">📜 Movement History Ledger</a>
            <a href="products.php" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">🛒 Products</a>
        </div>
    </div>
</div>

<!-- Recent Audit Logs Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700;">Recent System Audit Trail</h3>
        <span class="badge badge-info">Live Operational Logs</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User ID</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Branch ID</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentAudits)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--color-text-muted);">No audit logs recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentAudits as $log): ?>
                        <tr>
                            <td><?= View::formatDate($log['created_at']) ?></td>
                            <td><strong><?= View::escape($log['user_id'] ?? 'System') ?></strong></td>
                            <td><span class="badge badge-teal"><?= View::escape($log['action']) ?></span></td>
                            <td><?= View::escape($log['module']) ?></td>
                            <td><?= View::escape($log['branch_id'] ?? 'N/A') ?></td>
                            <td><code><?= View::escape($log['ip_address']) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
