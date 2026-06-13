<?php
/**
 * BLANK STARTER PAGE — copy this file to create every new page.
 * Fill in only the content area; never change the surrounding structure.
 */
$page_title = 'Blank Page';            // use t('key') on real pages
require_once __DIR__ . '/header.php';
?>

    <!-- ============ Content ============ -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= e($page_title) ?></h1>
                    </div>
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
                        <h3 class="card-title">Card title</h3>
                        <div class="card-tools"><!-- buttons / filters go here --></div>
                    </div>
                    <div class="card-body">
                        Page content goes here.
                    </div>
                </div>

            </div>
        </section>
    </div>

<?php require_once __DIR__ . '/footer.php'; ?>
