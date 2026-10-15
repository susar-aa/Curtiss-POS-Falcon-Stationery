<?php
/**
 * Curtiss POS - Category Core Component
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class Category
{
    public static function all(bool $activeOnly = false): array
    {
        try {
            $sql = "SELECT * FROM categories WHERE deleted_at IS NULL";
            if ($activeOnly) {
                $sql .= " AND status = 'active'";
            }
            $sql .= " ORDER BY name ASC";
            return Database::fetchAll($sql);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch categories: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        try {
            return Database::fetchOne("SELECT * FROM categories WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
        } catch (Throwable $e) {
            Logger::error("Failed to find category #$id: " . $e->getMessage(), $e);
            return null;
        }
    }

    public static function create(string $name, string $status = 'active'): array
    {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        $existing = Database::fetchOne("
            SELECT id FROM categories 
            WHERE LOWER(name) = LOWER(:name) AND deleted_at IS NULL
        ", [':name' => $name]);

        if ($existing) {
            return ['success' => false, 'message' => 'A category with this name already exists.'];
        }

        try {
            Database::execute("
                INSERT INTO categories (name, status) VALUES (:name, :status)
            ", [
                ':name'   => $name,
                ':status' => $status === 'inactive' ? 'inactive' : 'active'
            ]);

            $newId = Database::lastInsertId();
            Audit::log('CATEGORY_CREATED', 'categories', (string)$newId, null, ['name' => $name, 'status' => $status]);
            return ['success' => true, 'id' => $newId, 'message' => 'Category created successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to create category: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while creating category.'];
        }
    }

    public static function update(int $id, string $name, string $status): array
    {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        $category = self::find($id);
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found.'];
        }

        $existing = Database::fetchOne("
            SELECT id FROM categories 
            WHERE LOWER(name) = LOWER(:name) AND id != :id AND deleted_at IS NULL
        ", [':name' => $name, ':id' => $id]);

        if ($existing) {
            return ['success' => false, 'message' => 'Another category with this name already exists.'];
        }

        try {
            Database::execute("
                UPDATE categories SET name = :name, status = :status, updated_at = NOW() WHERE id = :id
            ", [
                ':name'   => $name,
                ':status' => $status === 'inactive' ? 'inactive' : 'active',
                ':id'     => $id
            ]);

            Audit::log('CATEGORY_UPDATED', 'categories', (string)$id, $category, ['name' => $name, 'status' => $status]);
            return ['success' => true, 'message' => 'Category updated successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to update category #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while updating category.'];
        }
    }

    public static function toggleStatus(int $id): array
    {
        $category = self::find($id);
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found.'];
        }

        $newStatus = ($category['status'] === 'active') ? 'inactive' : 'active';
        try {
            Database::execute("UPDATE categories SET status = :status WHERE id = :id", [
                ':status' => $newStatus,
                ':id'     => $id
            ]);

            Audit::log('CATEGORY_STATUS_TOGGLED', 'categories', (string)$id, ['status' => $category['status']], ['status' => $newStatus]);
            return ['success' => true, 'message' => "Category status updated to $newStatus."];
        } catch (Throwable $e) {
            Logger::error("Failed to toggle category status #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    public static function deleted(): array
    {
        try {
            return Database::fetchAll("SELECT * FROM categories WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        } catch (Throwable $e) {
            Logger::error("Failed to fetch deleted categories: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function delete(int $id): array
    {
        $category = self::find($id);
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found.'];
        }

        try {
            $linkedCount = (int)(Database::fetchOne("
                SELECT COUNT(*) as cnt FROM products WHERE category_id = :id AND deleted_at IS NULL
            ", [':id' => $id])['cnt'] ?? 0);

            Database::execute("UPDATE categories SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);

            Audit::log('CATEGORY_DELETED', 'categories', (string)$id, $category, ['deleted_at' => date('Y-m-d H:i:s')]);

            $msg = $linkedCount > 0 
                ? "Category moved to Deleted Categories trash bin (linked to $linkedCount product(s))." 
                : "Category moved to Deleted Categories trash bin.";

            return ['success' => true, 'message' => $msg];
        } catch (Throwable $e) {
            Logger::error("Failed to delete category #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while deleting category.'];
        }
    }

    public static function restore(int $id): array
    {
        try {
            $category = Database::fetchOne("SELECT * FROM categories WHERE id = :id AND deleted_at IS NOT NULL", [':id' => $id]);
            if (!$category) {
                return ['success' => false, 'message' => 'Deleted category record not found.'];
            }

            $existing = Database::fetchOne("
                SELECT id FROM categories 
                WHERE LOWER(name) = LOWER(:name) AND deleted_at IS NULL AND id != :id
            ", [':name' => $category['name'], ':id' => $id]);

            if ($existing) {
                return ['success' => false, 'message' => 'Cannot restore: an active category with the name "' . $category['name'] . '" already exists.'];
            }

            Database::execute("UPDATE categories SET deleted_at = NULL WHERE id = :id", [':id' => $id]);

            Audit::log('CATEGORY_RESTORED', 'categories', (string)$id, ['deleted_at' => $category['deleted_at']], ['restored_at' => date('Y-m-d H:i:s')]);
            return ['success' => true, 'message' => 'Category restored successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to restore category #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while restoring category.'];
        }
    }
}
