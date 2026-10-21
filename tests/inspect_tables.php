<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "--- USER BRANCHES ---\n";
print_r(Database::fetchAll("SELECT * FROM user_branches"));

echo "\n--- USER ROLES ---\n";
print_r(Database::fetchAll("SELECT * FROM user_roles"));
