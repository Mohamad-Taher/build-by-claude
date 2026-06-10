<?php
/** Destroy the session and return to the login page. */
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    audit_log('logout', 'tbl_users', (int)$_SESSION['user_id'], 'User logged out');
}

$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
