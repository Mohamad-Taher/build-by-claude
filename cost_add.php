<?php
/**
 * Standalone "add a cost to a car" page (?car_id=N).
 * Same endpoint as the car_view modal; provided for direct/page access.
 */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('add', 'cars.php');

$carId = (int)($_GET['car_id'] ?? 0);
$stmt = db()->prepare('SELECT id, make, model FROM tbl_cars WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$carId]);
$car = $stmt->fetch();
if (!$car) {
    die('<h3 style="text-align:center;margin-top:3rem">' . e(t('car_not_found')) . '</h3>');
}

$costTypes   = db()->query('SELECT id, name FROM tbl_cost_types WHERE id <> 1 ORDER BY name')->fetchAll();
$currencies  = db()->query('SELECT code, name FROM tbl_currency ORDER BY code')->fetchAll();

$page_title  = t('add_cost');
$active_page = 'cars.php';
require_once __DIR__ . '/header.php';
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
                            <li class="breadcrumb-item">
                                <a href="car_view.php?id=<?= $carId ?>"><?= e(trim($car['make'] . ' ' . $car['model'])) ?></a>
                            </li>
                            <li class="breadcrumb-item active"><?= e($page_title) ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus mr-1"></i> <?= e(t('add_cost')) ?></h3>
                    </div>
                    <form id="costForm" enctype="multipart/form-data" autocomplete="off">
                        <div class="card-body">
                            <input type="hidden" name="car_id" value="<?= $carId ?>">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label><?= e(t('cost_type')) ?> *</label>
                                    <select class="form-control select2" name="cost_type_id" style="width:100%" required>
                                        <option value=""><?= e(t('select_cost_type')) ?></option>
                                        <?php foreach ($costTypes as $ct): ?>
                                            <option value="<?= (int)$ct['id'] ?>"><?= e($ct['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label><?= e(t('description')) ?></label>
                                    <input type="text" class="form-control" name="description">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('amount')) ?> *</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="amount" id="cost_amount" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('currency')) ?> *</label>
                                    <select class="form-control" name="currency" id="cost_currency">
                                        <?php foreach ($currencies as $c): ?>
                                            <option value="<?= e($c['code']) ?>" <?= $c['code'] === 'IQD' ? 'selected' : '' ?>>
                                                <?= e($c['code']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('exchange_rate')) ?> *</label>
                                    <input type="number" step="0.000001" min="0" class="form-control" name="exchange_rate" id="cost_rate" value="1" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><?= e(t('cost_date')) ?></label>
                                    <input type="date" class="form-control" name="cost_date" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="callout callout-info py-2">
                                <?= e(t('amount_in_base')) ?>: <strong id="costBasePreview" class="ltr-nums">0 IQD</strong>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label><?= e(t('invoice_number')) ?></label>
                                    <input type="text" class="form-control" name="invoice_number">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label><?= e(t('attachment')) ?></label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="attachment" id="cost_attachment"
                                               accept=".jpg,.jpeg,.png,.gif,.pdf">
                                        <label class="custom-file-label" for="cost_attachment"><?= e(t('select')) ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= e(t('save')) ?></button>
                            <a href="car_view.php?id=<?= $carId ?>" class="btn btn-default">
                                <i class="fas fa-arrow-left"></i> <?= e(t('back_to_list')) ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

<?php
$extra_js = '<script>
var CAR_ID = ' . $carId . ';
var LBL_ERROR = ' . json_encode(t('something_went_wrong'), JSON_UNESCAPED_UNICODE) . ';
</script>' . <<<'JS'
<script>
$(function () {
    function updateCostBase() {
        var amt = parseFloat($('#cost_amount').val()) || 0;
        var rate = parseFloat($('#cost_rate').val()) || 0;
        $('#costBasePreview').text(Math.round(amt * rate).toLocaleString('en-US') + ' IQD');
    }
    $('#cost_currency').on('change', function () {
        if ($(this).val() === 'IQD') { $('#cost_rate').val(1); }
        updateCostBase();
    });
    $('#cost_amount, #cost_rate').on('input', updateCostBase);
    $('#cost_attachment').on('change', function () {
        if (this.files.length) { $(this).next('.custom-file-label').text(this.files[0].name); }
    });

    $('#costForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        $.ajax({
            url: 'ajax/costs/save.php', type: 'POST', data: fd,
            processData: false, contentType: false, dataType: 'json',
            success: function (res) {
                if (res.success) {
                    swalSuccess(res.message);
                    setTimeout(function () { window.location = 'car_view.php?id=' + CAR_ID; }, 1000);
                } else { swalError(res.message); }
            },
            error: function () { swalError(LBL_ERROR); }
        });
    });
});
</script>
JS;

require_once __DIR__ . '/footer.php';
