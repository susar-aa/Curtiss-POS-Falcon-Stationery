<?php
/**
 * Curtiss POS - Goods Received Note (GRN) Print & PDF Document
 * Fixed A4 Layout (210mm x 297mm) with Vector Crisp Typography
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
$businessName = $branchInfo['company_name'];
$companyLogo = $branchInfo['company_logo'];
$businessPhone = $branchInfo['branch_phone'];
$businessEmail = $branchInfo['branch_email'];
$businessAddress = $branchInfo['branch_address'];

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
        /* ── Standard CSS Reset & A4 Sheet Sizing ── */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            background: #e2e8f0;
            margin: 0;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ── Floating Action Bar (Screen Only) ── */
        .print-toolbar {
            position: fixed;
            top: 14px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 999px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }
        .tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
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
            background: #38bdf8;
            color: #0f172a;
        }
        .tool-btn-primary:hover {
            background: #7dd3fc;
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
            color: #ffffff;
        }
        .tool-btn-ghost:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        /* ── A4 Page Container ── */
        .a4-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 45px auto 20px auto;
            background: #ffffff;
            padding: 16mm 18mm;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        /* ── Typography & Header Layout ── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 14px;
            border-bottom: 2px solid #0f172a;
        }
        .brand-title {
            font-size: 1.45rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }
        .brand-meta {
            font-size: 0.76rem;
            color: #475569;
            line-height: 1.4;
            margin-top: 3px;
        }

        .doc-title-block {
            text-align: right;
        }
        .doc-main-title {
            font-size: 1.45rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .doc-po-number {
            font-family: monospace;
            font-size: 1.05rem;
            font-weight: 800;
            color: #0284c7;
            margin-top: 2px;
        }
        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }
        .status-approved { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-draft { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* ── Two-Column Information Grid ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 14px;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .info-card-title {
            font-size: 0.70rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }
        .info-card-name {
            font-size: 0.90rem;
            font-weight: 800;
            color: #0f172a;
        }
        .info-card-text {
            font-size: 0.74rem;
            color: #334155;
            line-height: 1.4;
            margin-top: 2px;
        }

        /* ── Items Table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .items-table thead th {
            background: #0f172a;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 7px 10px;
            border: none;
        }
        .items-table tbody td {
            padding: 7px 10px;
            font-size: 0.78rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .items-table tbody tr:nth-child(even) {
            background: rgba(248, 250, 252, 0.75);
        }

        /* ── Bottom Terms & Summary Section ── */
        .bottom-section {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 16px;
            margin-top: 16px;
            align-items: flex-start;
        }
        .notes-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.74rem;
            color: #475569;
            line-height: 1.45;
        }
        .totals-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #64748b;
            margin-bottom: 5px;
        }
        .total-row strong {
            font-family: monospace;
            font-size: 0.82rem;
            color: #0f172a;
        }
        .total-grand {
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            margin-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-grand span {
            font-size: 0.88rem;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }
        .total-grand strong {
            font-size: 1.05rem;
            font-weight: 900;
            color: #0f172a;
            font-family: monospace;
        }

        /* ── Signatures Section ── */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            padding-top: 10px;
        }
        .sig-block {
            text-align: center;
        }
        .sig-line {
            border-top: 1px dashed #64748b;
            margin-bottom: 6px;
        }
        .sig-label {
            font-size: 0.72rem;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .sig-sub {
            font-size: 0.68rem;
            color: #64748b;
            margin-top: 2px;
        }

        /* ── Document Footer ── */
        .doc-footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 0.68rem;
            color: #94a3b8;
        }

        /* ── STRICT PRINT RULES FOR CLEAN A4 EXPORT ── */
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-toolbar {
                display: none !important;
            }
            .a4-sheet {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
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
    </div>

    <!-- Official A4 Sheet Document Container -->
    <div class="a4-sheet">
        <div>
            <!-- Header Section -->
            <div class="doc-header">
                <div style="display: flex; align-items: flex-start; gap: 14px;">
                    <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/' . $companyLogo)): ?>
                        <img src="<?= htmlspecialchars($companyLogo) ?>" alt="Logo" style="max-height: 52px; max-width: 140px; object-fit: contain;">
                    <?php endif; ?>
                    <div>
                        <div class="brand-title"><?= htmlspecialchars($businessName) ?></div>
                        <div class="brand-meta">
                            <strong>Receiving Branch:</strong> <?= htmlspecialchars($branchInfo['branch_name']) ?> (<?= htmlspecialchars($branchInfo['branch_code'] ?? 'BR-01') ?>)<br>
                            <?= !empty($businessAddress) ? htmlspecialchars($businessAddress) . '<br>' : '' ?>
                            <?= !empty($businessPhone) ? 'Tel: ' . htmlspecialchars($businessPhone) . ' ' : '' ?>
                            <?= !empty($branchInfo['company_whatsapp']) ? '• WA: ' . htmlspecialchars($branchInfo['company_whatsapp']) . ' ' : '' ?>
                            <?= !empty($businessEmail) ? '• ' . htmlspecialchars($businessEmail) . ' ' : '' ?>
                            <?= !empty($branchInfo['company_website']) ? '• ' . htmlspecialchars($branchInfo['company_website']) : '' ?>
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
                        <div style="font-size: 0.72rem; color: #0284c7; font-weight: 700; margin-top: 3px;">
                            PO Ref: <?= htmlspecialchars($grn['po_number']) ?>
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
                        <strong>Variance Status:</strong> <?= $hasDiff ? htmlspecialchars($grn['difference_reason'] ?: 'Price/Qty Discrepancy') : 'Exact Match (Balanced)' ?>
                    </div>
                </div>
            </div>

            <!-- Itemized Products Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;">#</th>
                        <th style="text-align: left;">Product Description & SKU</th>
                        <th style="width: 70px; text-align: center;">Qty</th>
                        <th style="width: 110px; text-align: right;">Unit Cost (LKR)</th>
                        <th style="width: 120px; text-align: right;">Line Total (LKR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $items = $grn['items'] ?? [];
                    if (empty($items)): 
                    ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 25px; color: #94a3b8;">No line items found for this GRN.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $idx => $it): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: #64748b;"><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.82rem;"><?= htmlspecialchars($it['product_name']) ?></div>
                                    <div style="font-size: 0.68rem; color: #64748b; font-family: monospace;">SKU: <?= htmlspecialchars($it['sku'] ?? '—') ?></div>
                                </td>
                                <td style="text-align: center; font-weight: 700; font-family: monospace; color: #0f172a;"><?= number_format((float)$it['quantity'], 2) ?></td>
                                <td style="text-align: right; font-family: monospace; color: #334155;"><?= number_format((float)$it['unit_cost'], 2) ?></td>
                                <td style="text-align: right; font-weight: 800; font-family: monospace; color: #0f172a;"><?= number_format((float)$it['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Bottom Notes & Financial Variance Summary -->
            <div class="bottom-section">
                <div class="notes-card">
                    <div style="font-size: 0.72rem; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 4px;">RECEIVING NOTES & REMARKS</div>
                    <div><?= !empty($grn['notes']) ? nl2br(htmlspecialchars($grn['notes'])) : 'No additional receiving notes recorded. Goods inspected upon delivery.' ?></div>
                    
                    <?php if ($hasDiff): ?>
                        <div style="margin-top: 8px; padding: 6px 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; font-size: 0.72rem; color: #92400e;">
                            <strong>Variance Note:</strong> Invoice amount differs by LKR <?= number_format(abs($diff), 2) ?> (<?= htmlspecialchars($grn['difference_reason']) ?>).
                        </div>
                    <?php endif; ?>
                </div>

                <div class="totals-card">
                    <div class="total-row">
                        <span>Actual Received Total:</span>
                        <strong><?= number_format((float)$grn['actual_grn_total'], 2) ?></strong>
                    </div>
                    <div class="total-row">
                        <span>Supplier Stated Invoice:</span>
                        <strong><?= number_format((float)$grn['supplier_invoice_amount'], 2) ?></strong>
                    </div>
                    <div class="total-row">
                        <span>Invoice Variance:</span>
                        <strong style="color: <?= $hasDiff ? '#b91c1c' : '#15803d' ?>;"><?= ($diff > 0 ? '+' : '') . number_format($diff, 2) ?></strong>
                    </div>
                    <div class="total-grand">
                        <span>NET PAYABLE</span>
                        <strong>LKR <?= number_format((float)$grn['actual_grn_total'], 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <!-- Signatures Section -->
            <div class="signatures-grid">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Received By (Storekeeper)</div>
                    <div class="sig-sub"><?= htmlspecialchars($grn['creator_name']) ?></div>
                </div>

                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Inspected & Authorized By</div>
                    <div class="sig-sub">
                        <?= !empty($grn['approver_name']) ? htmlspecialchars($grn['approver_name']) : ($grn['status'] === 'Approved' ? 'Authorized Officer' : 'Pending Approval') ?>
                    </div>
                </div>

                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Supplier / Carrier Signature</div>
                    <div class="sig-sub">Authorized Signature & Stamp</div>
                </div>
            </div>

            <!-- Document Footer -->
            <div class="doc-footer">
                <span>System Generated GRN • <?= htmlspecialchars($businessName) ?> POS System</span>
                <span>Printed on: <?= date('d M Y, h:i A') ?></span>
                <span>Page 1 of 1</span>
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
