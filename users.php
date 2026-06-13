<?php
/** Users management — list, add/edit via modal, soft delete. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'users.php');

$page_title = t('users');
require_once __DIR__ . '/header.php';

$users = db()->query(
    "SELECT u.*, r.role_name
       FROM tbl_users u
       JOIN tbl_role r ON r.id = u.role_id
      WHERE u.deleted_at IS NULL
      ORDER BY u.id"
)->fetchAll();

$roles = db()->query(
    "SELECT id, role_name FROM tbl_role WHERE deleted_at IS NULL ORDER BY role_name"
)->fetchAll();

$langNames = ['en' => 'English', 'ar' => 'عربي', 'ku' => 'کوردی'];
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
                        <h3 class="card-title"><i class="fas fa-user mr-1"></i> <?= e(t('users')) ?></h3>
                        <div class="card-tools">
                            <?php if (can('add', 'users.php')): ?>
                                <button class="btn btn-primary btn-sm" id="btnAddUser">
                                    <i class="fas fa-plus"></i> <?= e(t('add_user')) ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="usersTable" class="table table-bordered table-striped table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th><?= e(t('username')) ?></th>
                                <th><?= e(t('full_name')) ?></th>
                                <th><?= e(t('role')) ?></th>
                                <th><?= e(t('language')) ?></th>
                                <th><?= e(t('status')) ?></th>
                                <th><?= e(t('actions')) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= (int)$u['id'] ?></td>
                                    <td><?= e($u['username']) ?></td>
                                    <td><?= e($u['full_name']) ?></td>
                                    <td><?= e($u['role_name']) ?></td>
                                    <td><?= e($langNames[$u['language']] ?? $u['language']) ?></td>
                                    <td>
                                        <?php if ((int)$u['status'] === 1): ?>
                                            <span class="badge badge-success"><?= e(t('active')) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><?= e(t('inactive')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (can('edit', 'users.php')): ?>
                                            <button class="btn btn-info btn-sm btn-edit-user"
                                                    data-user='<?= e(json_encode([
                                                        'id' => (int)$u['id'],
                                                        'username' => $u['username'],
                                                        'full_name' => $u['full_name'],
                                                        'role_id' => (int)$u['role_id'],
                                                        'language' => $u['language'],
                                                        'status' => (int)$u['status'],
                                                    ], JSON_UNESCAPED_UNICODE)) ?>'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (can('delete', 'users.php') && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                            <button class="btn btn-danger btn-sm btn-delete-user" data-id="<?= (int)$u['id'] ?>">
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

    <!-- Add/Edit user modal (single modal, unique ID) -->
    <div class="modal fade" id="modalUser" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="userForm" autocomplete="off">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalUserTitle"><?= e(t('add_user')) ?></h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="user_id" value="0">
                        <div class="form-group">
                            <label><?= e(t('username')) ?> *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" class="form-control" name="username" id="user_username" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?= e(t('full_name')) ?> *</label>
                            <input type="text" class="form-control" name="full_name" id="user_full_name" required>
                        </div>
                        <div class="form-group">
                            <label><?= e(t('password')) ?></label>
                            <input type="password" class="form-control" name="password" id="user_password">
                            <small class="text-muted" id="passwordHint"><?= e(t('leave_blank_password')) ?></small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(t('role')) ?> *</label>
                                    <select class="form-control select2 in-modal" name="role_id" id="user_role_id"
                                            data-dropdown-parent="#modalUser" style="width:100%">
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= (int)$r['id'] ?>"><?= e($r['role_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(t('language')) ?></label>
                                    <select class="form-control" name="language" id="user_language">
                                        <?php foreach ($langNames as $code => $label): ?>
                                            <option value="<?= $code ?>"><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?= e(t('status')) ?></label>
                            <select class="form-control" name="status" id="user_status">
                                <option value="1"><?= e(t('active')) ?></option>
                                <option value="0"><?= e(t('inactive')) ?></option>
                            </select>
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
$extra_js = <<<'JS'
<script>
$(function () {
    $('#usersTable').DataTable({ responsive: true });

    // Select2 inside the modal: init on shown, destroy first (known gotcha)
    $('#modalUser').on('shown.bs.modal', function () {
        var $sel = $('#user_role_id');
        if ($sel.hasClass('select2-hidden-accessible')) { $sel.select2('destroy'); }
        $sel.select2({ theme: 'bootstrap4', dropdownParent: $('#modalUser') });
    });

    $('#btnAddUser').on('click', function () {
        $('#userForm')[0].reset();
        $('#user_id').val(0);
        $('#user_password').prop('required', true);
        $('#passwordHint').hide();
        $('#modalUserTitle').text(LBL_ADD_USER);
        $('#modalUser').modal('show');
    });

    $('.btn-edit-user').on('click', function () {
        var u = $(this).data('user');
        $('#userForm')[0].reset();
        $('#user_id').val(u.id);
        $('#user_username').val(u.username);
        $('#user_full_name').val(u.full_name);
        $('#user_role_id').val(u.role_id);
        $('#user_language').val(u.language);
        $('#user_status').val(u.status);
        $('#user_password').prop('required', false);
        $('#passwordHint').show();
        $('#modalUserTitle').text(LBL_EDIT_USER);
        $('#modalUser').modal('show');
    });

    $('#userForm').on('submit', function (e) {
        e.preventDefault();
        $.post('ajax/users/save.php', $(this).serialize(), function (res) {
            if (res.success) {
                $('#modalUser').modal('hide');
                swalSuccess(res.message);
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                swalError(res.message);
            }
        }, 'json').fail(function () { swalError(LBL_ERROR); });
    });

    $('.btn-delete-user').on('click', function () {
        var id = $(this).data('id');
        swalConfirmDelete(function () {
            $.post('ajax/users/delete.php', { id: id }, function (res) {
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

// Inject translated labels used by the script above
$extra_js = '<script>
var LBL_ADD_USER = ' . json_encode(t('add_user'), JSON_UNESCAPED_UNICODE) . ';
var LBL_EDIT_USER = ' . json_encode(t('edit_user'), JSON_UNESCAPED_UNICODE) . ';
var LBL_ERROR = ' . json_encode(t('something_went_wrong'), JSON_UNESCAPED_UNICODE) . ';
</script>' . $extra_js;

require_once __DIR__ . '/footer.php';
