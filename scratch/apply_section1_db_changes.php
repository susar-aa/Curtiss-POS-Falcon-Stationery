<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Applying Section 1 Database Schema Changes...\n";

    // 1. Add sku column to products table if not exists
    $stmtCols = $db->query("SHOW COLUMNS FROM products LIKE 'sku'");
    if (!$stmtCols->fetch()) {
        $db->exec("ALTER TABLE products ADD COLUMN sku VARCHAR(50) NULL AFTER id");
        $db->exec("ALTER TABLE products ADD UNIQUE KEY idx_products_sku (sku)");
        echo " - Added `sku` column and unique index to `products` table.\n";
    } else {
        echo " - `sku` column already exists in `products` table.\n";
    }

    // Populate existing null SKUs with barcode if empty
    $db->exec("UPDATE products SET sku = barcode WHERE (sku IS NULL OR sku = '') AND barcode IS NOT NULL AND barcode != ''");
    echo " - Populated null SKUs with barcode for existing products.\n";

    // 2. Add product_import_batches table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS product_import_batches (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            batch_no VARCHAR(50) NOT NULL UNIQUE,
            file_name VARCHAR(255) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            import_date DATETIME NOT NULL,
            import_mode VARCHAR(50) NOT NULL,
            total_rows INT UNSIGNED NOT NULL DEFAULT 0,
            created_count INT UNSIGNED NOT NULL DEFAULT 0,
            updated_count INT UNSIGNED NOT NULL DEFAULT 0,
            skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
            failed_count INT UNSIGNED NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo " - Created `product_import_batches` table.\n";

    echo "Database schema changes applied successfully!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
