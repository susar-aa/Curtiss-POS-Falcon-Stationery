<?php
require_once __DIR__ . '/../core/Database.php';

$lines = Database::fetchAll("
    SELECT jl.*, je.source_type, je.source_id, je.description, je.created_at
    FROM journal_lines jl
    JOIN journal_entries je ON jl.journal_entry_id = je.id
    JOIN chart_of_accounts coa ON jl.account_id = coa.id
    WHERE coa.account_code = '1045'
");

echo "Journal Lines for COA 1045 (Supplier Credit):\n";
print_r($lines);

$scs = Database::fetchAll("SELECT * FROM supplier_credits");
echo "Supplier Credits Table:\n";
print_r($scs);
