<?php
/**
 * Curtiss POS - Accounting Core Forwarder
 * Redirects legacy links to Journal Entries page.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';

Auth::requireLogin();
header('Location: journal_entries.php');
exit;
