<?php
/** AJAX: change a car's pipeline status. */
require_once __DIR__ . '/../../includes/auth_check.php';
require_permission('edit', 'cars.php');

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id <= 0 || !in_array($status, car_statuses(), true)) {
    json_response(false, t('something_went_wrong'));
}

$stmt = db()->prepare('SELECT id FROM tbl_cars WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json_response(false, t('car_not_found'));
}

db()->prepare('UPDATE tbl_cars SET status = ? WHERE id = ?')->execute([$status, $id]);
audit_log('update', 'tbl_cars', $id, 'Status -> ' . $status);

json_response(true, t('status_updated'));
