<?php
/**
 * AJAX: add a cost line to a car (with optional invoice attachment).
 * amount_base is locked at to_base(amount, rate) and never recomputed.
 * Submitted as multipart/form-data (FormData) so a file can be attached.
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_permission('add', 'cars.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

$carId   = (int)($_POST['car_id'] ?? 0);
$typeId  = (int)($_POST['cost_type_id'] ?? 0);
$desc    = trim($_POST['description'] ?? '') ?: null;
$amount  = (float)($_POST['amount'] ?? 0);
$ccy     = strtoupper(trim($_POST['currency'] ?? 'IQD'));
$rate    = (float)($_POST['exchange_rate'] ?? 0);
$invoice = trim($_POST['invoice_number'] ?? '') ?: null;
$cdate   = trim($_POST['cost_date'] ?? '') ?: null;

if ($carId <= 0 || $typeId <= 0 || $amount <= 0 || $rate <= 0) {
    json_response(false, t('required_fields'));
}

// Car + cost type must exist
$cs = db()->prepare('SELECT id FROM tbl_cars WHERE id = ? AND deleted_at IS NULL');
$cs->execute([$carId]);
if (!$cs->fetch()) {
    json_response(false, t('car_not_found'));
}
$ts = db()->prepare('SELECT id FROM tbl_cost_types WHERE id = ?');
$ts->execute([$typeId]);
if (!$ts->fetch()) {
    json_response(false, t('something_went_wrong'));
}
$xs = db()->prepare('SELECT code FROM tbl_currency WHERE code = ?');
$xs->execute([$ccy]);
if (!$xs->fetch()) {
    json_response(false, t('something_went_wrong'));
}

// Optional attachment
try {
    $attachment = handle_upload('attachment');
} catch (RuntimeException $ex) {
    json_response(false, t($ex->getMessage())); // 'invalid_file' | 'upload_failed'
}

$amountBase = to_base($amount, $rate);

$stmt = db()->prepare(
    'INSERT INTO tbl_car_costs
        (car_id, cost_type_id, description, amount, currency, exchange_rate, amount_base,
         invoice_number, attachment, cost_date, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$carId, $typeId, $desc, $amount, $ccy, $rate, $amountBase,
                $invoice, $attachment, $cdate, (int)$_SESSION['user_id']]);
$costId = (int)db()->lastInsertId();

audit_log('create', 'tbl_car_costs', $costId, "Cost line on car {$carId}");
json_response(true, t('saved_successfully'), ['id' => $costId]);
