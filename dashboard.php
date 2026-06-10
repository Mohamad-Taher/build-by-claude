<?php
/**
 * Dashboard — KPI tiles. Real profit/receivables math arrives in Phase 5;
 * for now the boxes show live counts from the tables that already exist.
 */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'dashboard.php');

$page_title = t('dashboard');
require_once __DIR__ . '/header.php';

// Quick live counts (cheap queries; full KPI logic comes in Phase 5)
$totalCars     = (int)db()->query("SELECT COUNT(*) c FROM tbl_cars WHERE deleted_at IS NULL")->fetch()['c'];
$carsSold      = (int)db()->query("SELECT COUNT(*) c FROM tbl_cars WHERE deleted_at IS NULL AND status='sold'")->fetch()['c'];
$totalInvested = (float)db()->query("SELECT COALESCE(SUM(amount_base),0) s FROM tbl_car_costs WHERE deleted_at IS NULL")->fetch()['s'];
$receivables   = 0.0; // computed properly in Phase 3/5 (sales − payments)
?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1 class="m-0"><?= e($page_title) ?></h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active"><?= e(t('home')) ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">

                <!-- KPI small-boxes -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3 class="money"><?= money($totalInvested) ?></h3>
                                <p><?= e(t('total_invested')) ?></p>
                            </div>
                            <div class="icon"><i class="fas fa-sack-dollar fa-money-bill"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= $totalCars ?></h3>
                                <p><?= e(t('total_cars')) ?></p>
                            </div>
                            <div class="icon"><i class="fas fa-car"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= $carsSold ?></h3>
                                <p><?= e(t('cars_sold')) ?></p>
                            </div>
                            <div class="icon"><i class="fas fa-handshake"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3 class="money"><?= money($receivables) ?></h3>
                                <p><?= e(t('receivables')) ?></p>
                            </div>
                            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Cars by status -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-stream mr-1"></i> <?= e(t('cars_by_status')) ?></h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $statuses = ['purchased','paid','shipped','in_transit','at_port','cleared','in_showroom','sold'];
                        $badge    = ['purchased'=>'secondary','paid'=>'info','shipped'=>'primary','in_transit'=>'warning',
                                     'at_port'=>'warning','cleared'=>'info','in_showroom'=>'success','sold'=>'success'];
                        $counts = [];
                        foreach (db()->query("SELECT status, COUNT(*) c FROM tbl_cars WHERE deleted_at IS NULL GROUP BY status") as $r) {
                            $counts[$r['status']] = (int)$r['c'];
                        }
                        foreach ($statuses as $s): ?>
                            <span class="badge badge-<?= $badge[$s] ?> p-2 mr-2 mb-2" style="font-size:0.95rem">
                                <?= e(t('status_' . $s)) ?>
                                <span class="badge badge-light ml-1"><?= $counts[$s] ?? 0 ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </section>
    </div>

<?php require_once __DIR__ . '/footer.php'; ?>
