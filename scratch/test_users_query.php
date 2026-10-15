<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$roles = Database::fetchAll("SELECT id, name, code FROM roles ORDER BY id ASC");
$branches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$users = Database::fetchAll("
    SELECT u.*, r.name AS role_name, r.code AS role_code,
           GROUP_CONCAT(b.name SEPARATOR ', ') AS assigned_branches
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN user_branches ub ON u.id = ub.user_id
    LEFT JOIN branches b ON ub.branch_id = b.id AND b.status = 'active'
    WHERE u.deleted_at IS NULL
    GROUP BY u.id
    ORDER BY u.id ASC
");

echo "Roles count: " . count($roles) . "\n";
echo "Branches count: " . count($branches) . "\n";
echo "Users count: " . count($users) . "\n";
