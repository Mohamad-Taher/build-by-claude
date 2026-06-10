<?php
/**
 * Login page — standalone (does not use header.php).
 * Uses the system default language/theme; submits via AJAX.
 */
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$lang    = current_lang();
$dir     = is_rtl() ? 'rtl' : 'ltr';
$theme   = get_setting('theme', 'peshang');
$sysName = get_setting('system_name', 'Car Import System');

$bodyClass = 'hold-transition login-page';
if ($theme === 'peshang') { $bodyClass .= ' theme-peshang'; }
if ($theme === 'dark')    { $bodyClass .= ' dark-mode'; }
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= $dir ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('login')) ?> | <?= e($sysName) ?></title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
<?php if (is_rtl()): ?>
    <link rel="stylesheet" href="dist/css/adminlte-rtl.css">
<?php endif; ?>
<?php if ($theme === 'peshang'): ?>
    <link rel="stylesheet" href="dist/css/peshang-theme.css">
<?php endif; ?>
</head>
<body class="<?= $bodyClass ?>">
<div class="login-box">
    <div class="login-logo">
        <a href="#"><b><?= e($sysName) ?></b></a>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg"><?= e(t('sign_in_message')) ?></p>

            <form id="loginForm" autocomplete="off">
                <div class="input-group mb-3">
                    <input type="text" name="username" id="username" class="form-control"
                           placeholder="<?= e(t('username')) ?>" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-user"></span></div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="<?= e(t('password')) ?>" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> <?= e(t('sign_in')) ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<script>
$('#loginForm').on('submit', function (e) {
    e.preventDefault();
    $.post('ajax/auth/login.php', $(this).serialize(), function (res) {
        if (res.success) {
            window.location = 'dashboard.php';
        } else {
            Swal.fire({ icon: 'error', title: res.message });
        }
    }, 'json').fail(function () {
        Swal.fire({ icon: 'error', title: '<?= e(t('something_went_wrong')) ?>' });
    });
});
</script>
</body>
</html>
