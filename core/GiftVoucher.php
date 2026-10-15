<?php
/**
 * Curtiss POS - Gift Voucher Management Core Component (Stage 7)
 * Manages gift voucher creation, validation, and single-use redemption logic.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class GiftVoucher
{
    /**
     * Create/Issue a new Gift Voucher (Super Admin, Admin, Manager)
     */
    public static function createVoucher(float $amount, string $expiryDate, ?string $notes = null, ?int $userId = null): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Voucher amount must be greater than zero.'];
        }

        if (empty($expiryDate) || strtotime($expiryDate) < strtotime(date('Y-m-d'))) {
            return ['success' => false, 'message' => 'Expiry date must be today or in the future.'];
        }

        try {
            $userId = $userId ?? ($_SESSION['user_id'] ?? 1);
            $voucherCode = 'GV-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $sql = "
                INSERT INTO gift_vouchers (voucher_code, amount, issue_date, expiry_date, status, created_by, notes)
                VALUES (:voucher_code, :amount, :issue_date, :expiry_date, 'active', :created_by, :notes)
            ";

            Database::execute($sql, [
                ':voucher_code' => $voucherCode,
                ':amount'       => $amount,
                ':issue_date'   => date('Y-m-d'),
                ':expiry_date'  => $expiryDate,
                ':created_by'   => $userId,
                ':notes'        => $notes
            ]);

            $voucherId = (int)Database::lastInsertId();

            Audit::log('VOUCHER_CREATED', 'vouchers', (string)$voucherId, null, [
                'voucher_code' => $voucherCode,
                'amount'       => $amount,
                'expiry_date'  => $expiryDate
            ]);

            return [
                'success'      => true,
                'message'      => "Gift Voucher {$voucherCode} issued successfully.",
                'voucher_id'   => $voucherId,
                'voucher_code' => $voucherCode,
                'amount'       => $amount,
                'expiry_date'  => $expiryDate
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to create gift voucher: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error issuing gift voucher: ' . $e->getMessage()];
        }
    }

    /**
     * Validate gift voucher code during POS payment
     */
    public static function validateVoucher(string $code, float $payableTotal = 0.00): array
    {
        $code = trim($code);
        if (empty($code)) {
            return ['valid' => false, 'message' => 'Please enter a valid gift voucher barcode.'];
        }

        try {
            $voucher = Database::fetchOne("
                SELECT * FROM gift_vouchers WHERE voucher_code = :code
            ", [':code' => $code]);

            if (!$voucher) {
                return ['valid' => false, 'message' => 'Gift voucher code not found.'];
            }

            if ($voucher['status'] === 'redeemed') {
                return ['valid' => false, 'message' => "Gift voucher {$code} has already been redeemed."];
            }

            if ($voucher['status'] === 'cancelled') {
                return ['valid' => false, 'message' => "Gift voucher {$code} has been cancelled."];
            }

            if ($voucher['status'] !== 'active') {
                return ['valid' => false, 'message' => "Gift voucher {$code} is not active."];
            }

            if (strtotime($voucher['expiry_date']) < strtotime(date('Y-m-d'))) {
                // Update status to expired if overdue
                Database::execute("UPDATE gift_vouchers SET status = 'expired' WHERE id = :id", [':id' => $voucher['id']]);
                return ['valid' => false, 'message' => "Gift voucher {$code} expired on {$voucher['expiry_date']}."];
            }

            $voucherAmount = (float)$voucher['amount'];

            // Rule: No partial voucher use & prevent invalid overpayment if voucher > total
            if ($payableTotal > 0 && $voucherAmount > $payableTotal) {
                return [
                    'valid'   => false, 
                    'message' => "Voucher value (LKR " . number_format($voucherAmount, 2) . ") exceeds total payable (LKR " . number_format($payableTotal, 2) . "). Partial voucher usage is not permitted."
                ];
            }

            return [
                'valid'   => true,
                'voucher' => [
                    'id'           => (int)$voucher['id'],
                    'voucher_code' => $voucher['voucher_code'],
                    'amount'       => $voucherAmount,
                    'expiry_date'  => $voucher['expiry_date']
                ],
                'message' => 'Gift voucher validated successfully.'
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to validate voucher: " . $e->getMessage(), $e);
            return ['valid' => false, 'message' => 'Error validating gift voucher: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch paginated list of gift vouchers
     */
    public static function all(array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['search'])) {
                $where[] = "(gv.voucher_code LIKE :s OR u.username LIKE :s)";
                $params[':s'] = '%' . trim($filters['search']) . '%';
            }

            if (!empty($filters['status'])) {
                $where[] = "gv.status = :status";
                $params[':status'] = $filters['status'];
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total FROM gift_vouchers gv LEFT JOIN users u ON gv.created_by = u.id $whereSql
            ", $params);
            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT gv.*, u.full_name AS creator_name, s.invoice_no AS redeemed_invoice_no
                FROM gift_vouchers gv
                JOIN users u ON gv.created_by = u.id
                LEFT JOIN sales s ON gv.redeemed_sale_id = s.id
                $whereSql
                ORDER BY gv.id DESC
                LIMIT $limit OFFSET $offset
            ", $params);

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch vouchers list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Get Overall Gift Voucher Summary Statistics
     */
    public static function getSummaryStats(): array
    {
        try {
            $row = Database::fetchOne("
                SELECT 
                    COUNT(*) AS total_vouchers,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_vouchers,
                    COALESCE(SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END), 0.00) AS active_amount,
                    SUM(CASE WHEN status = 'redeemed' THEN 1 ELSE 0 END) AS redeemed_vouchers,
                    COALESCE(SUM(CASE WHEN status = 'redeemed' THEN amount ELSE 0 END), 0.00) AS redeemed_amount,
                    SUM(CASE WHEN status IN ('expired', 'cancelled') THEN 1 ELSE 0 END) AS expired_vouchers
                FROM gift_vouchers
            ");
            return $row ?: [
                'total_vouchers' => 0,
                'active_vouchers' => 0,
                'active_amount' => 0.00,
                'redeemed_vouchers' => 0,
                'redeemed_amount' => 0.00,
                'expired_vouchers' => 0
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch gift voucher summary stats: " . $e->getMessage(), $e);
            return [
                'total_vouchers' => 0,
                'active_vouchers' => 0,
                'active_amount' => 0.00,
                'redeemed_vouchers' => 0,
                'redeemed_amount' => 0.00,
                'expired_vouchers' => 0
            ];
        }
    }

    /**
     * Cancel an active gift voucher
     */
    public static function cancelVoucher(int $id): array
    {
        try {
            $voucher = Database::fetchOne("SELECT * FROM gift_vouchers WHERE id = :id", [':id' => $id]);
            if (!$voucher) {
                return ['success' => false, 'message' => 'Gift voucher not found.'];
            }
            if ($voucher['status'] !== 'active') {
                return ['success' => false, 'message' => "Cannot cancel voucher with status '{$voucher['status']}'."];
            }

            Database::execute("UPDATE gift_vouchers SET status = 'cancelled' WHERE id = :id", [':id' => $id]);
            Audit::log('VOUCHER_CANCELLED', 'vouchers', (string)$id, $voucher, ['status' => 'cancelled']);

            return ['success' => true, 'message' => "Gift Voucher {$voucher['voucher_code']} cancelled successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to cancel gift voucher #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error cancelling voucher: ' . $e->getMessage()];
        }
    }
}
