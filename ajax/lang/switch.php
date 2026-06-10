<?php
/** AJAX: switch the active UI language (session + user profile). */
require_once __DIR__ . '/../../includes/auth_check.php';

$lang = $_POST['lang'] ?? '';
if (!in_array($lang, APP_LANGS, true)) {
    json_response(false, 'Unsupported language');
}

$_SESSION['lang'] = $lang;

$stmt = db()->prepare('UPDATE tbl_users SET language = ? WHERE id = ?');
$stmt->execute([$lang, $_SESSION['user_id']]);

json_response(true, 'OK');
