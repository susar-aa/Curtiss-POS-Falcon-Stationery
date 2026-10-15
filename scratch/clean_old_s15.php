<?php
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance()->getConnection();
$db->exec("SET FOREIGN_KEY_CHECKS = 0");
$db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Test Supplier S15_%')");
$db->exec("DELETE FROM journal_entries WHERE description LIKE '%Test Supplier S15_%'");
$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "Cleaned up old Stage 15 test journal entries!\n";
