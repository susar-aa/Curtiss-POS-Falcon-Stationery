<?php
/**
 * Curtiss POS - Navigation Notifications API
 * 
 * Aggregates real system notifications:
 * - Low-stock & Out-of-stock items
 * - Supplier GRN Payment Due (within 7 days & critical overdue > 30 days)
 * - Customer credit overdue > 30 days
 * - Incoming Stock Transfers awaiting reception confirmation
 * - FEFO Expiry (expired & soon-to-expire lots)
 * - Customer cheques mature / due for banking
 * - Supplier post-dated cheques maturing
 * - Overnight unclosed business days
 * - Overdue purchase orders
 * - Outstanding employee salary advances
 * - Branch chat messages
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/Logger.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$user      = Auth::user();
$isSpecial = ($user['role_code'] ?? '') === ROLE_SPECIAL_USER;
$db        = Database::getInstance();
$pdo       = $db->getConnection();
$branchId  = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$currency  = Settings::get('currency_symbol', 'LKR');

$notifications = [];

if ($isSpecial) {
    echo json_encode(['ok' => true, 'notifications' => [], 'unread_count' => 0]);
    exit;
}

try {
    // ─── 1. Inbound Stock Transfers Awaiting Confirmation ────────────────────
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt, GROUP_CONCAT(b.name SEPARATOR ', ') AS from_branches
            FROM stock_transfers t
            INNER JOIN branches b ON b.id = t.from_branch_id
            WHERE t.to_branch_id = :bid AND t.status = 'sent'
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'stock_transfer_inbound',
                'title'      => 'Incoming Stock Transfer',
                'badge'      => 'Inbound',
                'text'       => (int)$row['cnt'] . ' stock transfer(s) arrived from ' . htmlspecialchars($row['from_branches'] ?: 'other branch') . ' awaiting receipt confirmation.',
                'time_label' => 'Transfers',
                'url'        => 'stock_transfers.php',
                'type'       => 'info',
                'icon'       => '📦',
                'unread'     => true,
            ];
        }
    }

    // ─── 2. Supplier GRN Payment Alerts (Due in 7 days & Overdue > 30 days) ───
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
        // Critical Overdue (> 30 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(actual_grn_total - paid_amount), 0) AS total_due
            FROM grns
            WHERE branch_id = :bid
              AND status = 'Approved'
              AND payment_status != 'paid'
              AND payment_due_date IS NOT NULL
              AND payment_due_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'grn_overdue_30',
                'title'      => 'Supplier Payment Overdue',
                'badge'      => 'Overdue 30d+',
                'text'       => (int)$row['cnt'] . ' supplier invoice(s) overdue by > 30 days (' . $currency . ' ' . number_format((float)$row['total_due'], 2) . ').',
                'time_label' => 'Purchasing',
                'url'        => 'supplier_payments.php',
                'type'       => 'critical',
                'icon'       => '🛑',
                'unread'     => true,
            ];
        }

        // Upcoming Due (Next 7 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(actual_grn_total - paid_amount), 0) AS total_due
            FROM grns
            WHERE branch_id = :bid
              AND status = 'Approved'
              AND payment_status != 'paid'
              AND payment_due_date IS NOT NULL
              AND payment_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'grn_due_7',
                'title'      => 'Supplier Payment Due Soon',
                'badge'      => 'Due in 7d',
                'text'       => (int)$row['cnt'] . ' supplier invoice(s) due within 7 days (' . $currency . ' ' . number_format((float)$row['total_due'], 2) . ').',
                'time_label' => 'Purchasing',
                'url'        => 'supplier_payments.php',
                'type'       => 'warning',
                'icon'       => '📅',
                'unread'     => true,
            ];
        }
    }

    // ─── 3. Customer Credit Overdue (> 30 Days) ──────────────────────────────
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.id) AS cnt, COALESCE(SUM(c.credit_balance), 0) AS total_balance
            FROM customers c
            INNER JOIN customer_credit_ledger l ON l.customer_id = c.id
            WHERE c.credit_balance > 0
              AND c.status = 'active'
              AND c.deleted_at IS NULL
              AND l.transaction_type = 'credit_sale'
              AND l.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'customer_credit_overdue',
                'title'      => 'Customer Credit Overdue',
                'badge'      => 'Overdue 30d+',
                'text'       => (int)$row['cnt'] . ' customer(s) have credit balances unpaid for > 30 days (' . $currency . ' ' . number_format((float)$row['total_balance'], 2) . ').',
                'time_label' => 'Credit',
                'url'        => 'customer_payments.php',
                'type'       => 'critical',
                'icon'       => '💳',
                'unread'     => true,
            ];
        }
    }

    // ─── 4. Low Stock & Out of Stock Alerts ──────────────────────────────────
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
        // Out of Stock
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM branch_inventory i
            INNER JOIN products p ON p.id = i.product_id
            WHERE i.branch_id = :bid
              AND i.quantity <= 0
              AND p.deleted_at IS NULL
              AND p.status = 'active'
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'out_of_stock',
                'title'      => 'Stock Depleted',
                'badge'      => 'Out of Stock',
                'text'       => (int)$row['cnt'] . ' product(s) are completely out of stock.',
                'time_label' => 'Inventory',
                'url'        => 'reports.php?report=low_stock',
                'type'       => 'critical',
                'icon'       => '🚨',
                'unread'     => true,
            ];
        }

        // Low Stock
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM branch_inventory i
            INNER JOIN products p ON p.id = i.product_id
            WHERE i.branch_id = :bid
              AND i.quantity <= COALESCE(i.low_stock_threshold, 5)
              AND i.quantity > 0
              AND p.deleted_at IS NULL
              AND p.status = 'active'
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'low_stock',
                'title'      => 'Low Stock Warning',
                'badge'      => 'Low Stock',
                'text'       => (int)$row['cnt'] . ' product(s) are running low on stock.',
                'time_label' => 'Inventory',
                'url'        => 'reports.php?report=low_stock',
                'type'       => 'warning',
                'icon'       => '⚠️',
                'unread'     => true,
            ];
        }
    }

    // ─── 5. FEFO Batch Expiry Alerts (Expired & Expiring within 30 days) ──────
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired_cnt,
                SUM(CASE WHEN expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS soon_cnt
            FROM product_lots
            WHERE branch_id = :bid
              AND remaining_quantity > 0
              AND status = 'active'
              AND expiry_date IS NOT NULL
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && (int)($row['expired_cnt'] ?? 0) > 0) {
            $notifications[] = [
                'id'         => 'lots_expired',
                'title'      => 'Expired Lots in Stock',
                'badge'      => 'Expired',
                'text'       => (int)$row['expired_cnt'] . ' lot batch(es) have expired and must be removed.',
                'time_label' => 'Expiry',
                'url'        => 'expired_stock.php',
                'type'       => 'critical',
                'icon'       => '⏳',
                'unread'     => true,
            ];
        }

        if ($row && (int)($row['soon_cnt'] ?? 0) > 0) {
            $notifications[] = [
                'id'         => 'lots_expiring_soon',
                'title'      => 'Batches Expiring Soon',
                'badge'      => 'FEFO 30d',
                'text'       => (int)$row['soon_cnt'] . ' lot batch(es) will expire within the next 30 days.',
                'time_label' => 'Expiry',
                'url'        => 'expired_stock.php',
                'type'       => 'warning',
                'icon'       => '⏳',
                'unread'     => true,
            ];
        }
    }

    // ─── 6. Customer Cheques Due for Banking ──────────────────────────────────
    if (Auth::hasPermission('view_cheques') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT, ROLE_MANAGER)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total_amt
            FROM customer_cheques
            WHERE branch_id = :bid
              AND status = 'in_hand'
              AND cheque_date <= CURDATE()
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'cheques_due',
                'title'      => 'Cheques Due for Deposit',
                'badge'      => 'Banking',
                'text'       => (int)$row['cnt'] . ' customer cheque(s) mature/due for banking (' . $currency . ' ' . number_format((float)$row['total_amt'], 2) . ').',
                'time_label' => 'Banking',
                'url'        => 'cheque_management.php',
                'type'       => 'warning',
                'icon'       => '🏦',
                'unread'     => true,
            ];
        }
    }

    // ─── 7. Supplier Issued Cheques Maturing Soon (Next 3 Days) ──────────────
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total_amt
            FROM supplier_cheques
            WHERE branch_id = :bid
              AND status = 'pending'
              AND cheque_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'supplier_cheques_maturing',
                'title'      => 'Supplier Cheques Clearing',
                'badge'      => 'Clearing 3d',
                'text'       => (int)$row['cnt'] . ' issued supplier cheque(s) scheduled to clear bank in 3 days (' . $currency . ' ' . number_format((float)$row['total_amt'], 2) . ').',
                'time_label' => 'Banking',
                'url'        => 'bank_accounts.php',
                'type'       => 'info',
                'icon'       => '🧾',
                'unread'     => true,
            ];
        }
    }

    // ─── 8. Overnight Unclosed Business Day ──────────────────────────────────
    if (Auth::hasPermission('view_cash_drawer') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_CASHIER)) {
        $stmt = $pdo->prepare("
            SELECT id, business_date
            FROM business_days
            WHERE branch_id = :bid
              AND status = 'open'
              AND business_date < CURDATE()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $notifications[] = [
                'id'         => 'unclosed_business_day',
                'title'      => 'Unclosed Business Day',
                'badge'      => 'Register',
                'text'       => 'Register from ' . htmlspecialchars($row['business_date']) . ' (#Day ' . (int)$row['id'] . ') is still open. Please perform day closing.',
                'time_label' => 'Cash Drawer',
                'url'        => 'cash_drawer.php',
                'type'       => 'critical',
                'icon'       => '💵',
                'unread'     => true,
            ];
        }
    }

    // ─── 9. Overdue Purchase Orders (Supplier Delivery Overdue) ──────────────
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM purchase_orders
            WHERE branch_id = :bid
              AND status = 'Approved'
              AND expected_date < CURDATE()
              AND deleted_at IS NULL
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'po_overdue',
                'title'      => 'Overdue Purchase Orders',
                'badge'      => 'Procurement',
                'text'       => (int)$row['cnt'] . ' purchase order(s) have passed expected supplier delivery date.',
                'time_label' => 'Purchasing',
                'url'        => 'purchase_orders.php',
                'type'       => 'warning',
                'icon'       => '📋',
                'unread'     => true,
            ];
        }
    }

    // ─── 10. Outstanding Employee Salary Advances ────────────────────────────
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT employee_id) AS cnt, COALESCE(SUM(remaining_amount), 0) AS total_remaining
            FROM employee_advances
            WHERE branch_id = :bid
              AND status = 'active'
              AND remaining_amount > 0
        ");
        $stmt->execute([':bid' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'employee_advances',
                'title'      => 'Salary Advances to Deduct',
                'badge'      => 'Payroll',
                'text'       => (int)$row['cnt'] . ' employee(s) have salary advances to deduct from monthly payroll (' . $currency . ' ' . number_format((float)$row['total_remaining'], 2) . ').',
                'time_label' => 'Payroll',
                'url'        => 'payroll.php',
                'type'       => 'info',
                'icon'       => '💼',
                'unread'     => true,
            ];
        }
    }

    // ─── 11. Unread Branch Chat Messages ─────────────────────────────────────
    if ($branchId > 0) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM employee_chat
            WHERE branch_id = :bid
              AND sender_id != :uid
              AND sent_at >= NOW() - INTERVAL 1 HOUR
        ");
        $stmt->execute([':bid' => $branchId, ':uid' => (int)$user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['cnt'] > 0) {
            $notifications[] = [
                'id'         => 'chat_unread',
                'title'      => 'Branch Chat',
                'badge'      => 'Chat',
                'text'       => (int)$row['cnt'] . ' new message(s) in Branch Chat.',
                'time_label' => 'Chat',
                'url'        => 'javascript:void(0)',
                'type'       => 'info',
                'icon'       => '💬',
                'unread'     => true,
            ];
        }
    }

} catch (Throwable $e) {
    Logger::exception($e);
}

echo json_encode([
    'ok'            => true,
    'notifications' => $notifications,
    'unread_count'  => count(array_filter($notifications, fn($n) => !empty($n['unread']))),
]);
