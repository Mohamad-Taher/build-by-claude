<?php
/** AJAX: soft-delete a car and its cost lines (transactional). Blocks sold cars. */
require_once __DIR__ . '/../../includes/auth_check.php';
require_permission('delete', 'cars.php');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(false, t('something_went_wrong'));
}

$stmt = db()->prepare('SELECT status FROM tbl_cars WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$id]);
$car = $stmt->fetch();
if (!$car) {
    json_response(false, t('car_not_found'));
}

// Don't allow deleting a car that has an active (non-cancelled) sale or is sold
$sale = db()->prepare(
    "SELECT id FROM tbl_sales WHERE car_id = ? AND status <> 'cancelled' AND deleted_at IS NULL LIMIT 1"
);
$sale->execute([$id]);
if ($car['status'] === 'sold' || $sale->fetch()) {
    json_response(false, t('cannot_delete_sold'));
}

try {
    db()->beginTransaction();
    db()->prepare('UPDATE tbl_cars SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
    db()->prepare('UPDATE tbl_car_costs SET deleted_at = NOW() WHERE car_id = ? AND deleted_at IS NULL')->execute([$id]);
    db()->commit();
    audit_log('delete', 'tbl_cars', $id, 'Soft-deleted car + cost lines');
    json_response(true, t('deleted_successfully'));
} catch (Throwable $ex) {
    db()->rollBack();
    json_response(false, t('something_went_wrong'));
}
