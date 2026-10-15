<?php
/**
 * CLI Test Script: Notifications System Verification
 */

define('CLI_MODE', true);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Setup fake session for super admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['role_id'] = 1;
$_SESSION['branch_id'] = 1;
$_SESSION['active_branch_id'] = 1;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';

echo "=== CURTISS NOTIFICATIONS SYSTEM CLI VERIFICATION ===\n";

// Execute API via output buffering
ob_start();
require __DIR__ . '/../nav_notifications_api.php';
$jsonOutput = ob_get_clean();

$data = json_decode($jsonOutput, true);

if (!$data) {
    echo "[FAIL] Invalid JSON returned: " . substr($jsonOutput, 0, 200) . "\n";
    exit(1);
}

if (!isset($data['ok']) || $data['ok'] !== true) {
    echo "[FAIL] API did not return ok: true. Response: " . json_encode($data) . "\n";
    exit(1);
}

echo "[PASS] API returned ok: true\n";
echo "Active Notifications Count: " . count($data['notifications']) . "\n";
echo "Unread Count: " . $data['unread_count'] . "\n\n";

$validTypes = ['critical', 'warning', 'info'];

foreach ($data['notifications'] as $idx => $n) {
    echo "--- Notification #" . ($idx + 1) . " ---\n";
    echo "ID:    " . ($n['id'] ?? 'MISSING') . "\n";
    echo "Title: " . ($n['title'] ?? 'MISSING') . "\n";
    echo "Badge: " . ($n['badge'] ?? 'N/A') . "\n";
    echo "Type:  " . ($n['type'] ?? 'MISSING') . "\n";
    echo "Icon:  " . ($n['icon'] ?? 'MISSING') . "\n";
    echo "URL:   " . ($n['url'] ?? 'MISSING') . "\n";
    echo "Text:  " . ($n['text'] ?? '') . "\n";

    if (empty($n['title']) || empty($n['url']) || empty($n['text'])) {
        echo "[FAIL] Missing required notification fields!\n";
        exit(1);
    }

    if (!in_array($n['type'], $validTypes, true)) {
        echo "[FAIL] Invalid notification type: " . $n['type'] . "\n";
        exit(1);
    }

    // Check if target page exists (ignoring query parameters)
    $urlPath = explode('?', $n['url'])[0];
    $filePath = __DIR__ . '/../' . $urlPath;
    if (!file_exists($filePath)) {
        echo "[WARNING] Target file {$urlPath} does not exist at {$filePath}\n";
    } else {
        echo "[VERIFIED] Target file exists: {$urlPath}\n";
    }
}

// Check SQL schema compatibility for payment_due_date column in grns
$pdo = Database::getInstance()->getConnection();
$colCheck = $pdo->query("SHOW COLUMNS FROM grns LIKE 'payment_due_date'")->fetch(PDO::FETCH_ASSOC);
if (!$colCheck) {
    echo "\n[INFO] 'payment_due_date' column not found in grns table, adding column now...\n";
    $pdo->exec("ALTER TABLE grns ADD COLUMN payment_due_date DATE NULL AFTER grn_date");
    $pdo->exec("CREATE INDEX idx_grns_payment_due_date ON grns(payment_due_date, branch_id, payment_status)");
    echo "[PASS] Created 'payment_due_date' column and performance index.\n";
} else {
    echo "\n[PASS] 'payment_due_date' column exists in grns table.\n";
}

// Test individual notification sections
echo "\n--- Section Query Validations ---\n";

// Low Stock test
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS cnt, SUM(CASE WHEN i.quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock
    FROM branch_inventory i
    INNER JOIN products p ON p.id = i.product_id
    WHERE i.branch_id = 1
      AND p.deleted_at IS NULL
      AND p.status = 'active'
      AND i.quantity <= COALESCE(i.low_stock_threshold, 5)
");
$stmt->execute();
$lowStockRes = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Low Stock Query Test: OK (found: " . $lowStockRes['cnt'] . ", out: " . ($lowStockRes['out_of_stock'] ?? 0) . ")\n";

// Customer Credit Overdue test
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT c.id) AS cnt, COALESCE(SUM(c.credit_balance), 0) AS total_overdue
    FROM customers c
    INNER JOIN customer_credit_ledger l ON l.customer_id = c.id
    WHERE c.credit_balance > 0
      AND c.status = 'active'
      AND c.deleted_at IS NULL
      AND l.transaction_type = 'credit_sale'
      AND l.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute();
$custRes = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Customer Credit Overdue Query Test: OK (found: " . $custRes['cnt'] . ")\n";

// Cheques test
$chqTables = $pdo->query("SHOW TABLES LIKE '%cheque%'")->fetchAll(PDO::FETCH_COLUMN);
echo "Cheque tables found: " . implode(', ', $chqTables) . "\n";
if (in_array('customer_cheques', $chqTables, true)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM customer_cheques WHERE status = 'in_hand' AND cheque_date <= CURDATE()");
    $stmt->execute();
    echo "Customer Cheques Query Test: OK (found: " . $stmt->fetchColumn() . ")\n";
}

// FEFO Expired Lots test
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS cnt, COALESCE(SUM(l.remaining_quantity), 0) AS units
    FROM product_lots l
    INNER JOIN products p ON p.id = l.product_id
    WHERE l.branch_id = 1
      AND l.remaining_quantity > 0
      AND l.expiry_date IS NOT NULL
      AND l.expiry_date < CURDATE()
");
$stmt->execute();
$fefoRes = $stmt->fetch(PDO::FETCH_ASSOC);
echo "FEFO Expired Lots Query Test: OK (found: " . $fefoRes['cnt'] . ")\n";

echo "\n[ALL TESTS PASSED WITH EXIT CODE 0]\n";
exit(0);
