<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$acc = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = '1250'");
print_r($acc);
