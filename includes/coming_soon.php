<?php
/**
 * Reusable "module under construction" content area.
 * A stub page sets $page_title (and optionally $phase_label) before
 * including this, between header.php and footer.php.
 * Each stub is replaced by the real module in its build phase.
 */
if (!isset($page_title)) { $page_title = ''; }
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
                        <h3 class="card-title"><?= e($page_title) ?></h3>
                        <?php if (!empty($phase_label)): ?>
                            <div class="card-tools"><span class="badge badge-info"><?= e($phase_label) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-center text-muted py-5">
                        <i class="fas fa-tools fa-3x mb-3"></i>
                        <h4><?= e(t('under_construction')) ?></h4>
                        <p class="mb-0"><?= e(t('coming_soon_text')) ?></p>
                    </div>
                </div>
            </div>
        </section>
    </div>
