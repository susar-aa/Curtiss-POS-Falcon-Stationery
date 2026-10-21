<?php
/**
 * Curtiss POS - Thermal Bill Receipt View (80mm / 88mm Xprinter & Standard POS)
 * Clean, modern layout, perfect tabular alignments, detailed item & savings breakdown, system branding, and developer credit.
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
$businessName = $companyInfo['name'] ?: 'Curtiss Retail';
$companyLogo = $companyInfo['logo'] ?? '';
$businessAddress = $companyInfo['address'] ?? '';
$businessPhone = $companyInfo['phone'] ?? '';
$businessWhatsapp = $companyInfo['whatsapp'] ?? '';
$businessEmail = $companyInfo['email'] ?? '';
$businessWebsite = $companyInfo['website'] ?? '';
$businessRegNo = $companyInfo['reg_no'] ?? '';
$currency = $companyInfo['currency'] ?: 'LKR';
$receiptFooter = $companyInfo['receipt_footer'] ?: 'Thank You For Shopping With Us!';

// ==========================================
// OFFLINE RECEIPT RENDERING (Client-Side JS)
// ==========================================
if (!empty($offlineRef)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Offline Receipt #<?= htmlspecialchars($offlineRef) ?></title>
        <style>
            @page {
                size: 80mm auto;
                margin: 0;
            }
            * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body {
                background: #f1f5f9;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                margin: 0;
                padding: 18px 8px;
                display: flex;
                flex-direction: column;
                align-items: center;
                color: #0f172a;
                font-size: 11px;
                line-height: 1.35;
            }
            .btn-print-box {
                margin-bottom: 12px;
                display: flex;
                gap: 8px;
            }
            .btn-action {
                background: #0f172a;
                color: #ffffff;
                border: none;
                padding: 8px 18px;
                font-size: 12px;
                font-weight: 700;
                border-radius: 999px;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.15s ease;
            }
            .btn-action:hover {
                background: #1e293b;
            }
            .btn-close {
                background: #ffffff;
                color: #475569;
                border: 1px solid #cbd5e1;
            }
            .receipt-card {
                background: #ffffff;
                width: 72mm;
                max-width: 72mm;
                padding: 12px 6px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                border-radius: 4px;
                box-sizing: border-box;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            .fw-bold { font-weight: 700; }
            .fw-bolder { font-weight: 800; }
            .tabular-nums { font-variant-numeric: tabular-nums; }
            .receipt-logo {
                max-height: 58px;
                max-width: 175px;
                object-fit: contain;
                margin-bottom: 5px;
                display: block;
                margin-left: auto;
                margin-right: auto;
            }
            .business-name {
                font-size: 12.5px;
                font-weight: 800;
                text-transform: uppercase;
                color: #0f172a;
                letter-spacing: 0.02em;
                line-height: 1.25;
            }
            .business-address {
                font-size: 10px;
                color: #334155;
                margin-top: 2px;
                line-height: 1.3;
            }
            .business-contact {
                font-size: 9.5px;
                color: #334155;
                margin-top: 2px;
                font-weight: 500;
            }
            .business-reg {
                font-size: 9px;
                color: #475569;
                margin-top: 2px;
                font-weight: 600;
            }
            .divider-dash {
                border-top: 1px dashed #475569;
                margin: 7px 0;
            }
            .divider-double {
                border-top: 3px double #0f172a;
                margin: 7px 0;
            }
            .divider-solid {
                border-top: 1px solid #cbd5e1;
                margin: 6px 0;
            }
            .meta-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 3px 6px;
                font-size: 10px;
            }
            .meta-item {
                display: flex;
                flex-direction: column;
            }
            .meta-label {
                font-size: 8.5px;
                font-weight: 800;
                text-transform: uppercase;
                color: #64748b;
                letter-spacing: 0.03em;
            }
            .meta-val {
                font-weight: 700;
                color: #0f172a;
                word-break: break-word;
                font-size: 10px;
            }
            .table-items {
                width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
                margin: 4px 0;
            }
            .table-items th {
                font-size: 9px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #334155;
                padding: 3px 0;
                border-bottom: 1.5px solid #0f172a;
            }
            .table-items td {
                padding: 3px 0;
                vertical-align: top;
                font-size: 10.5px;
            }
            .col-desc  { width: 48%; text-align: left; }
            .col-qty   { width: 12%; text-align: center; }
            .col-price { width: 20%; text-align: right; }
            .col-total { width: 20%; text-align: right; }
            .item-title {
                font-weight: 700;
                color: #0f172a;
                line-height: 1.25;
                word-break: break-word;
            }
            .item-inline-disc {
                font-size: 9.5px;
                font-weight: 700;
                color: #0f172a;
                white-space: nowrap;
            }
            .savings-badge {
                border: 1px dashed #0f172a;
                background: #f8fafc;
                border-radius: 4px;
                padding: 4px 6px;
                margin: 5px 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-weight: 800;
                font-size: 10.5px;
            }
            .table-totals {
                width: 100%;
                border-collapse: collapse;
            }
            .table-totals td {
                padding: 2px 0;
                font-size: 10.5px;
            }
            .net-total-row td {
                font-size: 13px;
                font-weight: 800;
                padding: 4px 0;
                border-top: 1.5px solid #0f172a;
                border-bottom: 1.5px solid #0f172a;
            }
            .receipt-footer-note {
                font-weight: 700;
                font-size: 10.5px;
                color: #0f172a;
                line-height: 1.3;
            }
            .receipt-footer-web {
                font-size: 10px;
                color: #334155;
                margin-top: 2px;
                font-weight: 600;
            }
            .dev-credit {
                margin-top: 8px;
                padding-top: 5px;
                border-top: 1px dotted #cbd5e1;
                font-size: 9px;
                color: #64748b;
                text-align: center;
                letter-spacing: 0.02em;
            }
            @media print {
                body {
                    background: #ffffff !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    width: 100% !important;
                }
                .btn-print-box { display: none !important; }
                .receipt-card {
                    box-shadow: none !important;
                    border-radius: 0 !important;
                    padding: 2mm 1mm !important;
                    max-width: 72mm !important;
                    width: 72mm !important;
                    margin: 0 auto !important;
                }
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="btn-print-box">
            <button class="btn-action" onclick="window.print()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print Receipt
            </button>
            <button class="btn-action btn-close" onclick="window.close()">Close</button>
        </div>

        <div class="receipt-card">
            <div class="text-center" style="border: 1.5px solid #b91c1c; background: #fef2f2; color: #b91c1c; padding: 4px; font-weight: 800; font-size: 10px; border-radius: 4px; margin-bottom: 8px;">
                OFFLINE RECEIPT &bull; PENDING SERVER SYNC
            </div>

            <!-- Header Branding (Strictly from Settings Page) -->
            <div class="text-center">
                <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/' . $companyLogo)): ?>
                    <img src="<?= htmlspecialchars($companyLogo) ?>" alt="Logo" class="receipt-logo">
                <?php endif; ?>
                <div class="business-name"><?= htmlspecialchars($businessName) ?></div>
                <?php if (!empty($businessAddress)): ?>
                    <div class="business-address"><?= nl2br(htmlspecialchars($businessAddress)) ?></div>
                <?php endif; ?>
                <?php if (!empty($businessPhone) || !empty($businessWhatsapp)): ?>
                    <div class="business-contact">
                        <?php if (!empty($businessPhone)): ?>
                            <span>Tel: <?= htmlspecialchars($businessPhone) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($businessWhatsapp)): ?>
                            <span><?= !empty($businessPhone) ? ' | ' : '' ?>WA: <?= htmlspecialchars($businessWhatsapp) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($businessWebsite) || !empty($businessEmail)): ?>
                    <div class="business-contact">
                        <?php if (!empty($businessWebsite)): ?>
                            <span><?= htmlspecialchars($businessWebsite) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($businessEmail)): ?>
                            <span><?= !empty($businessWebsite) ? ' | ' : '' ?><?= htmlspecialchars($businessEmail) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($businessRegNo)): ?>
                    <div class="business-reg">Reg/Tax No: <?= htmlspecialchars($businessRegNo) ?></div>
                <?php endif; ?>
            </div>

            <div class="divider-dash"></div>

            <div id="offline-receipt-content">
                <div class="text-center" style="padding: 15px; color: #64748b;">Loading offline sale data...</div>
            </div>

            <div class="divider-dash"></div>

            <div class="text-center">
                <div class="receipt-footer-note"><?= htmlspecialchars($receiptFooter) ?></div>
                <?php if (!empty($businessWebsite)): ?>
                    <div class="receipt-footer-web"><?= htmlspecialchars($businessWebsite) ?></div>
                <?php endif; ?>
                <div style="font-size: 9px; color: #64748b; margin-top: 3px;">Temporary offline record &bull; Saved securely</div>
                
                <div class="dev-credit">
                    System by Curtiss &bull; <strong style="color: #0f172a;">www.trycurtiss.com</strong>
                </div>
            </div>
        </div>

        <script src="assets/js/offline_pos.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', async () => {
                const sales = await CurtissOfflinePOS.getAllOfflineSales();
                const sale = sales.find(s => s.temp_ref === "<?= htmlspecialchars($offlineRef) ?>" || s.offline_uuid === "<?= htmlspecialchars($offlineRef) ?>");
                if (!sale) {
                    document.getElementById('offline-receipt-content').innerHTML = "<div class='text-center' style='color:#dc2626; padding:15px;'>Offline invoice record not found in browser storage.</div>";
                    return;
                }

                let itemsHtml = '';
                let grossSubtotal = 0;
                let itemDiscountsTotal = 0;

                sale.items.forEach(item => {
                    const qty = parseFloat(item.quantity || item.qty || 1);
                    const unitPrice = parseFloat(item.unit_price || 0);
                    const lineSubtotal = qty * unitPrice;
                    grossSubtotal += lineSubtotal;

                    const discAmt = parseFloat(item.discount_amount || 0);
                    itemDiscountsTotal += discAmt;
                    const lineTotal = Math.max(0, lineSubtotal - discAmt);

                    let inlineDiscHtml = '';
                    if (discAmt > 0.001) {
                        inlineDiscHtml = ` <span class="item-inline-disc">(-LKR ${discAmt.toFixed(2)})</span>`;
                    }

                    itemsHtml += `
                        <tr>
                            <td class="col-desc">
                                <div class="item-title">${item.product_name || item.name || 'Product'}${inlineDiscHtml}</div>
                            </td>
                            <td class="col-qty tabular-nums">${qty}</td>
                            <td class="col-price tabular-nums">${unitPrice.toFixed(2)}</td>
                            <td class="col-total tabular-nums fw-bold">${lineTotal.toFixed(2)}</td>
                        </tr>
                    `;
                });

                const cartSubtotal = parseFloat(sale.subtotal || grossSubtotal);
                const finalTotal = parseFloat(sale.total_amount || 0);
                const invoiceDiscAmt = Math.max(0, cartSubtotal - finalTotal);
                const totalSavings = Math.max(0, (grossSubtotal - finalTotal));

                let payHtml = '';
                let totalAllocated = 0;
                (sale.payments || []).forEach(p => {
                    const amt = parseFloat(p.amount || 0);
                    totalAllocated += amt;
                    payHtml += `
                        <tr>
                            <td class="text-left">${p.payment_method} ${p.reference_no ? '(' + p.reference_no + ')' : ''}:</td>
                            <td class="text-right tabular-nums">LKR ${amt.toFixed(2)}</td>
                        </tr>
                    `;
                });

                const changeAmt = Math.max(0, totalAllocated - finalTotal);
                if (changeAmt > 0) {
                    payHtml += `
                        <tr class="fw-bold" style="border-top: 1px dotted #cbd5e1;">
                            <td class="text-left">CHANGE RETURN:</td>
                            <td class="text-right tabular-nums">LKR ${changeAmt.toFixed(2)}</td>
                        </tr>
                    `;
                }

                let savingsHtml = '';
                if (totalSavings > 0.001) {
                    savingsHtml = `
                        <div class="savings-badge">
                            <span>TOTAL SAVINGS</span>
                            <span class="tabular-nums">- LKR ${totalSavings.toFixed(2)}</span>
                        </div>
                    `;
                }

                const html = `
                    <div class="meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Temp Reference</span>
                            <span class="meta-val">#${sale.temp_ref}</span>
                        </div>
                        <div class="meta-item text-right">
                            <span class="meta-label">Date & Time</span>
                            <span class="meta-val">${sale.created_at || new Date().toLocaleString()}</span>
                        </div>
                    </div>

                    <div class="divider-dash"></div>

                    <table class="table-items">
                        <thead>
                            <tr>
                                <th class="col-desc">Item Description</th>
                                <th class="col-qty">Qty</th>
                                <th class="col-price">Price</th>
                                <th class="col-total">Total</th>
                            </tr>
                        </thead>
                        <tbody>${itemsHtml}</tbody>
                    </table>

                    <div class="divider-dash"></div>

                    <table class="table-totals">
                        <tr>
                            <td class="text-left">Gross Subtotal:</td>
                            <td class="text-right tabular-nums">LKR ${grossSubtotal.toFixed(2)}</td>
                        </tr>
                        ${itemDiscountsTotal > 0 ? `
                        <tr>
                            <td class="text-left">Item Discounts:</td>
                            <td class="text-right tabular-nums">- LKR ${itemDiscountsTotal.toFixed(2)}</td>
                        </tr>
                        ` : ''}
                        ${invoiceDiscAmt > 0 ? `
                        <tr>
                            <td class="text-left">Bill Discount ${sale.customer_facing_discount ? '(' + sale.customer_facing_discount + ')' : ''}:</td>
                            <td class="text-right tabular-nums">- LKR ${invoiceDiscAmt.toFixed(2)}</td>
                        </tr>
                        ` : ''}
                        <tr class="fw-bolder" style="font-size: 14px; border-top: 1.5px solid #0f172a;">
                            <td class="text-left" style="padding-top: 4px;">NET TOTAL:</td>
                            <td class="text-right tabular-nums" style="padding-top: 4px;">LKR ${finalTotal.toFixed(2)}</td>
                        </tr>
                    </table>

                    ${savingsHtml}

                    <div class="divider-dash"></div>

                    <div class="fw-bold" style="font-size: 10px; text-transform: uppercase; color: #475569; margin-bottom: 2px;">Payment Breakdown:</div>
                    <table class="table-totals">${payHtml}</table>
                `;

                document.getElementById('offline-receipt-content').innerHTML = html;
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// ONLINE AUTHORITATIVE RECEIPT RENDERING
// ==========================================
$receiptData = Sale::getReceiptData($saleId);

if (!$receiptData) {
    die("Receipt record not found.");
}

$sale = $receiptData['sale'];
$items = $receiptData['items'];
$payments = $receiptData['payments'];

// Strictly maintain store branding from Settings Page
$companyLogo = $companyInfo['logo'] ?? '';
$businessName = $companyInfo['name'] ?: 'Curtiss Retail';
$currency = $companyInfo['currency'] ?: 'LKR';

$grossSubtotal = (float)($receiptData['gross_subtotal'] ?? 0);
$itemDiscountTotal = (float)($receiptData['item_discount_total'] ?? 0);
$invoiceDiscountTotal = (float)($receiptData['invoice_discount_total'] ?? 0);
$totalSavings = (float)($receiptData['total_savings'] ?? 0);
$cartSubtotal = (float)$sale['subtotal'];
$netTotal = (float)$sale['total_amount'];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= htmlspecialchars($sale['invoice_no']) ?> - <?= htmlspecialchars($businessName) ?></title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            background: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 18px 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.35;
        }
        .btn-print-box {
            margin-bottom: 12px;
            display: flex;
            gap: 8px;
        }
        .btn-action {
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 999px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .btn-action:hover {
            background: #1e293b;
        }
        .btn-close {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        .btn-close:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .receipt-card {
            background: #ffffff;
            width: 72mm;
            max-width: 72mm;
            padding: 12px 6px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: 700; }
        .fw-bolder { font-weight: 800; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
        .receipt-logo {
            max-height: 58px;
            max-width: 175px;
            object-fit: contain;
            margin-bottom: 5px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .business-name {
            font-size: 12.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.02em;
            line-height: 1.25;
        }
        .business-address {
            font-size: 10px;
            color: #334155;
            margin-top: 2px;
            line-height: 1.3;
        }
        .business-contact {
            font-size: 9.5px;
            color: #334155;
            margin-top: 2px;
            font-weight: 500;
        }
        .business-reg {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
            font-weight: 600;
        }
        .divider-dash {
            border-top: 1px dashed #475569;
            margin: 7px 0;
        }
        .divider-double {
            border-top: 3px double #0f172a;
            margin: 7px 0;
        }
        .divider-solid {
            border-top: 1px solid #cbd5e1;
            margin: 6px 0;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3px 6px;
            font-size: 10px;
        }
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        .meta-label {
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.03em;
        }
        .meta-val {
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
            font-size: 10px;
        }
        .table-items {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .table-items th {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #334155;
            padding: 3px 0;
            border-bottom: 1.5px solid #0f172a;
        }
        .table-items td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 10.5px;
        }
        .col-desc  { width: 48%; text-align: left; }
        .col-qty   { width: 12%; text-align: center; }
        .col-price { width: 20%; text-align: right; }
        .col-total { width: 20%; text-align: right; }
        .item-title {
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
            word-break: break-word;
        }
        .item-inline-disc {
            font-size: 9.5px;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }
        .savings-badge {
            border: 1px dashed #0f172a;
            background: #f8fafc;
            border-radius: 4px;
            padding: 4px 6px;
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
            font-size: 10.5px;
        }
        .table-totals {
            width: 100%;
            border-collapse: collapse;
        }
        .table-totals td {
            padding: 2px 0;
            font-size: 10.5px;
        }
        .net-total-row td {
            font-size: 13px;
            font-weight: 800;
            padding: 4px 0;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
        }
        .receipt-footer-note {
            font-weight: 700;
            font-size: 10.5px;
            color: #0f172a;
            line-height: 1.3;
        }
        .receipt-footer-web {
            font-size: 10px;
            color: #334155;
            margin-top: 2px;
            font-weight: 600;
        }
        .dev-credit {
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dotted #cbd5e1;
            font-size: 9px;
            color: #64748b;
            text-align: center;
            letter-spacing: 0.02em;
        }
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }
            .btn-print-box { display: none !important; }
            .receipt-card {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 2mm 1mm !important;
                max-width: 72mm !important;
                width: 72mm !important;
                margin: 0 auto !important;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <!-- Top Action Toolbar (Hidden during print) -->
    <div class="btn-print-box">
        <button class="btn-action" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print Receipt
        </button>
        <button class="btn-action btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="receipt-card">
        <?php if ($isReprint): ?>
            <div class="text-center" style="border: 1.5px solid #0f172a; padding: 2px 4px; font-weight: 800; font-size: 10px; margin-bottom: 8px; text-transform: uppercase;">
                ** REPRINTED / DUPLICATE RECEIPT **
            </div>
        <?php endif; ?>

        <!-- 1. SYSTEM STORE INFORMATION (Strictly from Settings Page) -->
        <div class="text-center">
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/' . $companyLogo)): ?>
                <img src="<?= htmlspecialchars($companyLogo) ?>" alt="Logo" class="receipt-logo">
            <?php endif; ?>
            <div class="business-name"><?= htmlspecialchars($businessName) ?></div>
            <?php if (!empty($businessAddress)): ?>
                <div class="business-address"><?= nl2br(htmlspecialchars($businessAddress)) ?></div>
            <?php endif; ?>
            <?php if (!empty($businessPhone) || !empty($businessWhatsapp)): ?>
                <div class="business-contact">
                    <?php if (!empty($businessPhone)): ?>
                        <span>Tel: <?= htmlspecialchars($businessPhone) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($businessWhatsapp)): ?>
                        <span><?= !empty($businessPhone) ? ' | ' : '' ?>WA: <?= htmlspecialchars($businessWhatsapp) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($businessWebsite) || !empty($businessEmail)): ?>
                <div class="business-contact">
                    <?php if (!empty($businessWebsite)): ?>
                        <span><?= htmlspecialchars($businessWebsite) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($businessEmail)): ?>
                        <span><?= !empty($businessWebsite) ? ' | ' : '' ?><?= htmlspecialchars($businessEmail) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($businessRegNo)): ?>
                <div class="business-reg">Reg/Tax No: <?= htmlspecialchars($businessRegNo) ?></div>
            <?php endif; ?>
        </div>

        <div class="divider-dash"></div>

        <!-- 2. INVOICE METADATA GRID (Crisp 2-Column Alignment) -->
        <div class="meta-grid">
            <div class="meta-item">
                <span class="meta-label">Invoice Number</span>
                <span class="meta-val">#<?= htmlspecialchars($sale['invoice_no']) ?></span>
            </div>
            <div class="meta-item text-right">
                <span class="meta-label">Date & Time</span>
                <span class="meta-val"><?= date('d M Y, h:i A', strtotime($sale['created_at'])) ?></span>
            </div>
        </div>

        <div class="divider-dash"></div>

        <!-- 3. DETAILED ITEMS TABLE (Clean 4-Column Layout) -->
        <table class="table-items">
            <thead>
                <tr>
                    <th class="col-desc">Item Description</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-price">Price</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <?php 
                        $qty = (float)$item['quantity'];
                        $unitPrice = (float)$item['unit_price'];
                        $lineTotal = (float)$item['line_total'];
                        $itemDisc = (float)($item['discount_amount'] ?? 0);
                        $itemPromo = (float)($item['promo_discount_amount'] ?? 0);
                        $totalLineDisc = $itemDisc + $itemPromo;
                    ?>
                    <tr>
                        <td class="col-desc">
                            <div class="item-title">
                                <?= htmlspecialchars($item['product_name']) ?><?php if ($totalLineDisc > 0.001): ?> <span class="item-inline-disc">(-<?= htmlspecialchars($currency) ?> <?= number_format($totalLineDisc, 2) ?>)</span><?php endif; ?>
                            </div>
                        </td>
                        <td class="col-qty tabular-nums"><?= $qty == (int)$qty ? (int)$qty : number_format($qty, 2) ?></td>
                        <td class="col-price tabular-nums"><?= number_format($unitPrice, 2) ?></td>
                        <td class="col-total tabular-nums fw-bold"><?= number_format($lineTotal, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="divider-dash"></div>

        <!-- 4. TOTALS & SAVINGS BREAKDOWN -->
        <table class="table-totals">
            <tr>
                <td class="text-left">Gross Subtotal:</td>
                <td class="text-right tabular-nums"><?= htmlspecialchars($currency) ?> <?= number_format($grossSubtotal > 0 ? $grossSubtotal : $cartSubtotal, 2) ?></td>
            </tr>
            <?php if ($itemDiscountTotal > 0.001): ?>
                <tr>
                    <td class="text-left">Item Discounts:</td>
                    <td class="text-right tabular-nums" style="color: #0f172a;">- <?= htmlspecialchars($currency) ?> <?= number_format($itemDiscountTotal, 2) ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($invoiceDiscountTotal > 0.001 || !empty($receiptData['customer_facing_discount'])): ?>
                <?php $invDiscShown = $invoiceDiscountTotal > 0 ? $invoiceDiscountTotal : abs((float)$cartSubtotal - (float)$netTotal); ?>
                <tr>
                    <td class="text-left">Bill Discount <?= !empty($receiptData['customer_facing_discount']) ? '(' . htmlspecialchars($receiptData['customer_facing_discount']) . ')' : '' ?>:</td>
                    <td class="text-right tabular-nums" style="color: #0f172a;">- <?= htmlspecialchars($currency) ?> <?= number_format($invDiscShown, 2) ?></td>
                </tr>
            <?php endif; ?>
            <tr class="net-total-row">
                <td class="text-left">NET TOTAL:</td>
                <td class="text-right tabular-nums"><?= htmlspecialchars($currency) ?> <?= number_format($netTotal, 2) ?></td>
            </tr>
        </table>

        <!-- TOTAL SAVINGS BANNER -->
        <?php if ($totalSavings > 0.001): ?>
            <div class="savings-badge">
                <span>&#9733; TOTAL SAVINGS</span>
                <span class="tabular-nums">- <?= htmlspecialchars($currency) ?> <?= number_format($totalSavings, 2) ?></span>
            </div>
        <?php endif; ?>

        <div class="divider-dash"></div>

        <!-- 5. PAYMENT BREAKDOWN -->
        <div class="fw-bold" style="font-size: 10px; text-transform: uppercase; color: #475569; margin-bottom: 2px;">
            Payment Summary:
        </div>
        <table class="table-totals">
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td class="text-left">
                        <?= htmlspecialchars($p['payment_method']) ?><?= !empty($p['reference_no']) ? ' (' . htmlspecialchars($p['reference_no']) . ')' : '' ?>:
                    </td>
                    <td class="text-right tabular-nums"><?= htmlspecialchars($currency) ?> <?= number_format($p['amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($receiptData['change_amount'] > 0.001): ?>
                <tr class="fw-bold" style="border-top: 1px dotted #cbd5e1;">
                    <td class="text-left" style="padding-top: 3px;">CHANGE RETURN:</td>
                    <td class="text-right tabular-nums" style="padding-top: 3px;"><?= htmlspecialchars($currency) ?> <?= number_format($receiptData['change_amount'], 2) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="divider-double"></div>

        <!-- 6. FOOTER & DEVELOPER CREDIT (Settings Only) -->
        <div class="text-center">
            <div class="receipt-footer-note"><?= htmlspecialchars($receiptFooter) ?></div>
            <?php if (!empty($businessWebsite)): ?>
                <div class="receipt-footer-web"><?= htmlspecialchars($businessWebsite) ?></div>
            <?php endif; ?>

            <!-- DEVELOPER CREDIT -->
            <div class="dev-credit">
                System by Curtiss &bull; <strong style="color: #0f172a;">www.trycurtiss.com</strong>
            </div>
        </div>
    </div>
</body>
</html>