<?php
/**
 * Migration: Add Performance Composite Indexes for Cash Drawer & Business Day
 * Curtiss POS - Stage 11 Optimization
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Running migration: add_cash_drawer_composite_indexes...\n";

    // 1. business_days composite index for branch + status + id pagination
    $idxBday = $db->query("
        SHOW INDEX FROM `business_days` WHERE Key_name = 'idx_bday_branch_status_id'
    ")->fetch();

    if (!$idxBday) {
        $db->exec("
            CREATE INDEX `idx_bday_branch_status_id` 
            ON `business_days` (`branch_id`, `status`, `id`)
        ");
        echo "  [CREATED] index idx_bday_branch_status_id on business_days\n";
    } else {
        echo "  [EXISTS] index idx_bday_branch_status_id on business_days\n";
    }

    // 2. business_days composite index for branch + date
    $idxBdayDate = $db->query("
        SHOW INDEX FROM `business_days` WHERE Key_name = 'idx_bday_branch_date'
    ")->fetch();

    if (!$idxBdayDate) {
        $db->exec("
            CREATE INDEX `idx_bday_branch_date` 
            ON `business_days` (`branch_id`, `business_date`)
        ");
        echo "  [CREATED] index idx_bday_branch_date on business_days\n";
    } else {
        echo "  [EXISTS] index idx_bday_branch_date on business_days\n";
    }

    // 3. cash_drawer_movements index on (business_day_id, id)
    $idxCdmBday = $db->query("
        SHOW INDEX FROM `cash_drawer_movements` WHERE Key_name = 'idx_cdm_bday_id'
    ")->fetch();

    if (!$idxCdmBday) {
        $db->exec("
            CREATE INDEX `idx_cdm_bday_id` 
            ON `cash_drawer_movements` (`business_day_id`, `id`)
        ");
        echo "  [CREATED] index idx_cdm_bday_id on cash_drawer_movements\n";
    } else {
        echo "  [EXISTS] index idx_cdm_bday_id on cash_drawer_movements\n";
    }

    // 4. cash_drawer_movements index on (business_day_id, movement_type)
    $idxCdmType = $db->query("
        SHOW INDEX FROM `cash_drawer_movements` WHERE Key_name = 'idx_cdm_bday_type'
    ")->fetch();

    if (!$idxCdmType) {
        $db->exec("
            CREATE INDEX `idx_cdm_bday_type` 
            ON `cash_drawer_movements` (`business_day_id`, `movement_type`)
        ");
        echo "  [CREATED] index idx_cdm_bday_type on cash_drawer_movements\n";
    } else {
        echo "  [EXISTS] index idx_cdm_bday_type on cash_drawer_movements\n";
    }

    echo "Migration completed successfully.\n";

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
