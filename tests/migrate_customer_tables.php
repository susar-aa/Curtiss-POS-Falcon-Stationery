<?php
require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance()->getConnection();
$cols = array_column(Database::fetchAll('DESCRIBE customer_credit_ledger'), 'Field');

if (!in_array('payment_method', $cols, true)) {
    $db->exec("ALTER TABLE customer_credit_ledger ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER transaction_type");
    echo "Added payment_method to customer_credit_ledger\n";
}

if (!in_array('bank_account_id', $cols, true)) {
    $db->exec("ALTER TABLE customer_credit_ledger ADD COLUMN bank_account_id INT UNSIGNED DEFAULT NULL AFTER payment_method");
    echo "Added bank_account_id to customer_credit_ledger\n";
}

$db->exec("ALTER TABLE customer_credit_ledger MODIFY COLUMN transaction_type ENUM('credit_sale','payment','opening_balance','adjustment','cheque_return') NOT NULL DEFAULT 'payment'");
echo "transaction_type enum updated to include cheque_return\n";

// Ensure customer_cheques table exists
$db->exec("CREATE TABLE IF NOT EXISTS customer_cheques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cheque_no VARCHAR(100) NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    bank_name VARCHAR(100) NULL,
    cheque_date DATE NOT NULL,
    received_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_no VARCHAR(100) NULL,
    customer_credit_ledger_id BIGINT UNSIGNED NULL,
    bank_account_id INT UNSIGNED NULL,
    status ENUM('in_hand','deposited','cleared','returned','cancelled') NOT NULL DEFAULT 'in_hand',
    return_reason VARCHAR(255) NULL,
    returned_at DATETIME NULL,
    returned_by INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cust_chq_cust (customer_id),
    INDEX idx_cust_chq_branch (branch_id),
    INDEX idx_cust_chq_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "customer_cheques table verified\n";
