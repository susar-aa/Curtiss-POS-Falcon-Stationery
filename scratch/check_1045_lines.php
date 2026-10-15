<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$rows = Database::fetchAll("
    SELECT je.id, je.source_type, je.source_id, je.description, jl.debit, jl.credit, coa.account_code 
    FROM journal_lines jl 
    JOIN journal_entries je ON jl.journal_entry_id = je.id 
    JOIN chart_of_accounts coa ON jl.account_id = coa.id 
    WHERE coa.account_code = '1045'
");

print_r($rows);
