<?php
/**
 * System Settings Configuration Engine
 * Provides access to system_settings key-value repository.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';

class Settings
{
    private static array $cache = [];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        try {
            $row = Database::fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1", [
                ':key' => $key
            ]);

            $val = $row ? $row['setting_value'] : $default;
            self::$cache[$key] = $val;
            return $val;
        } catch (Throwable $e) {
            Logger::error("Settings get failed for key: $key - " . $e->getMessage(), $e);
            return $default;
        }
    }

    /**
     * Update or insert a setting value
     */
    public static function set(string $key, string $value, ?int $userId = null, string $group = 'general'): bool
    {
        try {
            $existing = Database::fetchOne("SELECT id FROM system_settings WHERE setting_key = :key", [':key' => $key]);

            if ($existing) {
                $sql = "UPDATE system_settings SET setting_value = :value, updated_by = :updated_by, updated_at = NOW() WHERE setting_key = :key";
            } else {
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_by) VALUES (:key, :value, :group, :updated_by)";
            }

            $params = [
                ':key'        => $key,
                ':value'      => $value,
                ':updated_by' => $userId ?? ($_SESSION['user_id'] ?? null)
            ];

            if (!$existing) {
                $params[':group'] = $group;
            }

            $success = Database::execute($sql, $params);
            if ($success) {
                self::$cache[$key] = $value;
            }
            return $success;
        } catch (Throwable $e) {
            Logger::error("Settings set failed for key: $key - " . $e->getMessage(), $e);
            return false;
        }
    }

    /**
     * Get Company Information Bundle
     */
    public static function getCompanyInfo(): array
    {
        return [
            'name'           => self::get('business_name', 'Kandy Kids'),
            'phone'          => self::get('business_phone', '+94 81 222 3344'),
            'whatsapp'       => self::get('business_whatsapp', '+94 77 123 4567'),
            'email'          => self::get('business_email', 'info@curtisspos.com'),
            'website'        => self::get('business_website', 'www.curtisspos.com'),
            'address'        => self::get('business_address', 'No 123, Peradeniya Road, Kandy'),
            'reg_no'         => self::get('business_reg_no', ''),
            'logo'           => self::get('company_logo', ''),
            'currency'       => self::get('currency_symbol', 'LKR'),
            'receipt_footer' => self::get('receipt_footer_note', 'Thank You For Shopping With Us!')
        ];
    }

    /**
     * Get Branch Information with fallback to company settings
     */
    public static function getBranchInfo(?int $branchId = null): array
    {
        $bId = $branchId ?? (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
        $company = self::getCompanyInfo();

        $branch = null;
        if ($bId > 0) {
            try {
                $branch = Database::fetchOne("SELECT * FROM branches WHERE id = :id AND deleted_at IS NULL LIMIT 1", [':id' => $bId]);
            } catch (Throwable $e) {
                $branch = null;
            }
        }

        return [
            'branch_id'        => $branch ? (int)$branch['id'] : $bId,
            'branch_name'      => $branch['name'] ?? 'Main Branch',
            'branch_code'      => $branch['code'] ?? 'BR-01',
            'branch_phone'     => !empty($branch['phone']) ? $branch['phone'] : $company['phone'],
            'branch_email'     => !empty($branch['email']) ? $branch['email'] : $company['email'],
            'branch_address'   => !empty($branch['address']) ? $branch['address'] : $company['address'],
            'company_name'     => $company['name'],
            'company_phone'    => $company['phone'],
            'company_whatsapp' => $company['whatsapp'],
            'company_email'    => $company['email'],
            'company_website'  => $company['website'],
            'company_address'  => $company['address'],
            'company_reg_no'   => $company['reg_no'],
            'company_logo'     => $company['logo'],
            'currency'         => $company['currency'],
            'receipt_footer'   => $company['receipt_footer']
        ];
    }

    /**
     * Get all active branch records for receipt & invoice multi-branch footers
     *
     * @return array
     */
    public static function getAllActiveBranches(): array
    {
        try {
            return Database::fetchAll("
                SELECT id, name, code, phone, email, address 
                FROM branches 
                WHERE deleted_at IS NULL AND status = 'active' 
                ORDER BY id ASC
            ");
        } catch (Throwable $e) {
            return [];
        }
    }
}
