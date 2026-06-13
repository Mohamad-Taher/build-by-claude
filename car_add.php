<?php
/** Add / edit a car. Edit mode when ?id=N. */
require_once __DIR__ . '/includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
require_permission($id > 0 ? 'edit' : 'add', 'cars.php');

$car = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM tbl_cars WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $car = $stmt->fetch();
    if (!$car) {
        die('<h3 style="text-align:center;margin-top:3rem">' . e(t('car_not_found')) . '</h3>');
    }
}

$currencies = db()->query('SELECT code, name FROM tbl_currency ORDER BY code')->fetchAll();
$defaultRate = get_setting('default_exchange_rate', '1450');

$page_title    = $id > 0 ? t('edit_car') : t('add_car');
$active_page   = 'cars.php'; // keep "Cars" highlighted in the sidebar
require_once __DIR__ . '/header.php';

// Field value helper
$v = function (string $k, $default = '') use ($car) {
    return $car[$k] ?? $default;
};
?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1 class="m-0"><?= e($page_title) ?></h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php"><?= e(t('home')) ?></a></li>
                            <li class="breadcrumb-item"><a href="cars.php"><?= e(t('cars')) ?></a></li>
                            <li class="breadcrumb-item active"><?= e($page_title) ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <form id="carForm" autocomplete="off">
                    <input type="hidden" name="id" value="<?= (int)$id ?>">

                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-car mr-1"></i> <?= e(t('car_details')) ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('lot_number')) ?></label>
                                    <input type="text" name="lot_number" class="form-control" value="<?= e($v('lot_number')) ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('vin')) ?></label>
                                    <input type="text" name="vin" class="form-control" value="<?= e($v('vin')) ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('make')) ?> *</label>
                                    <input type="text" name="make" class="form-control" value="<?= e($v('make')) ?>" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('model')) ?> *</label>
                                    <input type="text" name="model" class="form-control" value="<?= e($v('model')) ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('year')) ?></label>
                                    <input type="number" name="year" class="form-control" min="1900" max="2100"
                                           value="<?= e($v('year')) ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('color')) ?></label>
                                    <input type="text" name="color" class="form-control" value="<?= e($v('color')) ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('supplier')) ?></label>
                                    <input type="text" name="supplier" class="form-control"
                                           value="<?= e($v('supplier', 'Copart')) ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('status')) ?></label>
                                    <select name="status" class="form-control">
                                        <?php $curStatus = $v('status', 'purchased');
                                        foreach (car_statuses() as $s): ?>
                                            <option value="<?= $s ?>" <?= $s === $curStatus ? 'selected' : '' ?>>
                                                <?= e(t('status_' . $s)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-dollar-sign mr-1"></i> <?= e(t('purchase_price')) ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('purchase_price')) ?> *</label>
                                    <input type="number" step="0.01" min="0" name="purchase_price" id="purchase_price"
                                           class="form-control" value="<?= e($v('purchase_price', '0')) ?>" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('purchase_currency')) ?> *</label>
                                    <select name="purchase_currency" id="purchase_currency" class="form-control">
                                        <?php $curCcy = $v('purchase_currency', 'USD');
                                        foreach ($currencies as $c): ?>
                                            <option value="<?= e($c['code']) ?>" <?= $c['code'] === $curCcy ? 'selected' : '' ?>>
                                                <?= e($c['code']) ?> — <?= e($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('exchange_rate')) ?> *</label>
                                    <input type="number" step="0.000001" min="0" name="purchase_rate" id="purchase_rate"
                                           class="form-control"
                                           value="<?= e($v('purchase_rate', $defaultRate)) ?>" required>
                                    <small class="text-muted">1 USD = ? IQD</small>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('purchase_date')) ?></label>
                                    <input type="date" name="purchase_date" class="form-control"
                                           value="<?= e($v('purchase_date')) ?>">
                                </div>
                            </div>
                            <div class="callout callout-info mb-0">
                                <?= e(t('amount_in_base')) ?>:
                                <strong id="basePreview" class="ltr-nums">0 IQD</strong>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-secondary">
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label><?= e(t('notes')) ?></label>
                                <textarea name="notes" class="form-control" rows="2"><?= e($v('notes')) ?></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= e(t('save')) ?>
                            </button>
                            <a href="cars.php" class="btn btn-default">
                                <i class="fas fa-arrow-left"></i> <?= e(t('back_to_list')) ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

<?php
$extra_js = '<script>
var LBL_ERROR = ' . json_encode(t('something_went_wrong'), JSON_UNESCAPED_UNICODE) . ';
</script>' . <<<'JS'
<script>
$(function () {
    // Live "amount in base" preview (purchase price * rate)
    function updateBase() {
        var price = parseFloat($('#purchase_price').val()) || 0;
        var rate  = parseFloat($('#purchase_rate').val()) || 0;
        var base  = Math.round(price * rate);
        $('#basePreview').text(base.toLocaleString('en-US') + ' IQD');
    }
    $('#purchase_price, #purchase_rate').on('input', updateBase);
    updateBase();

    $('#carForm').on('submit', function (e) {
        e.preventDefault();
        $.post('ajax/cars/save.php', $(this).serialize(), function (res) {
            if (res.success) {
                swalSuccess(res.message);
                setTimeout(function () { window.location = 'car_view.php?id=' + res.id; }, 1000);
            } else {
                swalError(res.message);
            }
        }, 'json').fail(function () { swalError(LBL_ERROR); });
    });
});
</script>
JS;

require_once __DIR__ . '/footer.php';
