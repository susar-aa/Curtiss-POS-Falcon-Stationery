<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Supplier.php';

function getSupplierActivityLedger(int $supplierId): array
{
    $activities = [];

    try {
        // 1. Purchase Orders
        $pos = Database::fetchAll("
            SELECT id, po_number AS ref_no, po_date AS activity_date, total_amount AS amount, status, notes, created_at
            FROM purchase_orders
            WHERE supplier_id = :id
        ", [':id' => $supplierId]);
        foreach ($pos as $po) {
            $activities[] = [
                'type' => 'PO',
                'title' => 'Purchase Order #' . $po['ref_no'],
                'ref_no' => $po['ref_no'],
                'date' => $po['activity_date'],
                'amount' => (float)$po['amount'],
                'status' => $po['status'],
                'details' => 'Status: ' . ucfirst($po['status']) . ($po['notes'] ? " - {$po['notes']}" : ''),
                'created_at' => $po['created_at']
            ];
        }

        // 2. Goods Received Notes (GRN)
        $grns = Database::fetchAll("
            SELECT id, grn_number AS ref_no, supplier_invoice_no, received_date AS activity_date, actual_grn_total AS amount, paid_amount, payment_status, status, created_at
            FROM grns
            WHERE supplier_id = :id
        ", [':id' => $supplierId]);
        foreach ($grns as $g) {
            $due = round((float)$g['amount'] - (float)$g['paid_amount'], 2);
            $activities[] = [
                'type' => 'GRN',
                'title' => 'Goods Received Note #' . $g['ref_no'],
                'ref_no' => $g['ref_no'],
                'date' => $g['activity_date'],
                'amount' => (float)$g['amount'],
                'status' => $g['status'],
                'details' => "Invoice: " . ($g['supplier_invoice_no'] ?: 'N/A') . " | Paid: LKR " . number_format($g['paid_amount'], 2) . " | Due: LKR " . number_format($due, 2) . " [{$g['payment_status']}]",
                'created_at' => $g['created_at']
            ];
        }

        // 3. Supplier Payments
        $payments = Database::fetchAll("
            SELECT sp.id, sp.payment_no AS ref_no, sp.payment_date AS activity_date, sp.payment_method, sp.total_amount AS amount, sp.bank_name, sp.reference_no, sp.notes, sp.created_at, sc.cheque_number
            FROM supplier_payments sp
            LEFT JOIN supplier_cheques sc ON sc.supplier_payment_id = sp.id
            WHERE sp.supplier_id = :id
        ", [':id' => $supplierId]);
        foreach ($payments as $p) {
            $chqText = $p['cheque_number'] ? " (Cheque #{$p['cheque_number']})" : '';
            $activities[] = [
                'type' => 'PAYMENT',
                'title' => 'Supplier Payment #' . $p['ref_no'],
                'ref_no' => $p['ref_no'],
                'date' => $p['activity_date'],
                'amount' => (float)$p['amount'],
                'status' => 'completed',
                'details' => "Method: {$p['payment_method']}{$chqText}" . ($p['bank_name'] ? " | Bank: {$p['bank_name']}" : '') . ($p['notes'] ? " - {$p['notes']}" : ''),
                'created_at' => $p['created_at']
            ];
        }

        // 4. Supplier Opening Balances
        $obs = Database::fetchAll("
            SELECT ob.*, je.entry_number AS journal_number
            FROM supplier_opening_balances ob
            LEFT JOIN journal_entries je ON ob.journal_entry_id = je.id
            WHERE ob.supplier_id = :id
        ", [':id' => $supplierId]);
        foreach ($obs as $ob) {
            $typeLabel = ($ob['balance_type'] === 'owe_supplier') ? 'We Owe Supplier (Payable)' : 'Supplier Owes Us (Credit)';
            $activities[] = [
                'type' => 'OPENING_BALANCE',
                'title' => 'Opening Balance #' . $ob['id'] . ' (' . $typeLabel . ')',
                'ref_no' => $ob['journal_number'] ?: ('OB-#' . $ob['id']),
                'date' => $ob['entry_date'],
                'amount' => (float)$ob['amount'],
                'status' => $ob['status'],
                'details' => "Status: " . ucfirst($ob['status']) . " | Journal #: " . ($ob['journal_number'] ?: 'N/A') . ($ob['notes'] ? " - {$ob['notes']}" : ''),
                'created_at' => $ob['created_at']
            ];
        }

        // 5. Supplier Returns
        $returns = Database::fetchAll("
            SELECT id, return_number AS ref_no, return_date AS activity_date, total_return_amount AS amount, applied_to_payable, credit_amount, status, created_at
            FROM supplier_returns
            WHERE supplier_id = :id
        ", [':id' => $supplierId]);
        foreach ($returns as $r) {
            $activities[] = [
                'type' => 'RETURN',
                'title' => 'Supplier Return #' . $r['ref_no'],
                'ref_no' => $r['ref_no'],
                'date' => $r['activity_date'],
                'amount' => (float)$r['amount'],
                'status' => $r['status'],
                'details' => "Applied to Payable: LKR " . number_format($r['applied_to_payable'], 2) . " | Credit Created: LKR " . number_format($r['credit_amount'], 2),
                'created_at' => $r['created_at']
            ];
        }

        // Sort activities by date DESC, then created_at DESC
        usort($activities, function ($a, $b) {
            $cmp = strcmp($b['date'], $a['date']);
            if ($cmp === 0) {
                return strcmp($b['created_at'], $a['created_at']);
            }
            return $cmp;
        });

    } catch (Throwable $e) {
        Logger::error("Failed to fetch supplier activity ledger for #{$supplierId}: " . $e->getMessage(), $e);
    }

    return $activities;
}

$suppliers = Supplier::all(true);
if (!empty($suppliers[0])) {
    $sId = (int)$suppliers[0]['id'];
    $ledger = getSupplierActivityLedger($sId);
    echo "Ledger items count for Supplier #{$sId}: " . count($ledger) . "\n";
    print_r(array_slice($ledger, 0, 5));
}
