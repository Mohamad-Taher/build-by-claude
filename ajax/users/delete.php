<?php
/** AJAX: soft-delete a user. Returns JSON. */
require_once __DIR__ . '/../../includes/auth_check.php';
require_permission('delete', 'users.php');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(false, t('something_went_wrong'));
}
if ($id === (int)$_SESSION['user_id']) {
    json_response(false, t('no_permission')); // cannot delete yourself
}

$stmt = db()->prepare('UPDATE tbl_users SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$id]);

audit_log('delete', 'tbl_users', $id, 'Soft-deleted user');
json_response(true, t('deleted_successfully'));
