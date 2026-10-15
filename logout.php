<?php
/**
 * Curtiss POS - Logout Controller
 */

require_once __DIR__ . '/config/config.php';

Auth::logout();
header('Location: login?loggedout=1');
exit();
