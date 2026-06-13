<?php
/** Cars — server-side DataTable with status/make filters. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'cars.php');

$page_title = t('cars');
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
                        <h3 class="card-title"><i class="fas fa-car mr-1"></i> <?= e(t('cars')) ?></h3>
                        <div class="card-tools">
                            <?php if (can('add', 'cars.php')): ?>
                                <a href="car_add.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> <?= e(t('add_car')) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label><?= e(t('filter_status')) ?></label>
                                <select id="filterStatus" class="form-control select2" style="width:100%">
                                    <option value=""><?= e(t('all')) ?></option>
                                    <?php foreach (car_statuses() as $s): ?>
                                        <option value="<?= $s ?>"><?= e(t('status_' . $s)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label><?= e(t('filter_make')) ?></label>
                                <input type="text" id="filterMake" class="form-control" placeholder="<?= e(t('make')) ?>">
                            </div>
                        </div>

                        <table id="carsTable" class="table table-bordered table-striped table-hover" style="width:100%">
                            <thead>
                            <tr>
                                <th><?= e(t('lot_number')) ?></th>
                                <th><?= e(t('vin')) ?></th>
                                <th><?= e(t('make')) ?></th>
                                <th><?= e(t('model')) ?></th>
                                <th><?= e(t('year')) ?></th>
                                <th><?= e(t('status')) ?></th>
                                <th><?= e(t('landed_cost')) ?></th>
                                <th><?= e(t('actions')) ?></th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

<?php
$extra_js = '<script>
var LBL_ERROR = ' . json_encode(t('something_went_wrong'), JSON_UNESCAPED_UNICODE) . ';
</script>' . <<<'JS'
<script>
$(function () {
    var table = $('#carsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: 'ajax/cars/list.php',
            type: 'POST',
            data: function (d) {
                d.f_status = $('#filterStatus').val();
                d.f_make = $('#filterMake').val();
            }
        },
        columns: [
            { data: 'lot_number' },
            { data: 'vin' },
            { data: 'make' },
            { data: 'model' },
            { data: 'year' },
            { data: 'status_html' },
            { data: 'landed_cost_html', className: 'money text-right' },
            { data: 'actions_html', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    $('#filterStatus').on('change', function () { table.ajax.reload(); });
    $('#filterMake').on('keyup', function () { table.ajax.reload(); });

    // Delete (event-delegated; rows are redrawn by DataTables)
    $('#carsTable tbody').on('click', '.btn-delete-car', function () {
        var id = $(this).data('id');
        swalConfirmDelete(function () {
            $.post('ajax/cars/delete.php', { id: id }, function (res) {
                if (res.success) { swalSuccess(res.message); table.ajax.reload(null, false); }
                else { swalError(res.message); }
            }, 'json').fail(function () { swalError(LBL_ERROR); });
        });
    });
});
</script>
JS;

require_once __DIR__ . '/footer.php';
