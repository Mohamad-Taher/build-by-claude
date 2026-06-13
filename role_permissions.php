<?php
/** Role permissions — checkbox matrix of pages × view/add/edit/delete per role. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'role_permissions.php');

$page_title = t('permissions');
require_once __DIR__ . '/header.php';

$roles = db()->query(
    "SELECT id, role_name FROM tbl_role WHERE deleted_at IS NULL ORDER BY role_name"
)->fetchAll();

$pages = db()->query(
    "SELECT * FROM tbl_pages ORDER BY COALESCE(parent_id, id), sort_order, id"
)->fetchAll();

// Selected role (?role_id=N) — default to the first role
$selectedRoleId = (int)($_GET['role_id'] ?? ($roles[0]['id'] ?? 0));

$perms = [];
if ($selectedRoleId > 0) {
    $stmt = db()->prepare('SELECT * FROM tbl_role_pages WHERE role_id = ?');
    $stmt->execute([$selectedRoleId]);
    foreach ($stmt->fetchAll() as $rp) {
        $perms[$rp['page_id']] = $rp;
    }
}
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
                        <h3 class="card-title"><i class="fas fa-key mr-1"></i> <?= e(t('permissions')) ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?= e(t('select_role')) ?></label>
                                    <select class="form-control select2" id="roleSelect" style="width:100%">
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= (int)$r['id'] ?>"
                                                <?= (int)$r['id'] === $selectedRoleId ? 'selected' : '' ?>>
                                                <?= e($r['role_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <form id="permissionsForm">
                            <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                <tr>
                                    <th><?= e(t('page')) ?></th>
                                    <th class="text-center" style="width:90px"><?= e(t('can_view')) ?></th>
                                    <th class="text-center" style="width:90px"><?= e(t('can_add')) ?></th>
                                    <th class="text-center" style="width:90px"><?= e(t('can_edit')) ?></th>
                                    <th class="text-center" style="width:90px"><?= e(t('can_delete')) ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pages as $p):
                                    $rp = $perms[$p['id']] ?? null; ?>
                                    <tr>
                                        <td>
                                            <?php if ($p['parent_id'] !== null): ?>
                                                <span class="text-muted mr-2">&mdash;</span>
                                            <?php endif; ?>
                                            <i class="<?= e($p['icon']) ?> mr-1"></i> <?= e(page_name($p)) ?>
                                        </td>
                                        <?php foreach (['can_view', 'can_add', 'can_edit', 'can_delete'] as $flag): ?>
                                            <td class="text-center">
                                                <div class="custom-control custom-checkbox d-inline-block">
                                                    <input type="checkbox" class="custom-control-input"
                                                           id="p<?= (int)$p['id'] ?>_<?= $flag ?>"
                                                           name="perm[<?= (int)$p['id'] ?>][<?= $flag ?>]" value="1"
                                                        <?= $rp && (int)$rp[$flag] === 1 ? 'checked' : '' ?>>
                                                    <label class="custom-control-label"
                                                           for="p<?= (int)$p['id'] ?>_<?= $flag ?>"></label>
                                                </div>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if (can('edit', 'role_permissions.php')): ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?= e(t('save_permissions')) ?>
                                </button>
                            <?php endif; ?>
                        </form>
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
    // Reload the page with the chosen role
    $('#roleSelect').on('change', function () {
        window.location = 'role_permissions.php?role_id=' + $(this).val();
    });

    $('#permissionsForm').on('submit', function (e) {
        e.preventDefault();
        $.post('ajax/permissions/save.php', $(this).serialize(), function (res) {
            if (res.success) { swalSuccess(res.message); }
            else { swalError(res.message); }
        }, 'json').fail(function () { swalError(LBL_ERROR); });
    });
});
</script>
JS;

require_once __DIR__ . '/footer.php';
