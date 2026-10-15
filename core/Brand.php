<?php
/**
 * Curtiss POS - Brand Core Component
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class Brand
{
    public static function all(bool $activeOnly = false): array
    {
        try {
            $sql = "SELECT * FROM brands WHERE deleted_at IS NULL";
            if ($activeOnly) {
                $sql .= " AND status = 'active'";
            }
            $sql .= " ORDER BY name ASC";
            return Database::fetchAll($sql);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch brands: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        try {
            return Database::fetchOne("SELECT * FROM brands WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
        } catch (Throwable $e) {
            Logger::error("Failed to find brand #$id: " . $e->getMessage(), $e);
            return null;
        }
    }

    public static function create(string $name, string $status = 'active'): array
    {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Brand name is required.'];
        }

        // Check for duplicate active brand name
        $existing = Database::fetchOne("
            SELECT id FROM brands 
            WHERE LOWER(name) = LOWER(:name) AND deleted_at IS NULL
        ", [':name' => $name]);

        if ($existing) {
            return ['success' => false, 'message' => 'A brand with this name already exists.'];
        }

        try {
            Database::execute("
                INSERT INTO brands (name, status) VALUES (:name, :status)
            ", [
                ':name'   => $name,
                ':status' => $status === 'inactive' ? 'inactive' : 'active'
            ]);

            $newId = Database::lastInsertId();
            Audit::log('BRAND_CREATED', 'brands', (string)$newId, null, ['name' => $name, 'status' => $status]);
            return ['success' => true, 'id' => $newId, 'message' => 'Brand created successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to create brand: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while creating brand.'];
        }
    }

    public static function update(int $id, string $name, string $status): array
    {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Brand name is required.'];
        }

        $brand = self::find($id);
        if (!$brand) {
            return ['success' => false, 'message' => 'Brand not found.'];
        }

        // Check duplicate name excluding current record
        $existing = Database::fetchOne("
            SELECT id FROM brands 
            WHERE LOWER(name) = LOWER(:name) AND id != :id AND deleted_at IS NULL
        ", [':name' => $name, ':id' => $id]);

        if ($existing) {
            return ['success' => false, 'message' => 'Another brand with this name already exists.'];
        }

        try {
            Database::execute("
                UPDATE brands SET name = :name, status = :status, updated_at = NOW() WHERE id = :id
            ", [
                ':name'   => $name,
                ':status' => $status === 'inactive' ? 'inactive' : 'active',
                ':id'     => $id
            ]);

            Audit::log('BRAND_UPDATED', 'brands', (string)$id, $brand, ['name' => $name, 'status' => $status]);
            return ['success' => true, 'message' => 'Brand updated successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to update brand #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while updating brand.'];
        }
    }

    public static function toggleStatus(int $id): array
    {
        $brand = self::find($id);
        if (!$brand) {
            return ['success' => false, 'message' => 'Brand not found.'];
        }

        $newStatus = ($brand['status'] === 'active') ? 'inactive' : 'active';
        try {
            Database::execute("UPDATE brands SET status = :status WHERE id = :id", [
                ':status' => $newStatus,
                ':id'     => $id
            ]);

            Audit::log('BRAND_STATUS_TOGGLED', 'brands', (string)$id, ['status' => $brand['status']], ['status' => $newStatus]);
            return ['success' => true, 'message' => "Brand status updated to $newStatus."];
        } catch (Throwable $e) {
            Logger::error("Failed to toggle brand status #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    public static function deleted(): array
    {
        try {
            return Database::fetchAll("SELECT * FROM brands WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        } catch (Throwable $e) {
            Logger::error("Failed to fetch deleted brands: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function delete(int $id): array
    {
        $brand = self::find($id);
        if (!$brand) {
            return ['success' => false, 'message' => 'Brand not found.'];
        }

        try {
            $linkedCount = (int)(Database::fetchOne("
                SELECT COUNT(*) as cnt FROM products WHERE brand_id = :id AND deleted_at IS NULL
            ", [':id' => $id])['cnt'] ?? 0);

            Database::execute("UPDATE brands SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);

            Audit::log('BRAND_DELETED', 'brands', (string)$id, $brand, ['deleted_at' => date('Y-m-d H:i:s')]);

            $msg = $linkedCount > 0 
                ? "Brand moved to Deleted Brands trash bin (linked to $linkedCount product(s))." 
                : "Brand moved to Deleted Brands trash bin.";

            return ['success' => true, 'message' => $msg];
        } catch (Throwable $e) {
            Logger::error("Failed to delete brand #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while deleting brand.'];
        }
    }

    public static function restore(int $id): array
    {
        try {
            $brand = Database::fetchOne("SELECT * FROM brands WHERE id = :id AND deleted_at IS NOT NULL", [':id' => $id]);
            if (!$brand) {
                return ['success' => false, 'message' => 'Deleted brand record not found.'];
            }

            $existing = Database::fetchOne("
                SELECT id FROM brands 
                WHERE LOWER(name) = LOWER(:name) AND deleted_at IS NULL AND id != :id
            ", [':name' => $brand['name'], ':id' => $id]);

            if ($existing) {
                return ['success' => false, 'message' => 'Cannot restore: an active brand with the name "' . $brand['name'] . '" already exists.'];
            }

            Database::execute("UPDATE brands SET deleted_at = NULL WHERE id = :id", [':id' => $id]);

            Audit::log('BRAND_RESTORED', 'brands', (string)$id, ['deleted_at' => $brand['deleted_at']], ['restored_at' => date('Y-m-d H:i:s')]);
            return ['success' => true, 'message' => 'Brand restored successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to restore brand #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while restoring brand.'];
        }
    }
}
