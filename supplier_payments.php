<?php
/**
 * Curtiss POS - Supplier Payments Redirect
 * Supplier payments and outstanding ledgers are integrated directly into Supplier Management.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';

Auth::requireAuth();

header('Location: suppliers.php', true, 302);
exit;
