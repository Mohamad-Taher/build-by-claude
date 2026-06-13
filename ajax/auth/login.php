<?php
/** AJAX: authenticate a user and open the session. Returns JSON. */
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    json_response(false, t('required_fields'));
}

$stmt = db()->prepare(
    'SELECT u.*, r.role_name
       FROM tbl_users u
       JOIN tbl_role r ON r.id = u.role_id
      WHERE u.username = ? AND u.deleted_at IS NULL'
);
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(false, t('invalid_credentials'));
}
if ((int)$user['status'] !== 1) {
    json_response(false, t('account_inactive'));
}

session_regenerate_id(true);
$_SESSION['user_id']   = (int)$user['id'];
$_SESSION['username']  = $user['username'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role_id']   = (int)$user['role_id'];
$_SESSION['role']      = $user['role_name'];
$_SESSION['role_name'] = $user['role_name'];
$_SESSION['lang']      = in_array($user['language'], APP_LANGS, true)
    ? $user['language']
    : get_setting('default_language', 'en');

audit_log('login', 'tbl_users', (int)$user['id'], 'User logged in');

json_response(true, 'OK');
