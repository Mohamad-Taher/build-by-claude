<?php
/** AJAX: save the full permission matrix for one role (transactional). */
require_once __DIR__ . '/../../includes/auth_check.php';
require_permission('edit', 'role_permissions.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

$roleId = (int)($_POST['role_id'] ?? 0);
$perm   = $_POST['perm'] ?? [];   // [page_id => [can_view => 1, ...]]

if ($roleId <= 0) {
    json_response(false, t('something_went_wrong'));
}

// Validate the role exists
$stmt = db()->prepare('SELECT id FROM tbl_role WHERE id = ? AND deleted_at IS NULL');
$stmt->execute([$roleId]);
if (!$stmt->fetch()) {
    json_response(false, t('something_went_wrong'));
}

$pageIds = db()->query('SELECT id FROM tbl_pages')->fetchAll(PDO::FETCH_COLUMN);

try {
    db()->beginTransaction();

    // Rewrite the role's matrix: delete then re-insert checked rows
    $del = db()->prepare('DELETE FROM tbl_role_pages WHERE role_id = ?');
    $del->execute([$roleId]);

    $ins = db()->prepare(
        'INSERT INTO tbl_role_pages (role_id, page_id, can_view, can_add, can_edit, can_delete)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($pageIds as $pid) {
        $flags = $perm[$pid] ?? [];
        $view   = isset($flags['can_view'])   ? 1 : 0;
        $add    = isset($flags['can_add'])    ? 1 : 0;
        $edit   = isset($flags['can_edit'])   ? 1 : 0;
        $delete = isset($flags['can_delete']) ? 1 : 0;
        if ($view || $add || $edit || $delete) {
            $ins->execute([$roleId, (int)$pid, $view, $add, $edit, $delete]);
        }
    }

    db()->commit();
    audit_log('update', 'tbl_role_pages', $roleId, 'Updated permission matrix for role ' . $roleId);
    json_response(true, t('saved_successfully'));
} catch (Throwable $ex) {
    db()->rollBack();
    json_response(false, t('something_went_wrong'));
}
