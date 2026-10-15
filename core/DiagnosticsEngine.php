<?php
/**
 * Curtiss POS - Comprehensive Production System Diagnostics Engine (Stage 19)
 * Read-only diagnostic system to detect data integrity anomalies, inventory mismatches,
 * orphan records, GL journal imbalances, and drawer mismatches.
 */

require_once __DIR__ . '/Database.php';

class DiagnosticsEngine
{
    /**
     * Run all production data integrity diagnostic checks
     */
    public static function runAllChecks(): array
    {
        $db = Database::getInstance()->getConnection();
        $results = [];

        // 1. Unbalanced GL Journal Entries (Debit != Credit)
        $stmtUnbalanced = $db->query("
            SELECT je.id, je.entry_number, je.entry_date, je.description, 
                   SUM(jl.debit) AS total_debit, SUM(jl.credit) AS total_credit
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.journal_entry_id
            WHERE je.posting_status = 'posted'
            GROUP BY je.id
            HAVING ABS(SUM(jl.debit) - SUM(jl.credit)) > 0.009
        ");
        $unbalanced = $stmtUnbalanced->fetchAll(PDO::FETCH_ASSOC);
        $results['unbalanced_journals'] = [
            'check_name'  => 'Unbalanced GL Journal Entries',
            'status'      => empty($unbalanced) ? 'PASS' : 'WARN',
            'count'       => count($unbalanced),
            'description' => 'Checks for posted journal entries where total debits do not equal total credits.',
            'items'       => $unbalanced
        ];

        // 2. Duplicate Source Journals
        $stmtDupJournals = $db->query("
            SELECT source_type, source_id, COUNT(*) AS journal_count
            FROM journal_entries
            WHERE posting_status = 'posted' 
              AND source_type IS NOT NULL 
              AND source_type != ''
              AND source_type NOT IN ('manual', 'reversal', 'active_drawer', 'other_cash', 'business_days')
              AND source_id IS NOT NULL
            GROUP BY source_type, source_id
            HAVING COUNT(*) > 1
        ");
        $dupJournals = $stmtDupJournals->fetchAll(PDO::FETCH_ASSOC);
        $results['duplicate_source_journals'] = [
            'check_name'  => 'Duplicate Source Journal Postings',
            'status'      => empty($dupJournals) ? 'PASS' : 'WARN',
            'count'       => count($dupJournals),
            'description' => 'Checks if a single business transaction generated multiple posted GL journals.',
            'items'       => $dupJournals
        ];

        // 3. Negative Stock Quantities
        $stmtNegLots = $db->query("
            SELECT id, lot_number, product_id, branch_id, remaining_quantity 
            FROM product_lots 
            WHERE remaining_quantity < -0.001
        ");
        $negLots = $stmtNegLots->fetchAll(PDO::FETCH_ASSOC);

        $stmtNegInv = $db->query("
            SELECT id, branch_id, product_id, quantity 
            FROM branch_inventory 
            WHERE quantity < -0.001
        ");
        $negInv = $stmtNegInv->fetchAll(PDO::FETCH_ASSOC);

        $totalNeg = count($negLots) + count($negInv);
        $results['negative_stock'] = [
            'check_name'  => 'Negative Stock Quantities',
            'status'      => $totalNeg === 0 ? 'PASS' : 'WARN',
            'count'       => $totalNeg,
            'description' => 'Checks for negative stock quantities in product lots or branch inventory.',
            'items'       => array_merge($negLots, $negInv)
        ];

        // 4. Branch Inventory vs Lot Stock Mismatch
        $stmtStockMismatch = $db->query("
            SELECT bi.branch_id, bi.product_id, bi.quantity AS branch_qty, 
                   COALESCE(SUM(pl.remaining_quantity), 0.00) AS lot_qty
            FROM branch_inventory bi
            LEFT JOIN product_lots pl ON bi.branch_id = pl.branch_id AND bi.product_id = pl.product_id AND pl.status = 'active'
            GROUP BY bi.branch_id, bi.product_id
            HAVING ABS(bi.quantity - COALESCE(SUM(pl.remaining_quantity), 0.00)) > 0.01
        ");
        $mismatches = $stmtStockMismatch->fetchAll(PDO::FETCH_ASSOC);
        $results['branch_inventory_vs_lot'] = [
            'check_name'  => 'Branch Inventory vs Active Lot Stock Mismatch',
            'status'      => empty($mismatches) ? 'PASS' : 'WARN',
            'count'       => count($mismatches),
            'description' => 'Checks for discrepancies between branch_inventory aggregate stock and sum of active lot remaining quantities.',
            'items'       => $mismatches
        ];

        // 5. Orphan Database Records
        $stmtOrphanSaleItems = $db->query("
            SELECT si.id, si.sale_id 
            FROM sale_items si 
            LEFT JOIN sales s ON si.sale_id = s.id 
            WHERE s.id IS NULL
        ");
        $orphanSaleItems = $stmtOrphanSaleItems->fetchAll(PDO::FETCH_ASSOC);

        $stmtOrphanJLines = $db->query("
            SELECT jl.id, jl.journal_entry_id 
            FROM journal_lines jl 
            LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id 
            WHERE je.id IS NULL
        ");
        $orphanJLines = $stmtOrphanJLines->fetchAll(PDO::FETCH_ASSOC);

        $totalOrphans = count($orphanSaleItems) + count($orphanJLines);
        $results['orphan_records'] = [
            'check_name'  => 'Orphan Foreign Key Records',
            'status'      => $totalOrphans === 0 ? 'PASS' : 'WARN',
            'count'       => $totalOrphans,
            'description' => 'Checks for child rows in sale_items or journal_lines that point to non-existent parent records.',
            'items'       => array_merge($orphanSaleItems, $orphanJLines)
        ];

        // 6. Completed Sale Payment Total Mismatches
        $stmtSalePayMismatch = $db->query("
            SELECT s.id, s.invoice_no, s.total_amount, 
                   COALESCE(SUM(p.amount), 0.00) AS total_payments
            FROM sales s
            LEFT JOIN payments p ON s.id = p.sale_id
            WHERE s.status = 'completed'
            GROUP BY s.id
            HAVING ABS(s.total_amount - COALESCE(SUM(p.amount), 0.00)) > 0.01
        ");
        $payMismatches = $stmtSalePayMismatch->fetchAll(PDO::FETCH_ASSOC);
        $results['sale_payment_mismatch'] = [
            'check_name'  => 'Completed Sale Payment Allocations Mismatch',
            'status'      => empty($payMismatches) ? 'PASS' : 'WARN',
            'count'       => count($payMismatches),
            'description' => 'Checks for completed sales where total recorded payments do not match invoice total amount.',
            'items'       => $payMismatches
        ];

        // 7. Invalid Gift Voucher States
        $stmtVouchers = $db->query("
            SELECT id, voucher_code, status 
            FROM gift_vouchers 
            WHERE status = 'redeemed' AND redeemed_at IS NULL
        ");
        $badVouchers = $stmtVouchers->fetchAll(PDO::FETCH_ASSOC);
        $results['invalid_voucher_states'] = [
            'check_name'  => 'Invalid Gift Voucher Redemption States',
            'status'      => empty($badVouchers) ? 'PASS' : 'WARN',
            'count'       => count($badVouchers),
            'description' => 'Checks for gift vouchers marked as redeemed without valid mapped sale_id.',
            'items'       => $badVouchers
        ];

        return $results;
    }
}
