<?php
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/config/config.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Database.php';

$je = Database::fetchOne("SELECT * FROM journal_entries ORDER BY id DESC LIMIT 1");
print_r($je);

$lines = Database::fetchAll("SELECT jl.*, coa.account_code, coa.account_name FROM journal_lines jl JOIN chart_of_accounts coa ON jl.account_id = coa.id WHERE jl.journal_entry_id = :id", [':id' => $je['id']]);
$totalDr = 0; $totalCr = 0;
foreach ($lines as $l) {
    $dr = (float)$l['debit'];
    $cr = (float)$l['credit'];
    $totalDr += $dr;
    $totalCr += $cr;
    echo "  [{$l['account_code']}] {$l['account_name']}: Dr " . number_format($dr, 2) . " | Cr " . number_format($cr, 2) . " ({$l['memo']})\n";
}
echo "Total Debit: " . number_format($totalDr, 2) . " | Total Credit: " . number_format($totalCr, 2) . "\n";
