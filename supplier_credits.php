<?php
/**
 * Curtiss POS - Supplier Credit Balances & Settlement UI (Stage 15)
 * Shows active supplier credits originating from over-returns,
 * and handles settlement via bank refund, cash refund, or future GRN offset.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/SupplierReturnEngine.php';
require_once __DIR__ . '/core/Banking.php';

Auth::requireLogin();
if (!Auth::hasPermission('manage_supplier_credits') && !Auth::hasPermission('view_supplier_returns')) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? 1);

// Fetch supplier credits
$credits = SupplierReturnEngine::getSupplierCredits(['branch_id' => $branchId]);

// Fetch real bank accounts for bank refund
$bankAccounts = Banking::getBankAccounts($branchId, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Credits & Settlements - Curtiss POS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .badge { padding: 4px 10px; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-settled { background: #f1f5f9; color: #64748b; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); }
        .modal-content { background: #fff; margin: 5% auto; padding: 24px; border-radius: 12px; width: 90%; max-width: 600px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div class="topbar-title">
                <h1>Supplier Credits & Receivables</h1>
                <p class="text-muted">Manage supplier credit balances arising from returns and settle via bank/cash refund or future GRN offset</p>
            </div>
        </header>

        <div class="content-container" style="padding: 24px;">
            <div class="card" style="background: #fff; border-radius: 12px; padding: 20px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; color: #1e293b;">Active & Settled Supplier Credits</h3>

                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f1f5f9; text-align: left; font-size: 0.85rem; color: #475569;">
                                <th style="padding: 10px;">Credit #</th>
                                <th style="padding: 10px;">Date</th>
                                <th style="padding: 10px;">Supplier</th>
                                <th style="padding: 10px;">Return Ref</th>
                                <th style="padding: 10px; text-align: right;">Total Credit</th>
                                <th style="padding: 10px; text-align: right;">Remaining Credit</th>
                                <th style="padding: 10px; text-align: center;">Status</th>
                                <th style="padding: 10px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($credits)): ?>
                                <tr><td colspan="8" style="padding: 20px; text-align: center; color: #94a3b8;">No supplier credit records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($credits as $c): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 10px; font-weight: 700; color: #2563eb;"><?= htmlspecialchars($c['credit_number']) ?></td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($c['credit_date']) ?></td>
                                        <td style="padding: 10px; font-weight: 600;"><?= htmlspecialchars($c['supplier_name']) ?></td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($c['return_number'] ?? 'Direct') ?></td>
                                        <td style="padding: 10px; text-align: right; font-weight: 700;">LKR <?= number_format($c['total_credit_amount'], 2) ?></td>
                                        <td style="padding: 10px; text-align: right; font-weight: 700; color: <?= $c['remaining_credit_amount'] > 0 ? '#166534' : '#64748b' ?>;">
                                            LKR <?= number_format($c['remaining_credit_amount'], 2) ?>
                                        </td>
                                        <td style="padding: 10px; text-align: center;">
                                            <?php if ($c['status'] === 'active'): ?>
                                                <span class="badge badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-settled">Fully Settled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px; text-align: center;">
                                            <?php if ($c['status'] === 'active' && $c['remaining_credit_amount'] > 0): ?>
                                                <button class="btn btn-sm btn-primary" onclick='openSettleModal(<?= json_encode($c) ?>)' style="padding: 6px 14px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                                                    Settle Credit
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 0.85rem;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SETTLE CREDIT MODAL -->
    <div id="settleModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                <h3 id="settleModalTitle" style="margin: 0;">Settle Supplier Credit</h3>
                <button onclick="closeModal('settleModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>

            <form id="settleForm" onsubmit="handleSettleSubmit(event)">
                <input type="hidden" id="settleCreditId">

                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                    <div style="font-weight: 600; color: #1e293b;" id="settleSupplierName">Supplier: -</div>
                    <div style="font-size: 0.875rem; color: #64748b;">Remaining Credit Balance: <strong id="settleRemBalance" style="color: #166534;">LKR 0.00</strong></div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600;">Settlement Method *</label>
                    <select id="settlementType" class="form-control" onchange="toggleSettlementFields(this.value)" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                        <option value="bank_refund">Bank Refund (Deposit into Company Bank Account)</option>
                        <option value="cash_refund">Cash Refund (Received in POS Drawer)</option>
                        <option value="future_grn_offset">Future GRN Offset (Apply to Unpaid GRN)</option>
                    </select>
                </div>

                <!-- BANK REFUND FIELD -->
                <div id="fieldBankAcc" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600;">Target Bank Account *</label>
                    <select id="bankAccountId" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                        <option value="">-- Choose Bank Account --</option>
                        <?php foreach ($bankAccounts as $ba): ?>
                            <option value="<?= $ba['id'] ?>"><?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- FUTURE GRN OFFSET FIELD -->
                <div id="fieldTargetGrn" style="display: none; margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600;">Target Unpaid GRN *</label>
                    <select id="targetGrnId" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                        <option value="">-- Choose Target GRN --</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label class="form-label" style="font-weight: 600;">Settlement Amount (LKR) *</label>
                        <input type="number" step="0.01" min="0.01" id="settleAmount" class="form-control" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 700;">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600;">Settlement Date *</label>
                        <input type="date" id="settleDate" class="form-control" value="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600;">Reference No / Slip Number</label>
                    <input type="text" id="settleRef" class="form-control" placeholder="e.g. Deposit slip # / Bank ref" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="form-label" style="font-weight: 600;">Notes</label>
                    <textarea id="settleNotes" class="form-control" rows="2" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;"></textarea>
                </div>

                <div style="text-align: right;">
                    <button type="button" onclick="closeModal('settleModal')" style="padding: 10px 18px; background: #e2e8f0; border: none; border-radius: 8px; margin-right: 8px; cursor: pointer;">Cancel</button>
                    <button type="submit" id="btnSubmitSettle" style="padding: 10px 24px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Confirm Settlement</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedCredit = null;

        async function openSettleModal(c) {
            selectedCredit = c;
            document.getElementById('settleCreditId').value = c.id;
            document.getElementById('settleSupplierName').innerText = `Supplier: ${c.supplier_name} (Credit #${c.credit_number})`;
            
            const rem = parseFloat(c.remaining_credit_amount);
            document.getElementById('settleRemBalance').innerText = `LKR ${rem.toLocaleString('en-US',{minimumFractionDigits:2})}`;
            document.getElementById('settleAmount').value = rem.toFixed(2);
            document.getElementById('settleAmount').max = rem;

            // Load unpaid GRNs for supplier
            const grnSelect = document.getElementById('targetGrnId');
            grnSelect.innerHTML = '<option value="">-- Loading Approved GRNs... --</option>';

            try {
                const res = await fetch(`supplier_payment_api.php?action=get_supplier_outstanding&supplier_id=${c.supplier_id}`);
                const data = await res.json();
                
                if (data.success && data.approved_grns && data.approved_grns.length > 0) {
                    grnSelect.innerHTML = '<option value="">-- Select Target GRN --</option>';
                    data.approved_grns.forEach(g => {
                        grnSelect.innerHTML += `<option value="${g.grn_id}">GRN #${g.grn_number} - Due: LKR ${parseFloat(g.due_amount).toLocaleString('en-US',{minimumFractionDigits:2})}</option>`;
                    });
                } else {
                    grnSelect.innerHTML = '<option value="">No unpaid approved GRNs for this supplier</option>';
                }
            } catch (e) {
                console.error(e);
            }

            toggleSettlementFields('bank_refund');
            document.getElementById('settleModal').style.display = 'block';
        }

        function toggleSettlementFields(type) {
            document.getElementById('fieldBankAcc').style.display = (type === 'bank_refund') ? 'block' : 'none';
            document.getElementById('fieldTargetGrn').style.display = (type === 'future_grn_offset') ? 'block' : 'none';
        }

        async function handleSettleSubmit(e) {
            e.preventDefault();
            const creditId = document.getElementById('settleCreditId').value;
            const type = document.getElementById('settlementType').value;
            const amount = parseFloat(document.getElementById('settleAmount').value) || 0;
            const sDate = document.getElementById('settleDate').value;
            const bankAccId = document.getElementById('bankAccountId').value;
            const targetGrnId = document.getElementById('targetGrnId').value;
            const ref = document.getElementById('settleRef').value;
            const notes = document.getElementById('settleNotes').value;

            if (type === 'bank_refund' && !bankAccId) {
                alert('Please select a valid target bank account.');
                return;
            }

            if (type === 'future_grn_offset' && !targetGrnId) {
                alert('Please select a target unpaid GRN.');
                return;
            }

            const btn = document.getElementById('btnSubmitSettle');
            btn.disabled = true;
            btn.innerText = 'Processing...';

            try {
                const res = await fetch('supplier_return_api.php?action=process_settlement', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        supplier_credit_id: creditId,
                        settlement_type: type,
                        amount: amount,
                        settlement_date: sDate,
                        bank_account_id: bankAccId,
                        target_grn_id: targetGrnId,
                        reference_no: ref,
                        notes: notes
                    })
                });

                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.innerText = 'Confirm Settlement';
                }
            } catch (err) {
                console.error(err);
                alert('Failed to process credit settlement.');
                btn.disabled = false;
                btn.innerText = 'Confirm Settlement';
            }
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>
