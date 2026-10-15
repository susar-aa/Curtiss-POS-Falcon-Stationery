<?php
/**
 * Curtiss POS - Stage 16 Database Migration Script
 * Seeds COA account 4050 (Bank Interest / Other Income) and Stage 16 Report permissions.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Stage 16 Migration...\n";

    // 1. Seed COA account 4050 (Bank Interest / Other Income)
    $db->exec("
        INSERT IGNORE INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
        ('4050', 'Bank Interest / Other Income Account', 'income', NULL, NULL, 1, 'active');
    ");
    echo "  - COA account 4050 (Bank Interest / Other Income) seeded.\n";

    // 2. Seed Stage 16 Permissions
    $db->exec("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (79, 'reports', 'view_reports', 'View central reports and analytics viewer'),
        (80, 'reports', 'export_reports', 'Export reports to Excel/CSV, PDF, and print format'),
        (81, 'reports', 'view_financial_statements', 'View financial statements (Trial Balance, P&L, Balance Sheet)');
    ");
    echo "  - Stage 16 permissions seeded.\n";

    // 3. Assign Permissions to Roles
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 79 AND 81;"); // Super Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 79 AND 81;"); // Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 79 AND 81;"); // Manager
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 79 AND 81;"); // Accountant

    echo "Stage 16 Database Migration Completed Successfully!\n";

} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
