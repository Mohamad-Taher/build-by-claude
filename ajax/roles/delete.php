<?php
/** AJAX: soft-delete a role (only when no active users use it). */
require_once __DIR__ . '/../../includes/auth_check.php';
require_permission('delete', 'roles.php');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(false, t('something_went_wrong'));
}

// Block deleting a role that still has active users
$stmt = db()->prepare('SELECT COUNT(*) c FROM tbl_users WHERE role_id = ? AND deleted_at IS NULL');
$stmt->execute([$id]);
if ((int)$stmt->fetch()['c'] > 0) {
    json_response(false, t('no_permission'));
}

$stmt = db()->prepare('UPDATE tbl_role SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$id]);

audit_log('delete', 'tbl_role', $id, 'Soft-deleted role');
json_response(true, t('deleted_successfully'));
