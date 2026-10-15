<?php
/**
 * Curtiss POS - Supplier Opening Balance Component
 * Handles supplier opening balance entry, double-entry accounting posting,
 * audit trail, duplicate prevention, and strict reversal workflow.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Supplier.php';

class SupplierOpeningBalance
{
    /**
     * Get active posted opening balance for a supplier
     */
    public static function getActiveOpeningBalance(int $supplierId): ?array
    {
        try {
            return Database::fetchOne("
                SELECT 
                    ob.*, 
                    u.full_name AS created_by_name, 
                    ru.full_name AS reversed_by_name,
                    je.entry_number AS journal_number,
                    rje.entry_number AS reversal_journal_number
                FROM supplier_opening_balances ob
                JOIN users u ON ob.created_by = u.id
                LEFT JOIN users ru ON ob.reversed_by = ru.id
                LEFT JOIN journal_entries je ON ob.journal_entry_id = je.id
                LEFT JOIN journal_entries rje ON ob.reversal_journal_id = rje.id
                WHERE ob.supplier_id = :supplier_id AND ob.status = 'posted'
                ORDER BY ob.id DESC
                LIMIT 1
            ", [':supplier_id' => $supplierId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch active opening balance for supplier #{$supplierId}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Get all opening balance history (posted & reversed) for a supplier
     */
    public static function getHistory(int $supplierId): array
    {
        try {
            return Database::fetchAll("
                SELECT 
                    ob.*, 
                    u.full_name AS created_by_name, 
                    ru.full_name AS reversed_by_name,
                    je.entry_number AS journal_number,
                    rje.entry_number AS reversal_journal_number
                FROM supplier_opening_balances ob
                JOIN users u ON ob.created_by = u.id
                LEFT JOIN users ru ON ob.reversed_by = ru.id
                LEFT JOIN journal_entries je ON ob.journal_entry_id = je.id
                LEFT JOIN journal_entries rje ON ob.reversal_journal_id = rje.id
                WHERE ob.supplier_id = :supplier_id
                ORDER BY ob.id DESC
            ", [':supplier_id' => $supplierId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch opening balance history for supplier #{$supplierId}: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Post a new Supplier Opening Balance with Double-Entry Accounting
     */
    public static function postOpeningBalance(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $supplierId  = (int)($data['supplier_id'] ?? 0);
            $amount      = round((float)($data['amount'] ?? 0), 2);
            $balanceType = trim($data['balance_type'] ?? '');
            $entryDate   = !empty($data['entry_date']) ? trim($data['entry_date']) : date('Y-m-d');
            $referenceNo = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
            $notes       = !empty($data['notes']) ? trim($data['notes']) : 'Initial Supplier Opening Balance';

            if ($supplierId <= 0) {
                return ['success' => false, 'message' => 'Valid supplier is required.'];
            }

            if ($amount <= 0.00) {
                return ['success' => false, 'message' => 'Opening balance amount must be greater than zero.'];
            }

            if (!in_array($balanceType, ['owe_supplier', 'supplier_owes_us'])) {
                return ['success' => false, 'message' => 'Invalid balance type. Must be "We Owe Supplier" or "Supplier Owes Us".'];
            }

            $supplier = Supplier::find($supplierId);
            if (!$supplier) {
                return ['success' => false, 'message' => 'Supplier not found.'];
            }

            // Prevent duplicate active opening balances
            $existing = self::getActiveOpeningBalance($supplierId);
            if ($existing) {
                $typeLabel = ($existing['balance_type'] === 'owe_supplier') ? 'We Owe Supplier' : 'Supplier Owes Us';
                return [
                    'success' => false, 
                    'message' => "Supplier '{$supplier['name']}' already has an active posted opening balance of LKR " . number_format($existing['amount'], 2) . " ({$typeLabel}). Unsafe direct editing is prevented. Please reverse the existing opening balance first."
                ];
            }

            $db->beginTransaction();

            // Insert into supplier_opening_balances
            $stmt = $db->prepare("
                INSERT INTO supplier_opening_balances (
                    supplier_id, branch_id, entry_date, balance_type, amount, reference_no, notes, status, created_by
                ) VALUES (
                    :supplier_id, :branch_id, :entry_date, :balance_type, :amount, :reference_no, :notes, 'posted', :created_by
                )
            ");

            $stmt->execute([
                ':supplier_id'  => $supplierId,
                ':branch_id'    => $branchId,
                ':entry_date'   => $entryDate,
                ':balance_type' => $balanceType,
                ':amount'       => $amount,
                ':reference_no' => $referenceNo,
                ':notes'        => $notes,
                ':created_by'   => $userId
            ]);

            $obId = (int)$db->lastInsertId();

            // Post Double-Entry Journal
            $equityAccId   = Accounting::getAccountIdByCode(Accounting::CODE_EQUITY);          // 3010 Opening Balance Equity
            $payableAccId  = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_PAYABLE); // 2020 Supplier Payables
            $creditAccId   = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_CREDIT);  // 1045 Supplier Credit/Receivable

            $supCode = $supplier['supplier_code'] ?: ('SUP-' . $supplierId);
            $descStr = "Supplier Opening Balance ({$supCode} - {$supplier['name']}): " . ($balanceType === 'owe_supplier' ? 'We Owe Supplier (Payable)' : 'Supplier Owes Us (Credit)');

            $journalHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => $entryDate,
                'source_type' => 'opening_balance',
                'source_id'   => $obId,
                'description' => $descStr,
                'created_by'  => $userId
            ];

            if ($balanceType === 'owe_supplier') {
                // We Owe Supplier: Dr 3010 Opening Balance Equity / Cr 2020 Supplier Payables
                $journalLines = [
                    ['account_id' => $equityAccId,  'debit' => $amount, 'credit' => 0.00,   'memo' => "Opening Balance Equity: {$supplier['name']}"],
                    ['account_id' => $payableAccId, 'debit' => 0.00,   'credit' => $amount, 'memo' => "Supplier Payable OB: {$supplier['name']} ({$supCode})"]
                ];
            } else {
                // Supplier Owes Us: Dr 1045 Supplier Credit/Receivable / Cr 3010 Opening Balance Equity
                $journalLines = [
                    ['account_id' => $creditAccId, 'debit' => $amount, 'credit' => 0.00,   'memo' => "Supplier Credit OB: {$supplier['name']} ({$supCode})"],
                    ['account_id' => $equityAccId, 'debit' => 0.00,   'credit' => $amount, 'memo' => "Opening Balance Equity: {$supplier['name']}"]
                ];

                // Also create supplier_credits record for supplier_owes_us
                $crdStmt = $db->prepare("
                    INSERT INTO supplier_credits (
                        supplier_id, branch_id, credit_number, credit_date, total_credit_amount, remaining_credit_amount, status, notes, created_by
                    ) VALUES (
                        :supplier_id, :branch_id, :credit_number, :credit_date, :amount1, :amount2, 'active', :notes, :created_by
                    )
                ");
                $crdStmt->execute([
                    ':supplier_id'   => $supplierId,
                    ':branch_id'     => $branchId,
                    ':credit_number' => "OB-CRD-{$obId}",
                    ':credit_date'   => $entryDate,
                    ':amount1'       => $amount,
                    ':amount2'       => $amount,
                    ':notes'         => "Supplier Opening Balance Credit: " . ($notes ?: 'Migration credit balance'),
                    ':created_by'    => $userId
                ]);
            }

            $jeRes = Accounting::postJournalEntry($journalHeader, $journalLines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Journal posting failed: " . $jeRes['message']];
            }

            // Save Journal Entry ID back to OB record
            $db->prepare("UPDATE supplier_opening_balances SET journal_entry_id = :je_id WHERE id = :id")->execute([
                ':je_id' => $jeRes['journal_id'],
                ':id'    => $obId
            ]);

            $db->commit();

            Audit::log('SUPPLIER_OPENING_BALANCE_POSTED', 'suppliers', (string)$supplierId, null, [
                'ob_id'        => $obId,
                'amount'       => $amount,
                'balance_type' => $balanceType,
                'journal_no'   => $jeRes['entry_number']
            ]);

            return [
                'success'            => true,
                'id'                 => $obId,
                'opening_balance_id' => $obId,
                'journal_number'     => $jeRes['entry_number'],
                'message'            => "Opening balance of LKR " . number_format($amount, 2) . " posted successfully for '{$supplier['name']}' (Journal #{$jeRes['entry_number']})."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            Logger::error("Failed to post supplier opening balance: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to post opening balance: ' . $e->getMessage()];
        }
    }

    /**
     * Reverse an existing posted Supplier Opening Balance with counter accounting entry
     */
    public static function reverseOpeningBalance(int $obId, string $reversalReason, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $obId = (int)$obId;
            $reversalReason = trim($reversalReason);

            if ($obId <= 0) {
                return ['success' => false, 'message' => 'Invalid opening balance ID.'];
            }

            if (empty($reversalReason)) {
                return ['success' => false, 'message' => 'Reversal reason/note is required to perform an auditable correction.'];
            }

            $ob = Database::fetchOne("SELECT * FROM supplier_opening_balances WHERE id = :id FOR UPDATE", [':id' => $obId]);
            if (!$ob) {
                return ['success' => false, 'message' => 'Opening balance record not found.'];
            }

            if ($ob['status'] === 'reversed') {
                return ['success' => false, 'message' => "Opening balance #{$obId} is already reversed."];
            }

            if ((float)($ob['paid_amount'] ?? 0) > 0.001) {
                return [
                    'success' => false, 
                    'message' => "Opening balance cannot be reversed because payments of LKR " . number_format($ob['paid_amount'], 2) . " have already been made against it. Please reverse those payments first."
                ];
            }

            $supplier = Supplier::find((int)$ob['supplier_id']);
            $supName = $supplier['name'] ?? ('Supplier #' . $ob['supplier_id']);

            $db->beginTransaction();

            // Reverse Double-Entry Journal
            $equityAccId  = Accounting::getAccountIdByCode(Accounting::CODE_EQUITY);          // 3010
            $payableAccId = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_PAYABLE); // 2020
            $creditAccId  = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_CREDIT);  // 1045

            $amount = (float)$ob['amount'];
            $balanceType = $ob['balance_type'];

            $journalHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'reversal',
                'source_id'   => $obId,
                'description' => "Reversal of Supplier Opening Balance #{$obId} for {$supName}: {$reversalReason}",
                'created_by'  => $userId
            ];

            if ($balanceType === 'owe_supplier') {
                // Original: Dr 3010 / Cr 2020 -> Reversal: Dr 2020 / Cr 3010
                $journalLines = [
                    ['account_id' => $payableAccId, 'debit' => $amount, 'credit' => 0.00,   'memo' => "Reverse OB Payable: {$supName}"],
                    ['account_id' => $equityAccId,  'debit' => 0.00,   'credit' => $amount, 'memo' => "Reverse OB Equity: {$supName}"]
                ];
            } else {
                // Original: Dr 1045 / Cr 3010 -> Reversal: Dr 3010 / Cr 1045
                $journalLines = [
                    ['account_id' => $equityAccId,  'debit' => $amount, 'credit' => 0.00,   'memo' => "Reverse OB Equity: {$supName}"],
                    ['account_id' => $creditAccId,  'debit' => 0.00,   'credit' => $amount, 'memo' => "Reverse OB Credit: {$supName}"]
                ];

                // Also cancel associated supplier_credits record if exists
                $db->prepare("UPDATE supplier_credits SET status = 'void', notes = CONCAT(COALESCE(notes, ''), ' (Reversed by OB #', :ob_id, ')') WHERE credit_number = :crd_no")->execute([
                    ':ob_id'  => $obId,
                    ':crd_no' => "OB-CRD-{$obId}"
                ]);
            }

            $rJeRes = Accounting::postJournalEntry($journalHeader, $journalLines);
            if (!$rJeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Reversal journal posting failed: " . $rJeRes['message']];
            }

            // Update opening balance status
            $db->prepare("
                UPDATE supplier_opening_balances 
                SET status = 'reversed', reversed_by = :userId, reversed_at = NOW(), reversal_notes = :notes, reversal_journal_id = :rjeId
                WHERE id = :id
            ")->execute([
                ':userId' => $userId,
                ':notes'  => $reversalReason,
                ':rjeId'  => $rJeRes['journal_id'],
                ':id'     => $obId
            ]);

            $db->commit();

            Audit::log('SUPPLIER_OPENING_BALANCE_REVERSED', 'suppliers', (string)$ob['supplier_id'], $ob, [
                'reversal_notes' => $reversalReason,
                'reversal_je'    => $rJeRes['entry_number']
            ]);

            return [
                'success'                 => true,
                'reversal_journal_number' => $rJeRes['entry_number'],
                'message'                 => "Opening balance #{$obId} of LKR " . number_format($amount, 2) . " reversed successfully (Journal #{$rJeRes['entry_number']})."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            Logger::error("Failed to reverse supplier opening balance #{$obId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to reverse opening balance: ' . $e->getMessage()];
        }
    }
}
