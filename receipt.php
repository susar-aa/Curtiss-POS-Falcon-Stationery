<?php
/**
 * Curtiss POS - 88mm Xprinter Thermal Receipt View (Stage 7)
 * Renders print-ready thermal receipt and supports receipt reprint.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Sale.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/Audit.php';

Auth::requireLogin();
if (Auth::isSpecialUser()) {
    header('Location: dashboard');
    exit;
}
Auth::requirePermission('pos.access');

$saleId = (int)($_GET['sale_id'] ?? 0);
$offlineRef = trim($_GET['offline_ref'] ?? '');
$isReprint = (int)($_GET['reprint'] ?? 0) === 1;

$companyInfo = Settings::getCompanyInfo();
$allBranches = Settings::getAllActiveBranches();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
$currentBranchInfo = Settings::getBranchInfo($activeBranchId);
$currency = $companyInfo['currency'] ?? 'LKR';

if (!empty($offlineRef)) {
    $businessName = $companyInfo['name'];
    $companyLogo = $companyInfo['logo'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Offline Receipt <?= htmlspecialchars($offlineRef) ?></title>
        <style>
            @page { size: 88mm auto; margin: 0; }
            body { font-family: 'Courier New', Courier, monospace; width: 80mm; margin: 0 auto; padding: 10px; font-size: 12px; color: #000; background: #fff; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .fw-bold { font-weight: bold; }
            .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
            .double-divider { border-bottom: 3px double #000; margin: 6px 0; }
            table { width: 100%; border-collapse: collapse; margin: 4px 0; }
            th, td { padding: 2px 0; vertical-align: top; }
            .btn-print-box { margin-bottom: 10px; text-align: center; }
            .receipt-logo { max-height: 48px; max-width: 160px; object-fit: contain; margin-bottom: 5px; }
            @media print { .btn-print-box { display: none; } }
        </style>
    </head>
    <body>
        <div class="btn-print-box">
            <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; cursor: pointer;">Print Receipt</button>
        </div>
        <div class="text-center fw-bold" style="border: 2px solid #000; padding: 4px; margin-bottom: 5px;">
            ** OFFLINE - PENDING SYNC **
        </div>
        <div class="text-center">
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/' . $companyLogo)): ?>
                <div><img src="<?= htmlspecialchars($companyLogo) ?>" alt="Logo" class="receipt-logo"></div>
            <?php endif; ?>
            <div style="font-size: 16px; font-weight: bold;"><?= htmlspecialchars($businessName) ?></div>
            <div><?= htmlspecialchars($currentBranchInfo['branch_name']) ?></div>
            <?php if (!empty($currentBranchInfo['branch_address'])): ?>
                <div><?= htmlspecialchars($currentBranchInfo['branch_address']) ?></div>
            <?php endif; ?>
            <div style="font-size: 11px;">
                <?php if (!empty($currentBranchInfo['branch_phone'])): ?>
                    <span>Tel: <?= htmlspecialchars($currentBranchInfo['branch_phone']) ?></span>
                <?php endif; ?>
                <?php if (!empty($companyInfo['whatsapp'])): ?>
                    <span><?= !empty($currentBranchInfo['branch_phone']) ? ' | ' : '' ?>WA: <?= htmlspecialchars($companyInfo['whatsapp']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($companyInfo['website'])): ?>
                <div style="font-size: 10px; color: #333;"><?= htmlspecialchars($companyInfo['website']) ?></div>
            <?php endif; ?>
        </div>
        <div class="divider"></div>
        <div id="offline-receipt-content">Loading offline receipt...</div>
        <script src="assets/js/offline_pos.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', async () => {
                const sales = await CurtissOfflinePOS.getAllOfflineSales();
                const sale = sales.find(s => s.temp_ref === "<?= htmlspecialchars($offlineRef) ?>" || s.offline_uuid === "<?= htmlspecialchars($offlineRef) ?>");
                if (!sale) {
                    document.getElementById('offline-receipt-content').innerText = "Offline receipt record not found in browser storage.";
                    return;
                }
                let itemsHtml = '';
                sale.items.forEach(item => {
                    itemsHtml += `
                        <tr>
                            <td colspan="4" class="fw-bold">${item.name || item.product_name || 'Product'}</td>
                        </tr>
                        <tr>
                            <td style="font-size: 10px; color: #555;">Lot #${item.lot_id}</td>
                            <td class="text-right">${item.quantity || item.qty}</td>
                            <td class="text-right">${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td class="text-right">${parseFloat(item.subtotal).toFixed(2)}</td>
                        </tr>
                    `;
                });

                let payHtml = '';
                let totalAllocated = 0;
                sale.payments.forEach(p => {
                    totalAllocated += parseFloat(p.amount);
                    payHtml += `<tr><td>${p.payment_method} ${p.reference_no ? '(' + p.reference_no + ')' : ''}:</td><td class="text-right">LKR ${parseFloat(p.amount).toFixed(2)}</td></tr>`;
                });
                const changeAmt = Math.max(0, totalAllocated - parseFloat(sale.total_amount));
                if (changeAmt > 0) {
                    payHtml += `<tr class="fw-bold"><td>CHANGE RETURN:</td><td class="text-right">LKR ${changeAmt.toFixed(2)}</td></tr>`;
                }

                const html = `
                    <div>
                        <div>Temp Ref: <strong>${sale.temp_ref}</strong></div>
                        <div>Date    : ${sale.created_at}</div>
                        <div>Customer: ${sale.customer_name}</div>
                        <div style="color:#b91c1c; font-weight:bold;">Status  : ${sale.sync_status}</div>
                    </div>
                    <div class="divider"></div>
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: left;">Item</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>${itemsHtml}</tbody>
                    </table>
                    <div class="divider"></div>
                    <table>
                        <tr><td>Subtotal:</td><td class="text-right">LKR ${parseFloat(sale.subtotal).toFixed(2)}</td></tr>
                        <tr><td>Discount (${(sale.customer_facing_discount || (sale.discount_str ? sale.discount_str.split('+')[0].trim() : '0'))}):</td><td class="text-right">${parseFloat(sale.total_discount || (sale.subtotal - sale.total_amount) || 0) >= 0 ? '- ' : '+ '}LKR ${Math.abs(parseFloat(sale.total_discount || (sale.subtotal - sale.total_amount) || 0)).toFixed(2)}</td></tr>
                        <tr class="fw-bold" style="font-size: 14px;"><td>NET TOTAL:</td><td class="text-right">LKR ${parseFloat(sale.total_amount).toFixed(2)}</td></tr>
                    </table>
                    <div class="divider"></div>
                    <div class="fw-bold">PAYMENT BREAKDOWN:</div>
                    <table>${payHtml}</table>
                    <div class="double-divider"></div>
                    <div class="text-center">
                        <div>Thank You For Shopping With Us!</div>
                        <div style="font-size: 10px; margin-top: 4px;">Temporary Receipt - Synchronizes automatically</div>
                    </div>
                `;
                document.getElementById('offline-receipt-content').innerHTML = html;
                window.print();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

$receiptData = Sale::getReceiptData($saleId);

if (!$receiptData) {
    die("Receipt record not found.");
}

$sale = $receiptData['sale'];
$items = $receiptData['items'];
$payments = $receiptData['payments'];

$branchInfo = Settings::getBranchInfo((int)($sale['branch_id'] ?? 1));
$companyLogo = $branchInfo['company_logo'];
$businessName = $branchInfo['company_name'];
$currency = $branchInfo['currency'] ?? 'LKR';

if ($isReprint) {
    if (!Auth::hasPermission('sales.reprint_receipt')) {
        die("Permission denied to reprint sales receipt.");
    }
    Audit::log('SALES_REPRINT_RECEIPT', 'pos', (string)$saleId, null, [
        'invoice_no' => $sale['invoice_no']
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= htmlspecialchars($sale['invoice_no']) ?></title>
    <style>
        @page {
            size: 88mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            font-size: 12px;
            color: #000;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-bottom: 3px double #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        th, td { padding: 2px 0; vertical-align: top; }
        .btn-print-box {
            margin-bottom: 10px;
            text-align: center;
        }
        .receipt-logo {
            max-height: 48px;
            max-width: 160px;
            object-fit: contain;
            margin-bottom: 5px;
        }
        @media print {
            .btn-print-box { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="btn-print-box">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; cursor: pointer;">
            Print Receipt
        </button>
    </div>

    <?php if ($isReprint): ?>
        <div class="text-center fw-bold" style="border: 1px solid #000; padding: 2px; margin-bottom: 5px;">
            ** REPRINTED RECEIPT **
        </div>
    <?php endif; ?>

    <div class="text-center">
        <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/' . $companyLogo)): ?>
            <div><img src="<?= htmlspecialchars($companyLogo) ?>" alt="Logo" class="receipt-logo"></div>
        <?php endif; ?>
        <div style="font-size: 16px; font-weight: bold;"><?= htmlspecialchars($businessName) ?></div>
        <div style="font-size: 12px; font-weight: bold;"><?= htmlspecialchars($branchInfo['branch_name']) ?></div>
        <?php if (!empty($branchInfo['branch_address'])): ?>
            <div><?= htmlspecialchars($branchInfo['branch_address']) ?></div>
        <?php endif; ?>
        <div style="font-size: 11px;">
            <?php if (!empty($branchInfo['branch_phone'])): ?>
                <span>Tel: <?= htmlspecialchars($branchInfo['branch_phone']) ?></span>
            <?php endif; ?>
            <?php if (!empty($branchInfo['company_whatsapp'])): ?>
                <span><?= !empty($branchInfo['branch_phone']) ? ' | ' : '' ?>WA: <?= htmlspecialchars($branchInfo['company_whatsapp']) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($branchInfo['company_website'])): ?>
            <div style="font-size: 10px; color: #333;"><?= htmlspecialchars($branchInfo['company_website']) ?></div>
        <?php elseif (!empty($branchInfo['branch_email'])): ?>
            <div style="font-size: 10px; color: #333;"><?= htmlspecialchars($branchInfo['branch_email']) ?></div>
        <?php endif; ?>
    </div>

    <div class="divider"></div>

    <div>
        <div>Inv No: <strong>#<?= htmlspecialchars($sale['invoice_no']) ?></strong></div>
        <div>Date  : <?= htmlspecialchars($sale['created_at']) ?></div>
        <div>Cashier: <?= htmlspecialchars($sale['cashier_name']) ?></div>
        <?php if (!empty($sale['sales_rep_name'])): ?>
            <div>Sales Rep: <?= htmlspecialchars($sale['sales_rep_name']) ?> (<?= htmlspecialchars($sale['sales_rep_code']) ?>)</div>
        <?php endif; ?>
        <div>Customer: <?= htmlspecialchars($sale['customer_name']) ?></div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td colspan="4" class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></td>
                </tr>
                <tr>
                    <?php 
                        $itemCleanDisc = !empty($item['discount_value']) ? trim(explode('+', $item['discount_value'])[0]) : '';
                    ?>
                    <td style="font-size: 10px; color: #555;"><?= htmlspecialchars($item['barcode']) ?><?= !empty($itemCleanDisc) ? ' | Disc: ' . htmlspecialchars($itemCleanDisc) : '' ?></td>
                    <td class="text-right"><?= (float)$item['quantity'] ?></td>
                    <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="text-right"><?= number_format($item['line_total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Subtotal:</td>
            <td class="text-right"><?= htmlspecialchars($currency) ?> <?= number_format($sale['subtotal'], 2) ?></td>
        </tr>
        <?php if (!empty($receiptData['customer_facing_discount'])): ?>
        <?php $displayDiscount = (float)$sale['subtotal'] - (float)$sale['total_amount']; ?>
        <tr>
            <td>Discount (<?= htmlspecialchars($receiptData['customer_facing_discount']) ?>):</td>
            <td class="text-right"><?= $displayDiscount >= 0 ? '- ' : '+ ' ?><?= htmlspecialchars($currency) ?> <?= number_format(abs($displayDiscount), 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="fw-bold" style="font-size: 14px;">
            <td>NET TOTAL:</td>
            <td class="text-right"><?= htmlspecialchars($currency) ?> <?= number_format($sale['total_amount'], 2) ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="fw-bold">PAYMENT BREAKDOWN:</div>
    <table>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['payment_method']) ?> <?= !empty($p['reference_no']) ? '(' . htmlspecialchars($p['reference_no']) . ')' : '' ?>:</td>
                <td class="text-right"><?= htmlspecialchars($currency) ?> <?= number_format($p['amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($receiptData['change_amount'] > 0): ?>
            <tr class="fw-bold">
                <td>CHANGE RETURN:</td>
                <td class="text-right"><?= htmlspecialchars($currency) ?> <?= number_format($receiptData['change_amount'], 2) ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="double-divider"></div>

    <div class="text-center">
        <div style="font-weight: bold;"><?= htmlspecialchars($branchInfo['receipt_footer'] ?: 'Thank You For Shopping With Us!') ?></div>
        <?php if (!empty($branchInfo['company_website'])): ?>
            <div style="font-size: 10px; margin-top: 2px; color: #222;"><?= htmlspecialchars($branchInfo['company_website']) ?></div>
        <?php endif; ?>
        <?php if (count($allBranches) > 1): ?>
            <div class="divider"></div>
            <div style="font-size: 9.5px; margin-top: 4px; line-height: 1.4; color: #222;">
                <div style="font-weight: bold; margin-bottom: 2px; text-transform: uppercase;">Our Branches</div>
                <?php foreach ($allBranches as $ab): ?>
                    <div><strong><?= htmlspecialchars($ab['name']) ?></strong>: <?= htmlspecialchars($ab['phone'] ?: '—') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div style="font-size: 9px; margin-top: 6px; color: #555;">Powered by <?= htmlspecialchars($businessName) ?></div>
    </div>
</body>
</html>
