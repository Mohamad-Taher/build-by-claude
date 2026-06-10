<?php
/**
 * Session guard + permission helpers.
 * Include at the top of every page (header.php does this automatically)
 * and at the top of every AJAX endpoint.
 */
require_once __DIR__ . '/functions.php';

// Not logged in -> redirect pages to login, return JSON 401 for AJAX
if (empty($_SESSION['username']) || empty($_SESSION['user_id'])) {
    $isAjax = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/ajax/');
    if ($isAjax) {
        http_response_code(401);
        json_response(false, 'Not authenticated');
    }
    header('Location: login.php');
    exit;
}

/**
 * Permission flags for a page URL (e.g. 'cars.php') for the logged-in role.
 * Returns ['can_view'=>0/1, 'can_add'=>..., 'can_edit'=>..., 'can_delete'=>...].
 * Unregistered pages (detail/sub-pages not in tbl_pages) return all zeros —
 * check against their parent module instead, e.g. can('edit', 'cars.php').
 */
function page_permissions(string $pageUrl): array
{
    static $cache = [];
    if (!isset($cache[$pageUrl])) {
        $stmt = db()->prepare(
            'SELECT rp.can_view, rp.can_add, rp.can_edit, rp.can_delete
               FROM tbl_role_pages rp
               JOIN tbl_pages p ON p.id = rp.page_id
              WHERE rp.role_id = ? AND p.page_url = ?'
        );
        $stmt->execute([$_SESSION['role_id'], $pageUrl]);
        $cache[$pageUrl] = $stmt->fetch()
            ?: ['can_view' => 0, 'can_add' => 0, 'can_edit' => 0, 'can_delete' => 0];
    }
    return $cache[$pageUrl];
}

/** Check one action ('view' | 'add' | 'edit' | 'delete') on a module page. */
function can(string $action, ?string $pageUrl = null): bool
{
    $pageUrl = $pageUrl ?? basename($_SERVER['SCRIPT_NAME']);
    return (int)(page_permissions($pageUrl)['can_' . $action] ?? 0) === 1;
}

/**
 * Stop the request unless the role has the given action on the module.
 * Pages call require_permission('view'); AJAX endpoints pass the module
 * explicitly, e.g. require_permission('add', 'cars.php').
 */
function require_permission(string $action, ?string $pageUrl = null): void
{
    if (!can($action, $pageUrl)) {
        $isAjax = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/ajax/');
        if ($isAjax) {
            http_response_code(403);
            json_response(false, t('no_permission'));
        }
        http_response_code(403);
        die('<h3 style="font-family:sans-serif;text-align:center;margin-top:3rem">403 — '
            . e(t('no_permission')) . '</h3>');
    }
}
