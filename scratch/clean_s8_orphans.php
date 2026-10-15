<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance()->getConnection();

// Delete orphan Sec8 test settlements and journal entries
$db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Sec8 Test%' OR description LIKE '%S8%')");
$db->exec("DELETE FROM journal_entries WHERE description LIKE '%Sec8 Test%' OR description LIKE '%S8%'");
$db->exec("DELETE FROM supplier_credit_settlements WHERE supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%') OR notes LIKE '%Sec8%' OR notes LIKE '%Section 8%'");
$db->exec("DELETE FROM supplier_credits WHERE supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%') OR notes LIKE '%Sec8%' OR notes LIKE '%Section 8%'");

echo "Cleaned up orphaned Sec8 test records.\n";
