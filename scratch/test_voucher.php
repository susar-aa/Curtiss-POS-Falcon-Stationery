<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/GiftVoucher.php';

$activeDay = BusinessDay::getActiveDay(1);
if (!$activeDay) {
    BusinessDay::startDay(1, 20000.00, 1);
}

$v = GiftVoucher::createVoucher(100.00, '2026-10-25', 'test');
$p = Sale::prepareDraftSale(['customer_id' => 1, 'items' => [['product_id' => 1, 'lot_id' => 1, 'quantity' => 1, 'unit_price' => 100]]], 1, 1);
$f = Sale::finalizeSale($p['sale_id'], [['payment_method' => 'Voucher', 'amount' => 100, 'reference_no' => $v['voucher_code']]], 1, 1);
print_r($f);
