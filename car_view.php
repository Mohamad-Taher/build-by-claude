<?php
/**
 * Car detail hub: details, status control, cost ledger (with add/delete),
 * computed landed cost, and profit (once a sale exists — sales arrive in Phase 3).
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

// Cost ledger
$costStmt = db()->prepare(
    "SELECT cc.*, ct.name AS type_name
       FROM tbl_car_costs cc
       JOIN tbl_cost_types ct ON ct.id = cc.cost_type_id
      WHERE cc.car_id = ? AND cc.deleted_at IS NULL
   ORDER BY cc.cost_type_id = 1 DESC, cc.cost_date, cc.id"
);
$costStmt->execute([$id]);
$costs = $costStmt->fetchAll();

$landed = car_landed_cost($id);

// Sale (if any) — full UI in Phase 3; here we just compute profit when present
$saleStmt = db()->prepare(
    "SELECT * FROM tbl_sales WHERE car_id = ? AND status <> 'cancelled' AND deleted_at IS NULL
     ORDER BY id DESC LIMIT 1"
);
$saleStmt->execute([$id]);
$sale = $saleStmt->fetch();
$profit = $sale ? ((float)$sale['sale_price_base'] - $landed) : null;

// Cost types for the add-cost modal (exclude the auto-managed Purchase Price line)
$costTypes = db()->query('SELECT id, name FROM tbl_cost_types WHERE id <> 1 ORDER BY name')->fetchAll();
$currencies = db()->query('SELECT code, name FROM tbl_currency ORDER BY code')->fetchAll();
$defaultRate = get_setting('default_exchange_rate', '1450');

$page_title  = trim($car['make'] . ' ' . $car['model']);
$active_page = 'cars.php';
require_once __DIR__ . '/header.php';

$canAddCost    = can('add', 'cars.php');
$canDeleteCost = can('delete', 'cars.php');
$canEdit       = can('edit', 'cars.php');
?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1 class="m-0"><?= e($page_title) ?> <?= status_badge($car['status']) ?></h1></div>
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

                <!-- KPI row: landed cost, sale price, profit -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= e(t('landed_cost')) ?></span>
                                <span class="info-box-number money"><?= money($landed) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-tag"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= e(t('sale_price')) ?></span>
                                <span class="info-box-number money">
                                    <?= $sale ? money((float)$sale['sale_price_base']) : '—' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon <?= $profit === null ? 'bg-secondary' : ($profit >= 0 ? 'bg-success' : 'bg-danger') ?>">
                                <i class="fas fa-chart-line"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= e(t('profit')) ?></span>
                                <span class="info-box-number money">
                                    <?= $profit === null ? '<small class="text-muted">' . e(t('profit_pending')) . '</small>' : money($profit) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left: details + status -->
                    <div class="col-md-4">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-car mr-1"></i> <?= e(t('car_details')) ?></h3>
                                <?php if ($canEdit): ?>
                                    <div class="card-tools">
                                        <a href="car_add.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> <?= e(t('edit')) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <tr><th><?= e(t('lot_number')) ?></th><td><?= e($car['lot_number'] ?: '—') ?></td></tr>
                                    <tr><th><?= e(t('vin')) ?></th><td><?= e($car['vin'] ?: '—') ?></td></tr>
                                    <tr><th><?= e(t('make')) ?></th><td><?= e($car['make']) ?></td></tr>
                                    <tr><th><?= e(t('model')) ?></th><td><?= e($car['model']) ?></td></tr>
                                    <tr><th><?= e(t('year')) ?></th><td><?= e($car['year'] ?: '—') ?></td></tr>
                                    <tr><th><?= e(t('color')) ?></th><td><?= e($car['color'] ?: '—') ?></td></tr>
                                    <tr><th><?= e(t('supplier')) ?></th><td><?= e($car['supplier'] ?: '—') ?></td></tr>
                                    <tr><th><?= e(t('purchase_date')) ?></th><td class="ltr-nums"><?= e($car['purchase_date'] ?: '—') ?></td></tr>
                                    <tr>
                                        <th><?= e(t('purchase_price')) ?></th>
                                        <td class="ltr-nums"><?= money((float)$car['purchase_price'], $car['purchase_currency']) ?>
                                            <small class="text-muted">@ <?= rtrim(rtrim(number_format((float)$car['purchase_rate'], 4), '0'), '.') ?></small>
                                        </td>
                                    </tr>
                                    <?php if ($car['notes']): ?>
                                        <tr><th><?= e(t('notes')) ?></th><td><?= nl2br(e($car['notes'])) ?></td></tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <!-- Status control -->
                        <div class="card card-info card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-stream mr-1"></i> <?= e(t('change_status')) ?></h3>
                            </div>
                            <div class="card-body">
                                <div class="input-group">
                                    <select id="statusSelect" class="form-control" <?= $canEdit ? '' : 'disabled' ?>>
                                        <?php foreach (car_statuses() as $s): ?>
                                            <option value="<?= $s ?>" <?= $s === $car['status'] ? 'selected' : '' ?>>
                                                <?= e(t('status_' . $s)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($canEdit): ?>
                                        <div class="input-group-append">
                                            <button id="btnStatus" class="btn btn-info"><i class="fas fa-check"></i></button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: cost ledger -->
                    <div class="col-md-8">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-list mr-1"></i> <?= e(t('cost_ledger')) ?></h3>
                                <div class="card-tools">
                                    <a href="print_car_costsheet.php?id=<?= $id ?>" target="_blank" class="btn btn-default btn-sm">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <?php if ($canAddCost): ?>
                                        <button class="btn btn-primary btn-sm" id="btnAddCost">
                                            <i class="fas fa-plus"></i> <?= e(t('add_cost')) ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-bordered table-striped table-hover mb-0">
                                    <thead>
                                    <tr>
                                        <th><?= e(t('cost_type')) ?></th>
                                        <th><?= e(t('description')) ?></th>
                                        <th class="text-right"><?= e(t('amount')) ?></th>
                                        <th class="text-right"><?= e(t('amount_in_base')) ?></th>
                                        <th><?= e(t('invoice_number')) ?></th>
                                        <th><?= e(t('cost_date')) ?></th>
                                        <th><?= e(t('attachment')) ?></th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($costs as $c): $isPurchase = (int)$c['cost_type_id'] === 1; ?>
                                        <tr>
                                            <td>
                                                <?= e($c['type_name']) ?>
                                                <?php if ($isPurchase): ?>
                                                    <i class="fas fa-lock text-muted ml-1" title="<?= e(t('purchase_line_note')) ?>"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($c['description'] ?: '—') ?></td>
                                            <td class="text-right ltr-nums"><?= money((float)$c['amount'], $c['currency']) ?></td>
                                            <td class="text-right ltr-nums"><?= money((float)$c['amount_base']) ?></td>
                                            <td><?= e($c['invoice_number'] ?: '—') ?></td>
                                            <td class="ltr-nums"><?= e($c['cost_date'] ?: '—') ?></td>
                                            <td>
                                                <?php if ($c['attachment']): ?>
                                                    <a href="<?= e($c['attachment']) ?>" target="_blank" class="btn btn-default btn-xs">
                                                        <i class="fas fa-paperclip"></i> <?= e(t('view')) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($canDeleteCost && !$isPurchase): ?>
                                                    <button class="btn btn-danger btn-xs btn-delete-cost" data-id="<?= (int)$c['id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                    <tr class="bg-light">
                                        <th colspan="3" class="text-right"><?= e(t('landed_cost')) ?></th>
                                        <th class="text-right ltr-nums"><?= money($landed) ?></th>
                                        <th colspan="4"></th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Sale & payments (Phase 3) -->
                        <div class="card card-secondary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-handshake mr-1"></i> <?= e(t('sale_and_payments')) ?></h3>
                                <div class="card-tools"><span class="badge badge-info"><?= e(t('phase')) ?> 3</span></div>
                            </div>
                            <div class="card-body text-center text-muted">
                                <?= e($sale ? t('sale_and_payments') : t('no_sale_yet')) ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <!-- Add cost modal (unique ID; Select2 init on shown / destroy first) -->
    <div class="modal fade" id="modalCost" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="costForm" enctype="multipart/form-data" autocomplete="off">
                    <div class="modal-header">
                        <h4 class="modal-title"><?= e(t('add_cost')) ?></h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="car_id" value="<?= $id ?>">
                        <div class="form-group">
                            <label><?= e(t('cost_type')) ?> *</label>
                            <select class="form-control select2 in-modal" name="cost_type_id" id="cost_type_id"
                                    style="width:100%" required>
                                <option value=""><?= e(t('select_cost_type')) ?></option>
                                <?php foreach ($costTypes as $ct): ?>
                                    <option value="<?= (int)$ct['id'] ?>"><?= e($ct['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= e(t('description')) ?></label>
                            <input type="text" class="form-control" name="description">
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label><?= e(t('amount')) ?> *</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="amount" id="cost_amount" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label><?= e(t('currency')) ?> *</label>
                                <select class="form-control" name="currency" id="cost_currency">
                                    <?php foreach ($currencies as $c): ?>
                                        <option value="<?= e($c['code']) ?>" <?= $c['code'] === 'IQD' ? 'selected' : '' ?>>
                                            <?= e($c['code']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label><?= e(t('exchange_rate')) ?> *</label>
                                <input type="number" step="0.000001" min="0" class="form-control"
                                       name="exchange_rate" id="cost_rate" value="1" required>
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
                                <label><?= e(t('cost_date')) ?></label>
                                <input type="date" class="form-control" name="cost_date" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?= e(t('attachment')) ?></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="attachment" id="cost_attachment"
                                       accept=".jpg,.jpeg,.png,.gif,.pdf">
                                <label class="custom-file-label" for="cost_attachment"><?= e(t('select')) ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?= e(t('cancel')) ?></button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= e(t('save')) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
$extra_js = '<script>
var CAR_ID = ' . $id . ';
var LBL_ERROR = ' . json_encode(t('something_went_wrong'), JSON_UNESCAPED_UNICODE) . ';
</script>' . <<<'JS'
<script>
$(function () {
    // Currency -> rate convenience: IQD locks rate to 1
    function syncRate() {
        if ($('#cost_currency').val() === 'IQD') { $('#cost_rate').val(1); }
    }
    function updateCostBase() {
        var amt = parseFloat($('#cost_amount').val()) || 0;
        var rate = parseFloat($('#cost_rate').val()) || 0;
        $('#costBasePreview').text(Math.round(amt * rate).toLocaleString('en-US') + ' IQD');
    }
    $('#cost_currency').on('change', function () { syncRate(); updateCostBase(); });
    $('#cost_amount, #cost_rate').on('input', updateCostBase);

    // Show chosen filename on the custom file input
    $('#cost_attachment').on('change', function () {
        var name = this.files.length ? this.files[0].name : '';
        $(this).next('.custom-file-label').text(name || $(this).next('.custom-file-label').text());
    });

    // Select2 inside the modal: init on shown, destroy first (known gotcha)
    $('#modalCost').on('shown.bs.modal', function () {
        var $sel = $('#cost_type_id');
        if ($sel.hasClass('select2-hidden-accessible')) { $sel.select2('destroy'); }
        $sel.select2({ theme: 'bootstrap4', dropdownParent: $('#modalCost') });
    });

    $('#btnAddCost').on('click', function () {
        $('#costForm')[0].reset();
        $('#cost_rate').val(1);
        updateCostBase();
        $('#modalCost').modal('show');
    });

    $('#costForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        $.ajax({
            url: 'ajax/costs/save.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#modalCost').modal('hide');
                    swalSuccess(res.message);
                    setTimeout(function () { location.reload(); }, 1000);
                } else {
                    swalError(res.message);
                }
            },
            error: function () { swalError(LBL_ERROR); }
        });
    });

    $('.btn-delete-cost').on('click', function () {
        var id = $(this).data('id');
        swalConfirmDelete(function () {
            $.post('ajax/costs/delete.php', { id: id }, function (res) {
                if (res.success) { swalSuccess(res.message); setTimeout(function () { location.reload(); }, 1000); }
                else { swalError(res.message); }
            }, 'json').fail(function () { swalError(LBL_ERROR); });
        });
    });

    // Status change
    $('#btnStatus').on('click', function () {
        $.post('ajax/cars/status.php', { id: CAR_ID, status: $('#statusSelect').val() }, function (res) {
            if (res.success) { swalSuccess(res.message); setTimeout(function () { location.reload(); }, 1000); }
            else { swalError(res.message); }
        }, 'json').fail(function () { swalError(LBL_ERROR); });
    });
});
</script>
JS;

require_once __DIR__ . '/footer.php';
