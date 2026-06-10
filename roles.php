<?php
/** Roles management — list, add/edit via modal, soft delete. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'roles.php');

$page_title = t('roles');
require_once __DIR__ . '/header.php';

$roles = db()->query(
    "SELECT r.*,
            (SELECT COUNT(*) FROM tbl_users u WHERE u.role_id = r.id AND u.deleted_at IS NULL) AS user_count
       FROM tbl_role r
      WHERE r.deleted_at IS NULL
      ORDER BY r.id"
)->fetchAll();
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
                        <h3 class="card-title"><i class="fas fa-user-tag mr-1"></i> <?= e(t('roles')) ?></h3>
                        <div class="card-tools">
                            <?php if (can('add', 'roles.php')): ?>
                                <button class="btn btn-primary btn-sm" id="btnAddRole">
                                    <i class="fas fa-plus"></i> <?= e(t('add_role')) ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="rolesTable" class="table table-bordered table-striped table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th><?= e(t('role_name')) ?></th>
                                <th><?= e(t('users')) ?></th>
                                <th><?= e(t('created_at')) ?></th>
                                <th><?= e(t('actions')) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($roles as $r): ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td><?= e($r['role_name']) ?></td>
                                    <td><span class="badge badge-info"><?= (int)$r['user_count'] ?></span></td>
                                    <td><?= e($r['created_at']) ?></td>
                                    <td>
                                        <?php if (can('edit', 'roles.php')): ?>
                                            <button class="btn btn-info btn-sm btn-edit-role"
                                                    data-id="<?= (int)$r['id'] ?>"
                                                    data-name="<?= e($r['role_name']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (can('delete', 'roles.php') && (int)$r['user_count'] === 0): ?>
                                            <button class="btn btn-danger btn-sm btn-delete-role" data-id="<?= (int)$r['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <!-- Add/Edit role modal -->
    <div class="modal fade" id="modalRole" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form id="roleForm" autocomplete="off">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalRoleTitle"><?= e(t('add_role')) ?></h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="role_id" value="0">
                        <div class="form-group">
                            <label><?= e(t('role_name')) ?> *</label>
                            <input type="text" class="form-control" name="role_name" id="role_name" required>
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
var LBL_ADD_ROLE = ' . json_encode(t('add_role'), JSON_UNESCAPED_UNICODE) . ';
var LBL_EDIT_ROLE = ' . json_encode(t('edit_role'), JSON_UNESCAPED_UNICODE) . ';
var LBL_ERROR = ' . json_encode(t('something_went_wrong'), JSON_UNESCAPED_UNICODE) . ';
</script>' . <<<'JS'
<script>
$(function () {
    $('#rolesTable').DataTable({ responsive: true });

    $('#btnAddRole').on('click', function () {
        $('#roleForm')[0].reset();
        $('#role_id').val(0);
        $('#modalRoleTitle').text(LBL_ADD_ROLE);
        $('#modalRole').modal('show');
    });

    $('.btn-edit-role').on('click', function () {
        $('#role_id').val($(this).data('id'));
        $('#role_name').val($(this).data('name'));
        $('#modalRoleTitle').text(LBL_EDIT_ROLE);
        $('#modalRole').modal('show');
    });

    $('#roleForm').on('submit', function (e) {
        e.preventDefault();
        $.post('ajax/roles/save.php', $(this).serialize(), function (res) {
            if (res.success) {
                $('#modalRole').modal('hide');
                swalSuccess(res.message);
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                swalError(res.message);
            }
        }, 'json').fail(function () { swalError(LBL_ERROR); });
    });

    $('.btn-delete-role').on('click', function () {
        var id = $(this).data('id');
        swalConfirmDelete(function () {
            $.post('ajax/roles/delete.php', { id: id }, function (res) {
                if (res.success) {
                    swalSuccess(res.message);
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    swalError(res.message);
                }
            }, 'json');
        });
    });
});
</script>
JS;

require_once __DIR__ . '/footer.php';
