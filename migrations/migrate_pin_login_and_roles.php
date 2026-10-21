<?php
require_once __DIR__ . '/../config/config.php';
$db = Database::getInstance()->getConnection();

echo "=== Running Migration: PIN Login and Multi-Role Management ===" . PHP_EOL;

// 1. Add pin_hash to users table if not exists
try {
    $colCheck = $db->query("SHOW COLUMNS FROM users LIKE 'pin_hash'")->fetch();
    if (!$colCheck) {
        $db->exec("ALTER TABLE users ADD COLUMN pin_hash VARCHAR(255) NULL AFTER password_hash");
        echo "PASS: Added pin_hash column to users table." . PHP_EOL;
    } else {
        echo "INFO: pin_hash column already exists in users table." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "ERROR adding pin_hash: " . $e->getMessage() . PHP_EOL;
}

// 2. Create user_roles table if not exists
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            user_id INT(10) UNSIGNED NOT NULL,
            role_id INT(10) UNSIGNED NOT NULL,
            assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, role_id),
            INDEX idx_ur_role (role_id),
            CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "PASS: Created/verified user_roles table." . PHP_EOL;

    // Seed existing roles
    $inserted = $db->exec("
        INSERT IGNORE INTO user_roles (user_id, role_id)
        SELECT id, role_id FROM users WHERE deleted_at IS NULL AND role_id IS NOT NULL;
    ");
    echo "PASS: Seeded user_roles ({$inserted} rows synced from existing users)." . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR setting up user_roles: " . $e->getMessage() . PHP_EOL;
}

// 3. Seed default unique 5-digit PINs for existing default accounts
$pinAssignments = [
    1 => '11111', // superadmin
    2 => '22222', // admin
    3 => '33333', // manager1
    4 => '44444', // cashier1
    5 => '55555', // accountant
    6 => '66666', // specialuser
    8 => '88888', // playwright_admin
];

$stmtUpdatePin = $db->prepare("UPDATE users SET pin_hash = :hash WHERE id = :id AND (pin_hash IS NULL OR pin_hash = '')");
foreach ($pinAssignments as $userId => $pin) {
    $hash = password_hash($pin, PASSWORD_BCRYPT);
    $stmtUpdatePin->execute([':hash' => $hash, ':id' => $userId]);
    if ($stmtUpdatePin->rowCount() > 0) {
        echo "PASS: Configured default 5-digit PIN for User #{$userId}." . PHP_EOL;
    }
}

// Check how many users have pin_hash set
$withPin = $db->query("SELECT COUNT(*) FROM users WHERE pin_hash IS NOT NULL AND pin_hash != ''")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
echo "Summary: {$withPin} of {$totalUsers} active users now have PINs configured." . PHP_EOL;
echo "=== Migration Complete ===" . PHP_EOL;
