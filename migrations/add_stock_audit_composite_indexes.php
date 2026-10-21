<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$queries = [
    "ALTER TABLE stock_audits ADD INDEX idx_aud_branch_status_id (branch_id, status, id)",
    "ALTER TABLE stock_audits ADD INDEX idx_aud_branch_id (branch_id, id)",
    "ALTER TABLE stock_audit_items ADD INDEX idx_sai_audit_id (audit_id, id)"
];

$pdo = Database::getInstance()->getConnection();
foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "SUCCESS: " . $sql . PHP_EOL;
    } catch (Throwable $e) {
        echo "NOTICE: " . $sql . " => " . $e->getMessage() . PHP_EOL;
    }
}
