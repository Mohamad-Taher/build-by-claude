<?php
/** AJAX: create or update a role. Returns JSON. */
require_once __DIR__ . '/../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

$id   = (int)($_POST['id'] ?? 0);
$name = trim($_POST['role_name'] ?? '');

require_permission($id > 0 ? 'edit' : 'add', 'roles.php');

if ($name === '') {
    json_response(false, t('required_fields'));
}

if ($id > 0) {
    $stmt = db()->prepare('UPDATE tbl_role SET role_name = ? WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$name, $id]);
    audit_log('update', 'tbl_role', $id, 'Updated role ' . $name);
} else {
    $stmt = db()->prepare('INSERT INTO tbl_role (role_name) VALUES (?)');
    $stmt->execute([$name]);
    $id = (int)db()->lastInsertId();
    audit_log('create', 'tbl_role', $id, 'Created role ' . $name);
}

json_response(true, t('saved_successfully'), ['id' => $id]);
