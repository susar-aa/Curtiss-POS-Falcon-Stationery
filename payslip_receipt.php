<?php
/**
 * Curtiss POS - 88mm Thermal Employee Payslip Receipt View
 * Print-ready receipt for Thermal POS Printers (88mm paper width / 80mm printable width).
 * Supports:
 * - Single employee payslip print
 * - Batch print for all employees in a period (Combined Document)
 * - Sequential print with Auto-Cut after each employee slip
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Payroll.php';
require_once __DIR__ . '/core/Settings.php';

Auth::requireLogin();
if (Auth::isSpecialUser()) {
    header('Location: dashboard');
    exit;
}

if (!Auth::hasPermission('payroll.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
    die("Access denied: Insufficient privileges to view payslips.");
}

$periodId = (int)($_GET['period_id'] ?? 0);
$entryId  = (int)($_GET['entry_id'] ?? 0);
$printAll = (int)($_GET['all'] ?? 0) === 1;
$autoCut  = (int)($_GET['autocut'] ?? 0) === 1;
$silent   = (int)($_GET['silent'] ?? 0) === 1;

$payslips = [];

if ($printAll && $periodId > 0) {
    $payslips = Payroll::getPeriodPayslips($periodId);
} elseif ($entryId > 0) {
    $ps = Payroll::getPayslip($entryId);
    if ($ps) {
        $payslips = [$ps];
    }
} elseif ($periodId > 0) {
    // Default to all payslips in that period if only period_id provided
    $payslips = Payroll::getPeriodPayslips($periodId);
}

if (empty($payslips)) {
    die("Payslip record(s) not found for the requested criteria.");
}

$companyInfo = Settings::getCompanyInfo();
$companyLogo = $companyInfo['logo'];
$businessName = $companyInfo['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?= count($payslips) === 1 ? htmlspecialchars($payslips[0]['employee_code'] . ' ' . $payslips[0]['full_name']) : 'Batch (' . count($payslips) . ' Employees)' ?></title>
    <style>
        @page {
            size: 88mm auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace, sans-serif;
            width: 80mm;
            margin: 0 auto;
            padding: 10px 5px;
            font-size: 11.5px;
            color: #000;
            background: #fff;
            line-height: 1.35;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-bottom: 3px double #000; margin: 7px 0; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 3px 0;
        }
        th, td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-top: 8px;
            margin-bottom: 4px;
            letter-spacing: 0.03em;
        }
        .item-detail-row {
            font-size: 10px;
            color: #111;
            padding-left: 6px;
        }
        .payslip-ticket {
            page-break-after: always;
            break-after: page;
            margin-bottom: 25px;
            padding-bottom: 5mm;
        }
        .payslip-ticket:last-child {
            page-break-after: avoid;
            break-after: avoid;
            margin-bottom: 0;
        }
        .cut-feed-space {
            height: 20mm;
            text-align: center;
            padding-top: 8px;
            font-size: 8.5px;
            color: #888;
        }
        .btn-print-box {
            margin-bottom: 14px;
            text-align: center;
        }
        .btn-print {
            background: #0f172a;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            font-family: inherit;
        }
        @media print {
            .btn-print-box { display: none !important; }
            body { padding: 0; width: 100%; margin: 0; }
            .payslip-ticket {
                page-break-after: always !important;
                break-after: page !important;
                margin-bottom: 0 !important;
                padding-bottom: 15mm !important;
            }
            .payslip-ticket:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .cut-feed-space {
                height: 20mm !important;
            }
        }
    </style>
</head>
<body>
    <div class="btn-print-box">
        <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
            <button class="btn-print" onclick="window.print()">
                🖨️ Print Batch (Single Job)
            </button>
            <?php if (count($payslips) > 1): ?>
            <button class="btn-print" onclick="printAllWithAutoCut()" style="background: #16a34a;">
                ✂️ Auto-Cut Each Slip (Sequential)
            </button>
            <?php endif; ?>
        </div>
        <div id="seqProgressBox" style="display: none; margin-top: 8px; font-weight: bold; color: #16a34a; font-size: 11px; background: #dcfce7; padding: 6px 14px; border-radius: 999px;">
            Preparing sequential print...
        </div>
        <?php if (count($payslips) > 1): ?>
        <div style="font-size: 10px; color: #555; margin-top: 6px; line-height: 1.35;">
            Tip: <strong>"Auto-Cut Each Slip"</strong> sends each employee slip as a separate print job so thermal printer cuts after each slip automatically.
        </div>
        <?php endif; ?>
    </div>

    <?php foreach ($payslips as $index => $p): 
        $pBranch = Settings::getBranchInfo(!empty($p['branch_id']) ? (int)$p['branch_id'] : 1);
    ?>
    <div class="payslip-ticket" id="ticket_<?= (int)$p['id'] ?>">
        <!-- HEADER -->
        <div class="text-center">
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/' . $companyLogo)): ?>
                <div>
                    <img src="<?= htmlspecialchars($companyLogo) ?>" alt="Logo" style="max-height: 42px; max-width: 150px; object-fit: contain; margin-bottom: 4px;">
                </div>
            <?php endif; ?>
            <div style="font-size: 15px; font-weight: bold;"><?= htmlspecialchars($businessName) ?></div>
            <div style="font-size: 11px;"><?= htmlspecialchars($pBranch['branch_name']) ?><?= !empty($pBranch['branch_phone']) ? ' &bull; ' . htmlspecialchars($pBranch['branch_phone']) : '' ?></div>
            <div style="font-size: 12px; font-weight: bold; margin-top: 4px; letter-spacing: 0.05em; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 2px 0;">
                SALARY PAYSLIP
            </div>
            <div style="font-size: 10.5px; margin-top: 3px; font-weight: bold;"><?= htmlspecialchars($p['period_code']) ?> &bull; <?= htmlspecialchars($p['period_name']) ?></div>
            <div style="font-size: 10px; color: #333;">Period: <?= htmlspecialchars($p['period_start']) ?> to <?= htmlspecialchars($p['period_end']) ?></div>
        </div>
        <div class="double-divider"></div>

        <!-- EMPLOYEE PARTICULARS -->
        <table>
            <tr>
                <td style="width: 40%;">Employee Name:</td>
                <td class="text-right fw-bold"><?= htmlspecialchars($p['full_name']) ?></td>
            </tr>
            <tr>
                <td>Employee Code:</td>
                <td class="text-right fw-bold"><?= htmlspecialchars($p['employee_code']) ?></td>
            </tr>
            <?php if (!empty($p['job_role'])): ?>
            <tr>
                <td>Designation:</td>
                <td class="text-right"><?= htmlspecialchars($p['job_role']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($p['nic'])): ?>
            <tr>
                <td>NIC Number:</td>
                <td class="text-right"><?= htmlspecialchars($p['nic']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Bank / Payout:</td>
                <td class="text-right">
                    <?= htmlspecialchars($p['bank_name'] ?: 'Cash') ?><?= !empty($p['bank_account_no']) ? ' (' . htmlspecialchars($p['bank_account_no']) . ')' : '' ?>
                </td>
            </tr>
            <tr>
                <td>Slip Status:</td>
                <td class="text-right fw-bold"><?= strtoupper($p['status'] ?? 'DRAFT') ?></td>
            </tr>
        </table>
        <div class="divider"></div>

        <!-- SALES PERFORMANCE & COMMISSIONS (IF RECORDED) -->
        <?php if ((float)($p['period_sales'] ?? 0) > 0 || (float)($p['commissions'] ?? 0) > 0): ?>
        <div class="section-title">Sales Performance</div>
        <table>
            <tr>
                <td>Covered Period Sales:</td>
                <td class="text-right fw-bold">LKR <?= number_format((float)($p['period_sales'] ?? 0), 2) ?></td>
            </tr>
            <?php if (!empty($p['sales_count'])): ?>
            <tr>
                <td>Completed Invoices:</td>
                <td class="text-right"><?= (int)$p['sales_count'] ?> sales</td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Earned Commission:</td>
                <td class="text-right fw-bold">LKR <?= number_format((float)($p['commissions'] ?? 0), 2) ?></td>
            </tr>
        </table>
        <div class="divider"></div>
        <?php endif; ?>

        <!-- EARNINGS BREAKDOWN -->
        <div class="section-title">Earnings Breakdown</div>
        <table>
            <tr>
                <td>Basic Salary:</td>
                <td class="text-right">LKR <?= number_format((float)$p['basic_salary'], 2) ?></td>
            </tr>
            <?php if ((float)$p['commissions'] > 0 && (float)($p['period_sales'] ?? 0) == 0): ?>
            <tr>
                <td>Sales Commission:</td>
                <td class="text-right">LKR <?= number_format((float)$p['commissions'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$p['overtime_amount'] > 0): ?>
            <tr>
                <td>Overtime (OT):</td>
                <td class="text-right">LKR <?= number_format((float)$p['overtime_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$p['bonuses'] > 0): ?>
            <tr>
                <td>Bonuses / Incentives:</td>
                <td class="text-right">LKR <?= number_format((float)$p['bonuses'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$p['allowances'] > 0): ?>
            <tr>
                <td>Allowances:</td>
                <td class="text-right">LKR <?= number_format((float)$p['allowances'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="fw-bold" style="border-top: 1px dashed #000;">
                <td>GROSS EARNINGS:</td>
                <td class="text-right">LKR <?= number_format((float)$p['gross_salary'], 2) ?></td>
            </tr>
        </table>
        <div class="divider"></div>

        <!-- DEDUCTIONS BREAKDOWN -->
        <div class="section-title">Deductions Breakdown</div>
        <table>
            <tr>
                <td>Employee EPF (<?= number_format((float)$p['employee_epf_rate'], 0) ?>%):</td>
                <td class="text-right">- LKR <?= number_format((float)$p['employee_epf_amount'], 2) ?></td>
            </tr>
            <?php if ((float)$p['salary_advances_deducted'] > 0): ?>
            <tr>
                <td>Salary Advance Deducted:</td>
                <td class="text-right">- LKR <?= number_format((float)$p['salary_advances_deducted'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$p['other_deductions'] > 0): ?>
            <tr>
                <td>Other Deductions:</td>
                <td class="text-right">- LKR <?= number_format((float)$p['other_deductions'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="fw-bold" style="border-top: 1px dashed #000;">
                <td>TOTAL DEDUCTIONS:</td>
                <td class="text-right">- LKR <?= number_format((float)$p['total_deductions'], 2) ?></td>
            </tr>
        </table>
        <div class="double-divider"></div>

        <!-- NET SALARY -->
        <table style="font-size: 13px; font-weight: bold; margin: 4px 0;">
            <tr>
                <td>NET TAKE-HOME PAY:</td>
                <td class="text-right">LKR <?= number_format((float)$p['net_salary'], 2) ?></td>
            </tr>
        </table>
        <div class="double-divider"></div>

        <!-- EMPLOYER STATUTORY CONTRIBUTIONS (NOT DEDUCTED) -->
        <div class="section-title">Employer Contributions (Co. Paid)</div>
        <table>
            <tr>
                <td>Employer EPF (<?= number_format((float)$p['employer_epf_rate'], 0) ?>%):</td>
                <td class="text-right">LKR <?= number_format((float)$p['employer_epf_amount'], 2) ?></td>
            </tr>
            <tr>
                <td>Employer ETF (<?= number_format((float)$p['employer_etf_rate'], 0) ?>%):</td>
                <td class="text-right">LKR <?= number_format((float)$p['employer_etf_amount'], 2) ?></td>
            </tr>
            <tr class="fw-bold" style="border-top: 1px dashed #000;">
                <td>Total EPF to Central Bank (20%):</td>
                <td class="text-right">LKR <?= number_format((float)$p['employee_epf_amount'] + (float)$p['employer_epf_amount'], 2) ?></td>
            </tr>
            <tr>
                <td>Total Statutory (EPF+ETF 23%):</td>
                <td class="text-right">LKR <?= number_format((float)$p['employee_epf_amount'] + (float)$p['employer_epf_amount'] + (float)$p['employer_etf_amount'], 2) ?></td>
            </tr>
        </table>
        <div class="divider"></div>

        <!-- ADVANCE RECOVERY & REMAINING ADVANCE BALANCE -->
        <?php if ((float)$p['salary_advances_deducted'] > 0 || (float)($p['remaining_advance_balance'] ?? 0) > 0): ?>
        <div class="section-title">Advance Account Status</div>
        <table>
            <tr>
                <td>Advance Recovered This Pay:</td>
                <td class="text-right">LKR <?= number_format((float)$p['salary_advances_deducted'], 2) ?></td>
            </tr>
            <tr>
                <td>Remaining Advance Balance:</td>
                <td class="text-right fw-bold">LKR <?= number_format((float)($p['remaining_advance_balance'] ?? 0), 2) ?></td>
            </tr>
        </table>
        <div class="divider"></div>
        <?php endif; ?>

        <!-- PAYMENT DISBURSEMENT / SETTLEMENT HISTORY -->
        <?php if (!empty($p['payments'])): ?>
        <div class="section-title">Payment Settlement</div>
        <table>
            <?php foreach ($p['payments'] as $pmt): ?>
            <tr>
                <td><?= htmlspecialchars($pmt['payment_date']) ?> [<?= htmlspecialchars($pmt['payment_method']) ?>]:</td>
                <td class="text-right">LKR <?= number_format((float)$pmt['amount'], 2) ?></td>
            </tr>
            <?php if (!empty($pmt['cheque_number'])): ?>
            <tr class="item-detail-row">
                <td colspan="2">Cheque #: <?= htmlspecialchars($pmt['cheque_number']) ?> (Date: <?= htmlspecialchars($pmt['cheque_date']) ?>)</td>
            </tr>
            <?php elseif (!empty($pmt['reference_no'])): ?>
            <tr class="item-detail-row">
                <td colspan="2">Ref: <?= htmlspecialchars($pmt['reference_no']) ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            <tr class="fw-bold" style="border-top: 1px dashed #000;">
                <td>Total Disbursed:</td>
                <td class="text-right">LKR <?= number_format((float)$p['paid_amount'], 2) ?></td>
            </tr>
            <?php if ((float)$p['outstanding_amount'] > 0): ?>
            <tr class="fw-bold">
                <td>Remaining Payable:</td>
                <td class="text-right">LKR <?= number_format((float)$p['outstanding_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
        </table>
        <div class="divider"></div>
        <?php endif; ?>

        <!-- SIGNATURES -->
        <div style="margin-top: 25px;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%; text-align: center;">
                        <div style="border-top: 1px solid #000; margin: 0 4px; padding-top: 3px; font-size: 10px;">
                            Prepared By
                        </div>
                    </td>
                    <td style="width: 50%; text-align: center;">
                        <div style="border-top: 1px solid #000; margin: 0 4px; padding-top: 3px; font-size: 10px;">
                            Approved By
                        </div>
                    </td>
                </tr>
            </table>
            <div style="margin-top: 24px; text-align: center;">
                <div style="border-top: 1px solid #000; margin: 0 20px; padding-top: 3px; font-size: 10px;">
                    Employee Signature & Date
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="double-divider" style="margin-top: 12px;"></div>
        <div class="text-center" style="font-size: 9.5px; color: #222;">
            <div>Printed on <?= date('Y-m-d H:i:s') ?></div>
            <div>Curtiss POS &bull; Automated Payroll System</div>
        </div>

        <!-- FEED CLEARANCE & TEAR/CUT LINE -->
        <div class="cut-feed-space">
            ---------------- ✂ CUT HERE ----------------
        </div>
    </div>
    <?php endforeach; ?>

    <script>
        const entryIds = <?= json_encode(array_values(array_filter(array_map(function($x) { return (int)($x['id'] ?? 0); }, $payslips)))) ?>;
        const autoCutRequested = <?= $autoCut ? 'true' : 'false' ?>;
        const silentMode = <?= $silent ? 'true' : 'false' ?>;
        let isSequentialPrinting = false;

        async function printAllWithAutoCut() {
            if (isSequentialPrinting || !entryIds || entryIds.length === 0) return;
            isSequentialPrinting = true;

            const progressBox = document.getElementById('seqProgressBox');
            if (progressBox) progressBox.style.display = 'inline-block';

            for (let i = 0; i < entryIds.length; i++) {
                const eid = entryIds[i];
                if (progressBox) {
                    progressBox.innerText = `Printing & cutting slip ${i + 1} of ${entryIds.length}...`;
                }
                await printSingleSlipFrame(eid);
            }

            if (progressBox) {
                progressBox.innerText = `✓ All ${entryIds.length} payslips printed and cut!`;
                setTimeout(() => { progressBox.style.display = 'none'; }, 4000);
            }
            isSequentialPrinting = false;
        }

        function printSingleSlipFrame(entryId) {
            return new Promise((resolve) => {
                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.src = `payslip_receipt.php?entry_id=${entryId}&silent=1`;
                document.body.appendChild(iframe);

                iframe.onload = () => {
                    setTimeout(() => {
                        try {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        } catch (e) {
                            console.error("Print error:", e);
                        }
                        setTimeout(() => {
                            try { document.body.removeChild(iframe); } catch (e) {}
                            resolve();
                        }, 1200);
                    }, 400);
                };
            });
        }

        window.addEventListener('load', () => {
            if (silentMode) {
                // In silent iframe mode, parent triggers print
                return;
            }
            if (autoCutRequested && entryIds.length > 1) {
                setTimeout(() => {
                    printAllWithAutoCut();
                }, 400);
            } else if (!autoCutRequested) {
                setTimeout(() => {
                    window.print();
                }, 400);
            }
        });
    </script>
</body>
</html>
