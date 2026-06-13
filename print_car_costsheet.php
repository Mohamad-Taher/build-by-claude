<?php
/**
 * Printable cost breakdown for one car (minimal print layout, no AdminLTE shell).
 * Linked from car_view.php. Full invoice printing lands in Phase 3.
 */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'cars.php');

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM tbl_cars WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$id]);
$car = $stmt->fetch();
if (!$car) {
    die('<h3 style="text-align:center;margin-top:3rem">' . e(t('car_not_found')) . '</h3>');
}

$costStmt = db()->prepare(
    "SELECT cc.*, ct.name AS type_name
       FROM tbl_car_costs cc
       JOIN tbl_cost_types ct ON ct.id = cc.cost_type_id
      WHERE cc.car_id = ? AND cc.deleted_at IS NULL
   ORDER BY cc.cost_type_id = 1 DESC, cc.cost_date, cc.id"
);
$costStmt->execute([$id]);
$costs  = $costStmt->fetchAll();
$landed = car_landed_cost($id);

$sysName = get_setting('system_name', 'Car Import System');
$logo    = get_setting('logo_path', 'dist/img/AdminLTELogo.png');
$dir     = is_rtl() ? 'rtl' : 'ltr';
$title   = trim($car['make'] . ' ' . $car['model']);
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= $dir ?>">
<head>
    <meta charset="utf-8">
    <title><?= e(t('cost_ledger')) ?> — <?= e($title) ?></title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { color: #222; margin: 30px; direction: <?= $dir ?>; }
        .head { display: flex; align-items: center; justify-content: space-between;
                border-bottom: 3px solid #8A3FFC; padding-bottom: 12px; margin-bottom: 18px; }
        .head h1 { margin: 0; font-size: 20px; color: #0B1A3A; }
        .head .brand { display: flex; align-items: center; gap: 10px; }
        .head img { height: 46px; }
        .meta { width: 100%; margin-bottom: 16px; font-size: 13px; }
        .meta td { padding: 3px 6px; }
        .meta th { text-align: <?= is_rtl() ? 'right' : 'left' ?>; padding: 3px 6px; white-space: nowrap; }
        table.costs { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.costs th, table.costs td { border: 1px solid #ccc; padding: 6px 8px; }
        table.costs thead th { background: #0B1A3A; color: #fff; }
        table.costs tfoot th { background: #f0eaff; font-size: 14px; }
        .num { text-align: right; direction: ltr; }
        .footer { margin-top: 24px; font-size: 11px; color: #888; text-align: center; }
        @media print { .noprint { display: none; } body { margin: 10px; } }
    </style>
</head>
<body onload="window.print()">
    <div class="head">
        <div class="brand">
            <img src="<?= e($logo) ?>" alt="">
            <h1><?= e($sysName) ?></h1>
        </div>
        <div><strong><?= e(t('cost_ledger')) ?></strong></div>
    </div>

    <table class="meta">
        <tr>
            <th><?= e(t('car')) ?>:</th><td><?= e($title) ?> <?= e($car['year']) ?></td>
            <th><?= e(t('vin')) ?>:</th><td><?= e($car['vin'] ?: '—') ?></td>
        </tr>
        <tr>
            <th><?= e(t('lot_number')) ?>:</th><td><?= e($car['lot_number'] ?: '—') ?></td>
            <th><?= e(t('status')) ?>:</th><td><?= e(t('status_' . $car['status'])) ?></td>
        </tr>
        <tr>
            <th><?= e(t('supplier')) ?>:</th><td><?= e($car['supplier'] ?: '—') ?></td>
            <th><?= e(t('purchase_date')) ?>:</th><td class="num"><?= e($car['purchase_date'] ?: '—') ?></td>
        </tr>
    </table>

    <table class="costs">
        <thead>
        <tr>
            <th><?= e(t('cost_type')) ?></th>
            <th><?= e(t('description')) ?></th>
            <th><?= e(t('amount')) ?></th>
            <th><?= e(t('amount_in_base')) ?></th>
            <th><?= e(t('invoice_number')) ?></th>
            <th><?= e(t('cost_date')) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($costs as $c): ?>
            <tr>
                <td><?= e($c['type_name']) ?></td>
                <td><?= e($c['description'] ?: '—') ?></td>
                <td class="num"><?= money((float)$c['amount'], $c['currency']) ?></td>
                <td class="num"><?= money((float)$c['amount_base']) ?></td>
                <td><?= e($c['invoice_number'] ?: '—') ?></td>
                <td class="num"><?= e($c['cost_date'] ?: '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="3" class="num"><?= e(t('landed_cost')) ?></th>
            <th class="num"><?= money($landed) ?></th>
            <th colspan="2"></th>
        </tr>
        </tfoot>
    </table>

    <div class="footer">
        <?= e($sysName) ?> — <?= e(t('copyright')) ?> &copy; <?= date('Y') ?> &nbsp;|&nbsp; <?= date('Y-m-d H:i') ?>
    </div>
</body>
</html>
