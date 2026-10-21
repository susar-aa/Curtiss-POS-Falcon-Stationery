<?php
/**
 * Curtiss POS - Goods Received Note (GRN) Print & PDF Document
 * Precision A4 Portrait Layout (210mm x 297mm) with Vector Typography
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/GRN.php';
require_once __DIR__ . '/core/Settings.php';

Auth::requireAuth();

$grnId = (int)($_GET['id'] ?? 0);
if ($grnId <= 0) {
    die("Error: Invalid Goods Received Note ID.");
}

$grn = GRN::find($grnId);
if (!$grn) {
    die("Error: Goods Received Note not found.");
}

$branchInfo = Settings::getBranchInfo((int)($grn['branch_id'] ?? 1));
$businessName = $branchInfo['company_name'] ?: 'Falcon Stationery';
$companyLogo = $branchInfo['company_logo'] ?? '';
$businessPhone = $branchInfo['branch_phone'] ?: $branchInfo['company_phone'];
$businessEmail = $branchInfo['branch_email'] ?: $branchInfo['company_email'];
$businessAddress = $branchInfo['branch_address'] ?: $branchInfo['company_address'];
$businessWebsite = $branchInfo['company_website'] ?? '';
$businessWhatsapp = $branchInfo['company_whatsapp'] ?? '';
$businessRegNo = $branchInfo['company_reg_no'] ?? '';

$autoPrint = isset($_GET['print']) && $_GET['print'] === '1';
$diff = (float)$grn['difference_amount'];
$hasDiff = abs($diff) > 0.001;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN_<?= htmlspecialchars($grn['grn_number']) ?>_<?= date('Ymd') ?></title>
    <style>
        /* ── Standard CSS Reset & Print Quality Rules ── */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            background: #cbd5e1;
            margin: 0;
            padding: 20px 0 40px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 9.5pt;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Floating Action Bar (Screen Only) ── */
        .print-toolbar {
            position: fixed;
            top: 14px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(10px);
            padding: 8px 18px;
            border-radius: 999px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 15px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            font-family: inherit;
        }
        .tool-btn-primary {
            background: #0284c7;
            color: #ffffff;
        }
        .tool-btn-primary:hover {
            background: #0369a1;
            transform: translateY(-1px);
        }
        .tool-btn-secondary {
            background: #ffffff;
            color: #0f172a;
        }
        .tool-btn-secondary:hover {
            background: #f1f5f9;
        }
        .tool-btn-ghost {
            background: rgba(255, 255, 255, 0.12);
            color: #f8fafc;
        }
        .tool-btn-ghost:hover {
            background: rgba(255, 255, 255, 0.22);
        }
        .tool-credits {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            border-left: 1px solid rgba(255, 255, 255, 0.18);
            padding-left: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .tool-credits a {
            color: #38bdf8;
            text-decoration: none;
        }
        .tool-credits a:hover {
            text-decoration: underline;
        }

        /* ── True A4 Page Container (210mm x 297mm) ── */
        .a4-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 48px auto 0 auto;
            background: #ffffff;
            padding: 12mm 15mm 12mm 15mm;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.16);
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-sizing: border-box;
        }

        /* ── Header Section ── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            gap: 16px;
        }
        .header-brand {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
        }
        .brand-logo-img {
            max-height: 52px;
            max-width: 140px;
            object-fit: contain;
        }
        .brand-logo-box {
            width: 50px;
            height: 50px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 900;
            letter-spacing: -0.05em;
            flex-shrink: 0;
        }
        .brand-title {
            font-size: 1.35rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .brand-meta {
            font-size: 7.8pt;
            color: #475569;
            line-height: 1.35;
            margin-top: 3px;
        }
        .brand-meta strong {
            color: #0f172a;
        }

        .doc-title-block {
            text-align: right;
            flex-shrink: 0;
        }
        .doc-main-title {
            font-size: 1.35rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .doc-po-number {
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
            font-size: 1rem;
            font-weight: 800;
            color: #0284c7;
            margin-top: 2px;
        }
        .status-pill {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }
        .status-approved { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-draft { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        .po-ref-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 7.5pt;
            font-weight: 700;
            color: #0284c7;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 999px;
            padding: 1px 8px;
            margin-top: 4px;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        }

        /* ── Information Grid (Vendor & Receiving) ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 12px;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 9px 12px;
        }
        .info-card-title {
            font-size: 7pt;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }
        .info-card-name {
            font-size: 9.5pt;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
        }
        .info-card-text {
            font-size: 7.8pt;
            color: #334155;
            line-height: 1.4;
            margin-top: 2px;
        }
        .info-card-text strong {
            color: #0f172a;
        }

        /* ── Product Items Table ── */
        .table-wrap {
            margin-top: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.2pt;
        }
        .items-table thead th {
            background: #0f172a;
            color: #ffffff;
            font-size: 7.2pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 7px 10px;
            border: none;
            line-height: 1.2;
        }
        .items-table tbody td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            color: #1e293b;
        }
        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .col-center { text-align: center; }
        .col-right { text-align: right; }
        .font-mono {
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        }

        /* ── Notes & Financial Summary Section ── */
        .bottom-section {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 12px;
            margin-top: 12px;
            align-items: flex-start;
        }
        .notes-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 9px 12px;
            font-size: 7.6pt;
            color: #475569;
            line-height: 1.45;
        }
        .notes-card-title {
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-size: 7pt;
            color: #0f172a;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 2px;
        }
        .totals-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 9px 12px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            color: #475569;
            margin-bottom: 4px;
        }
        .total-row strong {
            color: #0f172a;
        }
        .total-grand {
            border-top: 2px solid #0f172a;
            padding-top: 6px;
            margin-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-grand span {
            font-size: 8.5pt;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }
        .total-grand strong {
            font-size: 10pt;
            font-weight: 900;
            color: #0f172a;
        }

        /* ── Signatures Section ── */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 24px;
            padding-top: 8px;
        }
        .sig-block {
            text-align: center;
        }
        .sig-line {
            border-top: 1px dashed #64748b;
            margin-bottom: 5px;
            height: 1px;
        }
        .sig-label {
            font-size: 7.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .sig-sub {
            font-size: 7pt;
            color: #64748b;
            margin-top: 2px;
        }

        /* ── Document Footer & Developer Credits ── */
        .doc-footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.2pt;
            color: #64748b;
        }
        .footer-left {
            flex: 1;
            text-align: left;
        }
        .footer-center {
            flex: 1;
            text-align: center;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.02em;
        }
        .footer-center a {
            color: #0284c7;
            text-decoration: none;
            font-weight: 800;
        }
        .footer-right {
            flex: 1;
            text-align: right;
        }

        /* ── Page Break Avoidance ── */
        .avoid-break {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        /* ── Screen Responsiveness for Preview ── */
        @media screen and (max-width: 220mm) {
            body {
                padding: 10px;
            }
            .a4-sheet {
                width: 100%;
                max-width: 100%;
                min-height: auto;
                padding: 16px;
                margin-top: 60px;
            }
        }

        /* ── STRICT PRINT RULES FOR CLEAN A4 EXPORT ── */
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 12mm 12mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 9pt;
            }
            .print-toolbar {
                display: none !important;
            }
            .a4-sheet {
                width: 100% !important;
                max-width: 100% !important;
                min-height: 275mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
            }
            .items-table {
                page-break-inside: auto;
            }
            .items-table thead {
                display: table-header-group !important;
            }
            .items-table tr {
                page-break-inside: avoid !important;
                page-break-after: auto;
            }
            .avoid-break {
                page-break-inside: avoid !important;
            }
            .footer-center a {
                color: #0f172a !important;
                text-decoration: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Top Toolbar (Screen Only) -->
    <div class="print-toolbar">
        <a href="grns" class="tool-btn tool-btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to GRNs
        </a>
        <button type="button" class="tool-btn tool-btn-secondary" onclick="window.print()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print GRN
        </button>
        <button type="button" class="tool-btn tool-btn-primary" onclick="exportToPDF()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Save as PDF
        </button>
        <div class="tool-credits">
            <span>Powered by Curtiss POS</span> • <a href="https://www.trycurtiss.com" target="_blank" rel="noopener">www.trycurtiss.com</a>
        </div>
    </div>

    <!-- Official A4 Document Sheet -->
    <div class="a4-sheet" id="grnDocumentSheet">
        <div>
            <!-- Header Section -->
            <div class="doc-header">
                <div class="header-brand">
                    <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/' . $companyLogo)): ?>
                        <img src="<?= htmlspecialchars($companyLogo) ?>" alt="Logo" class="brand-logo-img">
                    <?php else: ?>
                        <div class="brand-logo-box"><?= strtoupper(substr($businessName, 0, 1)) ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="brand-title"><?= htmlspecialchars($businessName) ?></div>
                        <div class="brand-meta">
                            <strong>Receiving Branch:</strong> <?= htmlspecialchars($branchInfo['branch_name']) ?> (<?= htmlspecialchars($branchInfo['branch_code'] ?? 'BR-01') ?>)<br>
                            <?= !empty($businessAddress) ? htmlspecialchars($businessAddress) . '<br>' : '' ?>
                            Phone: <?= htmlspecialchars($businessPhone ?: '—') ?>
                            <?= !empty($businessWhatsapp) ? ' | WA: ' . htmlspecialchars($businessWhatsapp) : '' ?>
                            <?= !empty($businessEmail) ? ' | Email: ' . htmlspecialchars($businessEmail) : '' ?>
                            <?= !empty($businessWebsite) ? ' | Web: ' . htmlspecialchars($businessWebsite) : '' ?>
                            <?= !empty($businessRegNo) ? '<br>Reg No: ' . htmlspecialchars($businessRegNo) : '' ?>
                        </div>
                    </div>
                </div>

                <div class="doc-title-block">
                    <div class="doc-main-title">GOODS RECEIVED NOTE</div>
                    <div class="doc-po-number"><?= htmlspecialchars($grn['grn_number']) ?></div>
                    <div>
                        <?php if ($grn['status'] === 'Approved'): ?>
                            <span class="status-pill status-approved">Approved (Stock Posted)</span>
                        <?php elseif ($grn['status'] === 'Cancelled'): ?>
                            <span class="status-pill status-cancelled">Cancelled</span>
                        <?php else: ?>
                            <span class="status-pill status-draft">Draft</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($grn['po_number'])): ?>
                        <div>
                            <span class="po-ref-pill">PO Ref: <?= htmlspecialchars($grn['po_number']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Two-Column Information Details Grid -->
            <div class="info-grid">
                <!-- Vendor Box -->
                <div class="info-card">
                    <div class="info-card-title">SUPPLIER / VENDOR</div>
                    <div class="info-card-name"><?= htmlspecialchars($grn['supplier_name']) ?></div>
                    <div class="info-card-text">
                        <?php if (!empty($grn['supplier_code'])): ?>
                            <strong>Code:</strong> <?= htmlspecialchars($grn['supplier_code']) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($grn['supplier_phone'])): ?>
                            <strong>Phone:</strong> <?= htmlspecialchars($grn['supplier_phone']) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($grn['supplier_whatsapp'])): ?>
                            <strong>WhatsApp:</strong> <?= htmlspecialchars($grn['supplier_whatsapp']) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($grn['supplier_address'])): ?>
                            <strong>Address:</strong> <?= htmlspecialchars($grn['supplier_address']) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Invoice & Receiving Box -->
                <div class="info-card">
                    <div class="info-card-title">INVOICE & RECEIVING DETAILS</div>
                    <div class="info-card-name">Invoice: <?= htmlspecialchars($grn['supplier_invoice_no']) ?></div>
                    <div class="info-card-text">
                        <strong>Invoice Date:</strong> <?= !empty($grn['supplier_invoice_date']) ? date('d M Y', strtotime($grn['supplier_invoice_date'])) : '—' ?><br>
                        <strong>Received Date:</strong> <?= date('d M Y', strtotime($grn['received_date'])) ?><br>
                        <strong>Received By:</strong> <?= htmlspecialchars($grn['creator_name']) ?><br>
                        <?php if (!empty($grn['payment_due_date'])): ?>
                            <strong>Payment Due:</strong> <?= date('d M Y', strtotime($grn['payment_due_date'])) ?><br>
                        <?php endif; ?>
                        <strong>Variance Status:</strong> <?= $hasDiff ? htmlspecialchars($grn['difference_reason'] ?: 'Price/Qty Discrepancy') : 'Exact Match (Balanced)' ?>
                    </div>
                </div>
            </div>

            <!-- Itemized Products Table -->
            <div class="table-wrap">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;" class="col-center">#</th>
                            <th style="text-align: left;">Product Description & SKU</th>
                            <th style="width: 110px;" class="col-center">Batch / Expiry</th>
                            <th style="width: 70px;" class="col-center">Qty Received</th>
                            <th style="width: 105px;" class="col-right">Unit Cost (LKR)</th>
                            <th style="width: 115px;" class="col-right">Line Total (LKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $items = $grn['items'] ?? [];
                        if (empty($items)): 
                        ?>
                            <tr>
                                <td colspan="6" class="col-center" style="padding: 24px; color: #94a3b8;">No line items found for this Goods Received Note.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $idx => $it): ?>
                                <tr>
                                    <td class="col-center font-mono" style="font-weight: 700; color: #64748b;"><?= $idx + 1 ?></td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 8.5pt;"><?= htmlspecialchars($it['product_name']) ?></div>
                                        <div style="font-size: 7.2pt; color: #64748b;" class="font-mono">
                                            SKU: <?= htmlspecialchars($it['sku'] ?? '—') ?>
                                            <?= !empty($it['barcode']) ? ' • Barcode: ' . htmlspecialchars($it['barcode']) : '' ?>
                                        </div>
                                    </td>
                                    <td class="col-center font-mono" style="font-size: 7.4pt; color: #475569;">
                                        <?php 
                                        $hasBatch = !empty($it['batch_number']);
                                        $hasExp = !empty($it['expiry_date']) && $it['expiry_date'] !== '0000-00-00';
                                        if ($hasBatch || $hasExp):
                                        ?>
                                            <?= $hasBatch ? 'B: ' . htmlspecialchars($it['batch_number']) : '' ?>
                                            <?= ($hasBatch && $hasExp) ? '<br>' : '' ?>
                                            <?= $hasExp ? 'Exp: ' . date('d M Y', strtotime($it['expiry_date'])) : '' ?>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-center font-mono" style="font-weight: 800; font-size: 8.5pt; color: #0f172a;"><?= number_format((float)$it['quantity'], 2) ?></td>
                                    <td class="col-right font-mono"><?= number_format((float)$it['unit_cost'], 2) ?></td>
                                    <td class="col-right font-mono" style="font-weight: 800; color: #0f172a;"><?= number_format((float)$it['line_total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Notes & Financial Variance Summary -->
            <div class="bottom-section avoid-break">
                <div class="notes-card">
                    <div class="notes-card-title">RECEIVING NOTES & REMARKS:</div>
                    <div><?= !empty($grn['notes']) ? nl2br(htmlspecialchars($grn['notes'])) : 'Goods physically received, inspected for carton integrity, quantities verified, and stock credited to inventory.' ?></div>
                    
                    <?php if ($hasDiff): ?>
                        <div style="margin-top: 8px; padding: 6px 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; font-size: 7.4pt; color: #92400e; line-height: 1.35;">
                            <strong>Variance Note:</strong> Invoice amount differs by LKR <?= number_format(abs($diff), 2) ?> (<?= htmlspecialchars($grn['difference_reason']) ?>).
                        </div>
                    <?php endif; ?>
                </div>

                <div class="totals-card">
                    <div class="total-row">
                        <span>Actual Received Total:</span>
                        <strong class="font-mono"><?= number_format((float)$grn['actual_grn_total'], 2) ?></strong>
                    </div>
                    <div class="total-row">
                        <span>Supplier Invoice Amount:</span>
                        <strong class="font-mono"><?= number_format((float)$grn['supplier_invoice_amount'], 2) ?></strong>
                    </div>
                    <div class="total-row">
                        <span>Invoice Variance:</span>
                        <strong class="font-mono" style="color: <?= $hasDiff ? '#b91c1c' : '#15803d' ?>;"><?= ($diff > 0 ? '+' : '') . number_format($diff, 2) ?></strong>
                    </div>
                    <div class="total-grand">
                        <span>NET PAYABLE (LKR):</span>
                        <strong class="font-mono"><?= number_format((float)$grn['actual_grn_total'], 2) ?></strong>
                    </div>
                    <?php if (!empty($grn['payment_status']) && $grn['payment_status'] !== 'unpaid'): ?>
                        <div class="total-row" style="margin-top: 6px; padding-top: 4px; border-top: 1px dashed #cbd5e1; font-size: 7.5pt;">
                            <span>Payment Status:</span>
                            <strong style="text-transform: uppercase; color: <?= $grn['payment_status'] === 'paid' ? '#15803d' : '#b45309' ?>;"><?= htmlspecialchars($grn['payment_status']) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="avoid-break">
            <!-- Signatures Section -->
            <div class="signatures-grid">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Received By (Stores)</div>
                    <div class="sig-sub"><?= htmlspecialchars($grn['creator_name']) ?></div>
                </div>

                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Inspected & Authorized</div>
                    <div class="sig-sub">
                        <?= !empty($grn['approver_name']) ? htmlspecialchars($grn['approver_name']) : ($grn['status'] === 'Approved' ? 'Authorized Officer' : 'Pending Verification') ?>
                    </div>
                </div>

                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Supplier / Carrier</div>
                    <div class="sig-sub">Authorized Signature & Stamp</div>
                </div>
            </div>

            <!-- Document Footer with Developer Credits -->
            <div class="doc-footer">
                <div class="footer-left">
                    Official Goods Received Note • <?= htmlspecialchars($businessName) ?>
                </div>
                <div class="footer-center">
                    Powered by Curtiss POS • <a href="https://www.trycurtiss.com" target="_blank" rel="noopener">www.trycurtiss.com</a>
                </div>
                <div class="footer-right">
                    Printed: <?= date('d M Y, h:i A') ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportToPDF() {
            window.print();
        }

        <?php if ($autoPrint): ?>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
        <?php endif; ?>
    </script>
</body>
</html>
