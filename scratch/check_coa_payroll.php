<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$coas = Database::fetchAll("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE account_code LIKE '5%' OR account_code LIKE '2%' OR account_code LIKE '104%' ORDER BY account_code ASC");
print_r($coas);
