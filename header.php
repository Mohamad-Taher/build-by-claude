<?php
/**
 * Layout header: <head> assets, top navbar, dynamic sidebar.
 * Every content page includes this first (see blank.php).
 *
 * Optional variables a page may set BEFORE including header.php:
 *   $page_title  string  page title (defaults to system name)
 *   $extra_css   string  extra <link> tags echoed in <head>
 */
require_once __DIR__ . '/includes/auth_check.php';

$lang      = current_lang();
$dir       = is_rtl() ? 'rtl' : 'ltr';
$theme     = get_setting('theme', 'peshang');
$sysName   = get_setting('system_name', 'Car Import System');
$logoPath  = get_setting('logo_path', 'dist/img/AdminLTELogo.png');
$pageTitle = $page_title ?? $sysName;

// Body classes per theme: peshang gets its own class, dark uses AdminLTE dark-mode
$bodyClass = 'hold-transition sidebar-mini layout-fixed';
if ($theme === 'peshang') { $bodyClass .= ' theme-peshang'; }
if ($theme === 'dark')    { $bodyClass .= ' dark-mode'; }

// ---- Build the sidebar menu from tbl_pages + role permissions ---------
$stmt = db()->prepare(
    'SELECT p.*
       FROM tbl_pages p
       JOIN tbl_role_pages rp ON rp.page_id = p.id
      WHERE rp.role_id = ? AND rp.can_view = 1
      ORDER BY p.sort_order, p.id'
);
$stmt->execute([$_SESSION['role_id']]);
$allPages = $stmt->fetchAll();

$topPages = [];
$children = [];
foreach ($allPages as $p) {
    if ($p['parent_id'] === null) {
        $topPages[] = $p;
    } else {
        $children[$p['parent_id']][] = $p;
    }
}

$currentScript = basename($_SERVER['SCRIPT_NAME']);
// Pages may set $active_page to highlight a parent module (e.g. car_view.php -> cars.php)
$activeUrl = $active_page ?? $currentScript;

$langNames = ['en' => 'English', 'ar' => 'عربي', 'ku' => 'کوردی'];
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= $dir ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($sysName) ?></title>

    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
<?php if (is_rtl()): ?>
    <link rel="stylesheet" href="dist/css/adminlte-rtl.css">
<?php endif; ?>
<?php if ($theme === 'peshang'): ?>
    <link rel="stylesheet" href="dist/css/peshang-theme.css">
<?php endif; ?>
    <?= $extra_css ?? '' ?>
</head>
<body class="<?= $bodyClass ?>">
<div class="wrapper">

    <!-- ============ Top navbar ============ -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="dashboard.php" class="nav-link"><?= e(t('home')) ?></a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <!-- Language switcher -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fas fa-globe"></i> <?= e($langNames[$lang]) ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <?php foreach ($langNames as $code => $label): ?>
                        <a href="#" class="dropdown-item lang-switch <?= $code === $lang ? 'active' : '' ?>"
                           data-lang="<?= $code ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </li>
            <!-- User menu -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="far fa-user"></i> <?= e($_SESSION['full_name'] ?? $_SESSION['username']) ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <span class="dropdown-item-text text-muted">
                        <?= e(t('role')) ?>: <?= e($_SESSION['role_name'] ?? '') ?>
                    </span>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> <?= e(t('logout')) ?>
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- ============ Sidebar ============ -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="dashboard.php" class="brand-link">
            <img src="<?= e($logoPath) ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity:.9">
            <span class="brand-text font-weight-light"><?= e($sysName) ?></span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="">
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?= e($_SESSION['full_name'] ?? $_SESSION['username']) ?></a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <?php foreach ($topPages as $p): ?>
                        <?php
                        $kids = $children[$p['id']] ?? [];
                        if ($p['page_url'] === null && empty($kids)) { continue; } // empty group
                        if (!empty($kids)) :
                            // treeview group — open/active when a child matches the current page
                            $groupActive = false;
                            foreach ($kids as $c) {
                                if ($c['page_url'] === $activeUrl) { $groupActive = true; break; }
                            }
                        ?>
                            <li class="nav-item <?= $groupActive ? 'menu-open' : '' ?>">
                                <a href="#" class="nav-link <?= $groupActive ? 'active' : '' ?>">
                                    <i class="nav-icon <?= e($p['icon']) ?>"></i>
                                    <p><?= e(page_name($p)) ?><i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <?php foreach ($kids as $c): ?>
                                        <li class="nav-item">
                                            <a href="<?= e($c['page_url']) ?>"
                                               class="nav-link <?= $c['page_url'] === $activeUrl ? 'active' : '' ?>">
                                                <i class="nav-icon <?= e($c['icon']) ?>"></i>
                                                <p><?= e(page_name($c)) ?></p>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a href="<?= e($p['page_url']) ?>"
                                   class="nav-link <?= $p['page_url'] === $activeUrl ? 'active' : '' ?>">
                                    <i class="nav-icon <?= e($p['icon']) ?>"></i>
                                    <p><?= e(page_name($p)) ?></p>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </aside>
