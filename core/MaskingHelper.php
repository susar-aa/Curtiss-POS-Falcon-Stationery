<?php
/**
 * Curtiss POS - Special User Masking Engine (Stage 17)
 * Centralized service for presentation-layer masking of sensitive financial & stock values.
 * CRITICAL: Must NEVER mutate database values, transactions, accounting journals, or stock quantities.
 */

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Settings.php';

class MaskingHelper
{
    private static ?float $cachedPercentage = null;

    /**
     * Clear memory cache (useful for CLI tests when setting is updated in runtime)
     */
    public static function clearCache(): void
    {
        self::$cachedPercentage = null;
    }

    /**
     * Get configured display percentage value (e.g. 60.00)
     */
    public static function getPercentage(): float
    {
        if (self::$cachedPercentage !== null) {
            return self::$cachedPercentage;
        }

        $pctStr = Settings::get('special_user_display_percentage');
        if ($pctStr === null || $pctStr === '') {
            $pctStr = Settings::get('special_user_visibility_pct', '60.00');
        }
        $pct = (float)$pctStr;
        if ($pct <= 0 || $pct > 100) {
            $pct = 60.00;
        }

        self::$cachedPercentage = $pct;
        return $pct;
    }

    /**
     * Get configured masking percentage as a decimal factor (e.g. 60% -> 0.60)
     */
    public static function getFactor(): float
    {
        return self::getPercentage() / 100.0;
    }

    /**
     * Check if active session user is a Special User requiring masked output
     */
    public static function isSpecialUser(): bool
    {
        return Auth::isSpecialUser();
    }

    /**
     * Apply masking to a financial numeric value if current user is Special User or $force is true
     */
    public static function maskValue(float $value, bool $force = false): float
    {
        if (!$force && !self::isSpecialUser()) {
            return round($value, 2);
        }
        return round($value * self::getFactor(), 2);
    }

    /**
     * Apply masking to a stock quantity if current user is Special User or $force is true
     */
    public static function maskQuantity(float $quantity, bool $force = false): float
    {
        if (!$force && !self::isSpecialUser()) {
            return round($quantity, 2);
        }
        return round($quantity * self::getFactor(), 2);
    }

    /**
     * Update configured display percentage (Super Admin Only)
     */
    public static function setPercentage(float $percentage, ?int $userId = null): array
    {
        $roleCode = $_SESSION['role_code'] ?? '';
        $currentUserId = $_SESSION['user_id'] ?? null;

        if ($userId !== null && $userId !== $currentUserId) {
            try {
                $userRow = Database::fetchOne("
                    SELECT r.code 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    WHERE u.id = :id
                ", [':id' => $userId]);
                if ($userRow) {
                    $roleCode = $userRow['code'];
                }
            } catch (Throwable $e) {}
        }

        if ($roleCode !== ROLE_SUPER_ADMIN) {
            Audit::log('SPECIAL_USER_MASK_PERCENT_UPDATE_BLOCKED', 'settings', null, ['attempted_percentage' => $percentage, 'user_id' => $userId ?? $currentUserId]);
            return ['success' => false, 'message' => 'Only Super Admin can configure Special User display percentage.'];
        }

        if ($percentage <= 0.0 || $percentage > 100.0) {
            return ['success' => false, 'message' => 'Display percentage must be between 0.01% and 100.00%.'];
        }

        $formattedPct = sprintf('%.2f', $percentage);
        $saved = Settings::set('special_user_display_percentage', $formattedPct, $userId ?? $currentUserId);
        if ($saved) {
            self::clearCache();
            Audit::log('SPECIAL_USER_PERCENTAGE_UPDATED', 'settings', null, ['percentage' => $formattedPct]);
            return ['success' => true, 'message' => "Special User display percentage updated to {$formattedPct}%."];
        }

        return ['success' => false, 'message' => 'Failed to persist display percentage setting.'];
    }

    /**
     * Apply masking to product array (Cost & Stock Qty masked; Selling Price REAL)
     */
    public static function maskProduct(array $product, bool $force = false): array
    {
        if (!$force && !self::isSpecialUser()) {
            return $product;
        }

        foreach (['cost', 'unit_cost', 'cost_price', 'total_cost', 'line_cost'] as $k) {
            if (isset($product[$k])) {
                $product[$k] = self::maskValue((float)$product[$k], true);
            }
        }

        foreach (['stock_quantity', 'quantity', 'branch_stock', 'stock_qty', 'remaining_quantity', 'in_transit_quantity', 'damaged_quantity'] as $k) {
            if (isset($product[$k])) {
                $product[$k] = self::maskQuantity((float)$product[$k], true);
            }
        }

        return $product;
    }

    /**
     * Apply masking to customer financial balance fields
     */
    public static function maskCustomer(array $customer, bool $force = false): array
    {
        if (!$force && !self::isSpecialUser()) {
            return $customer;
        }

        foreach (['current_balance', 'outstanding_balance', 'credit_limit', 'total_purchases', 'total_payments', 'balance'] as $k) {
            if (isset($customer[$k])) {
                $customer[$k] = self::maskValue((float)$customer[$k], true);
            }
        }

        return $customer;
    }

    /**
     * Apply masking to supplier financial balance fields
     */
    public static function maskSupplier(array $supplier, bool $force = false): array
    {
        if (!$force && !self::isSpecialUser()) {
            return $supplier;
        }

        foreach (['outstanding_balance', 'total_purchases', 'total_payments', 'supplier_credit', 'balance'] as $k) {
            if (isset($supplier[$k])) {
                $supplier[$k] = self::maskValue((float)$supplier[$k], true);
            }
        }

        return $supplier;
    }

    /**
     * Apply masking to Card Settlement & Card Receivable amounts (Section 17)
     * Keeps dedicated bank balances REAL!
     */
    public static function maskCardSettlement(array $settlement, bool $force = false): array
    {
        if (!$force && !self::isSpecialUser()) {
            return $settlement;
        }

        foreach (['gross_amount', 'processing_fee', 'net_bank_amount', 'unsettled_amount', 'settled_amount', 'gross_payment_amount', 'original_payment_amount'] as $k) {
            if (isset($settlement[$k])) {
                $settlement[$k] = self::maskValue((float)$settlement[$k], true);
            }
        }

        if (!empty($settlement['items']) && is_array($settlement['items'])) {
            foreach ($settlement['items'] as &$item) {
                foreach (['gross_amount', 'unsettled_amount', 'settled_amount', 'original_payment_amount', 'payment_total_settled'] as $k) {
                    if (isset($item[$k])) {
                        $item[$k] = self::maskValue((float)$item[$k], true);
                    }
                }
            }
        }

        return $settlement;
    }
}

