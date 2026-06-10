<?php
/** AJAX: create or update a user. Returns JSON. */
require_once __DIR__ . '/../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

$id       = (int)($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$password = $_POST['password'] ?? '';
$roleId   = (int)($_POST['role_id'] ?? 0);
$language = in_array($_POST['language'] ?? '', APP_LANGS, true) ? $_POST['language'] : 'en';
$status   = (int)($_POST['status'] ?? 1) === 1 ? 1 : 0;

require_permission($id > 0 ? 'edit' : 'add', 'users.php');

if ($username === '' || $fullName === '' || $roleId <= 0 || ($id === 0 && $password === '')) {
    json_response(false, t('required_fields'));
}

// Username must be unique among non-deleted users
$stmt = db()->prepare(
    'SELECT id FROM tbl_users WHERE username = ? AND deleted_at IS NULL AND id <> ?'
);
$stmt->execute([$username, $id]);
if ($stmt->fetch()) {
    json_response(false, t('username') . ' — ' . t('error'));
}

if ($id > 0) {
    if ($password !== '') {
        $stmt = db()->prepare(
            'UPDATE tbl_users
                SET username = ?, full_name = ?, password_hash = ?, role_id = ?, language = ?, status = ?
              WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$username, $fullName, password_hash($password, PASSWORD_DEFAULT),
                        $roleId, $language, $status, $id]);
    } else {
        $stmt = db()->prepare(
            'UPDATE tbl_users
                SET username = ?, full_name = ?, role_id = ?, language = ?, status = ?
              WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$username, $fullName, $roleId, $language, $status, $id]);
    }
    audit_log('update', 'tbl_users', $id, 'Updated user ' . $username);
} else {
    $stmt = db()->prepare(
        'INSERT INTO tbl_users (username, password_hash, full_name, role_id, language, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT),
                    $fullName, $roleId, $language, $status]);
    $id = (int)db()->lastInsertId();
    audit_log('create', 'tbl_users', $id, 'Created user ' . $username);
}

json_response(true, t('saved_successfully'), ['id' => $id]);
