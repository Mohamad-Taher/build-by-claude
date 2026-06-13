<?php
/**
 * AJAX: soft-delete a cost line. The mirrored "Purchase Price" line
 * (cost_type_id = 1) is managed from the car form and cannot be deleted here.
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_permission('delete', 'cars.php');

const PURCHASE_COST_TYPE_ID = 1;

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(false, t('something_went_wrong'));
}

$stmt = db()->prepare('SELECT cost_type_id FROM tbl_car_costs WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$id]);
$cost = $stmt->fetch();
if (!$cost) {
    json_response(false, t('something_went_wrong'));
}
if ((int)$cost['cost_type_id'] === PURCHASE_COST_TYPE_ID) {
    json_response(false, t('purchase_line_note'));
}

db()->prepare('UPDATE tbl_car_costs SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
audit_log('delete', 'tbl_car_costs', $id, 'Soft-deleted cost line');

json_response(true, t('deleted_successfully'));
