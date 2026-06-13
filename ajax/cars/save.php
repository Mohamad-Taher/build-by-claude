<?php
/**
 * AJAX: create or update a car.
 * The purchase price is mirrored into tbl_car_costs as a "Purchase Price"
 * cost line (cost_type_id = 1) so the landed cost stays purely the sum of
 * the cost ledger — no separate manual total that could drift.
 */
require_once __DIR__ . '/../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

const PURCHASE_COST_TYPE_ID = 1;

$id        = (int)($_POST['id'] ?? 0);
require_permission($id > 0 ? 'edit' : 'add', 'cars.php');

$lot       = trim($_POST['lot_number'] ?? '') ?: null;
$vin       = trim($_POST['vin'] ?? '') ?: null;
$make      = trim($_POST['make'] ?? '');
$model     = trim($_POST['model'] ?? '');
$year      = (int)($_POST['year'] ?? 0) ?: null;
$color     = trim($_POST['color'] ?? '') ?: null;
$supplier  = trim($_POST['supplier'] ?? '') ?: null;
$status    = $_POST['status'] ?? 'purchased';
$notes     = trim($_POST['notes'] ?? '') ?: null;

$price     = (float)($_POST['purchase_price'] ?? 0);
$currency  = strtoupper(trim($_POST['purchase_currency'] ?? 'USD'));
$rate      = (float)($_POST['purchase_rate'] ?? 0);
$pdate     = trim($_POST['purchase_date'] ?? '') ?: null;

// --- Server-side validation ---
if ($make === '' || $model === '') {
    json_response(false, t('required_fields'));
}
if (!in_array($status, car_statuses(), true)) {
    json_response(false, t('something_went_wrong'));
}
if ($price < 0 || $rate <= 0) {
    json_response(false, t('required_fields'));
}
// Currency must exist
$cs = db()->prepare('SELECT code FROM tbl_currency WHERE code = ?');
$cs->execute([$currency]);
if (!$cs->fetch()) {
    json_response(false, t('something_went_wrong'));
}

$priceBase = to_base($price, $rate);
$uid       = (int)$_SESSION['user_id'];

try {
    db()->beginTransaction();

    if ($id > 0) {
        $stmt = db()->prepare(
            'UPDATE tbl_cars
                SET lot_number = ?, vin = ?, make = ?, model = ?, year = ?, color = ?,
                    purchase_price = ?, purchase_currency = ?, purchase_rate = ?, purchase_price_base = ?,
                    purchase_date = ?, supplier = ?, status = ?, notes = ?
              WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$lot, $vin, $make, $model, $year, $color,
                        $price, $currency, $rate, $priceBase, $pdate, $supplier, $status, $notes, $id]);

        // Keep the mirrored purchase cost line in sync (update or create)
        $find = db()->prepare(
            'SELECT id FROM tbl_car_costs
              WHERE car_id = ? AND cost_type_id = ? AND deleted_at IS NULL
           ORDER BY id LIMIT 1'
        );
        $find->execute([$id, PURCHASE_COST_TYPE_ID]);
        $costId = $find->fetchColumn();

        if ($costId) {
            $upd = db()->prepare(
                'UPDATE tbl_car_costs
                    SET amount = ?, currency = ?, exchange_rate = ?, amount_base = ?, cost_date = ?
                  WHERE id = ?'
            );
            $upd->execute([$price, $currency, $rate, $priceBase, $pdate, $costId]);
        } else {
            $ins = db()->prepare(
                'INSERT INTO tbl_car_costs
                    (car_id, cost_type_id, description, amount, currency, exchange_rate, amount_base, cost_date, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$id, PURCHASE_COST_TYPE_ID, 'Purchase Price', $price, $currency, $rate, $priceBase, $pdate, $uid]);
        }
        audit_log('update', 'tbl_cars', $id, "Updated car {$make} {$model}");
    } else {
        $stmt = db()->prepare(
            'INSERT INTO tbl_cars
                (lot_number, vin, make, model, year, color,
                 purchase_price, purchase_currency, purchase_rate, purchase_price_base,
                 purchase_date, supplier, status, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$lot, $vin, $make, $model, $year, $color,
                        $price, $currency, $rate, $priceBase, $pdate, $supplier, $status, $notes, $uid]);
        $id = (int)db()->lastInsertId();

        // Mirror purchase price into the cost ledger
        $ins = db()->prepare(
            'INSERT INTO tbl_car_costs
                (car_id, cost_type_id, description, amount, currency, exchange_rate, amount_base, cost_date, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$id, PURCHASE_COST_TYPE_ID, 'Purchase Price', $price, $currency, $rate, $priceBase, $pdate, $uid]);
        audit_log('create', 'tbl_cars', $id, "Created car {$make} {$model}");
    }

    db()->commit();
    json_response(true, t('saved_successfully'), ['id' => $id]);
} catch (Throwable $ex) {
    db()->rollBack();
    json_response(false, t('something_went_wrong'));
}
