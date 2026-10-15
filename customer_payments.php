<?php
/**
 * Curtiss POS - Customer Collections (Redirected to Unified Customer Center)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';

Auth::requireLogin();
header('Location: customers.php');
exit;
