<?php
require_once dirname(__DIR__) . '/config/config.php';

$jes = Database::fetchAll("SELECT id, entry_number, branch_id, source_type, source_id, description FROM journal_entries WHERE description LIKE '%Alona%' OR description LIKE '%Opening Balance%'");
print_r($jes);

$obs = Database::fetchAll("SELECT * FROM supplier_opening_balances");
print_r($obs);
