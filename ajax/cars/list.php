<?php
/**
 * AJAX: server-side DataTables source for the cars list.
 * Supports global search, status/make filters, ordering, and computes
 * each car's landed cost (SUM of cost_base) inline.
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_permission('view', 'cars.php');

$draw    = (int)($_POST['draw'] ?? 1);
$start   = max(0, (int)($_POST['start'] ?? 0));
$length  = (int)($_POST['length'] ?? 10);
$length  = ($length > 0 && $length <= 100) ? $length : 10;
$search  = trim($_POST['search']['value'] ?? '');
$fStatus = trim($_POST['f_status'] ?? '');
$fMake   = trim($_POST['f_make'] ?? '');

// Whitelisted sortable columns by DataTables column index
$orderCols = ['lot_number', 'vin', 'make', 'model', 'year', 'status', 'landed_cost'];
$orderIdx  = (int)($_POST['order'][0]['column'] ?? 0);
$orderCol  = $orderCols[$orderIdx] ?? 'id';
$orderDir  = strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// --- WHERE clause (parameterized) ---
$where  = ['c.deleted_at IS NULL'];
$params = [];

if ($search !== '') {
    $where[] = '(c.lot_number LIKE ? OR c.vin LIKE ? OR c.make LIKE ? OR c.model LIKE ? OR c.year LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($fStatus !== '' && in_array($fStatus, car_statuses(), true)) {
    $where[] = 'c.status = ?';
    $params[] = $fStatus;
}
if ($fMake !== '') {
    $where[] = 'c.make LIKE ?';
    $params[] = '%' . $fMake . '%';
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

// --- Counts ---
$total = (int)db()->query('SELECT COUNT(*) c FROM tbl_cars WHERE deleted_at IS NULL')->fetch()['c'];

$cntStmt = db()->prepare("SELECT COUNT(*) c FROM tbl_cars c $whereSql");
$cntStmt->execute($params);
$filtered = (int)$cntStmt->fetch()['c'];

// --- Page of rows ---
$sql = "SELECT c.id, c.lot_number, c.vin, c.make, c.model, c.year, c.status,
               (SELECT COALESCE(SUM(cc.amount_base), 0)
                  FROM tbl_car_costs cc
                 WHERE cc.car_id = c.id AND cc.deleted_at IS NULL) AS landed_cost
          FROM tbl_cars c
          $whereSql
      ORDER BY $orderCol $orderDir
         LIMIT $length OFFSET $start";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$canEdit   = can('edit', 'cars.php');
$canDelete = can('delete', 'cars.php');

$data = [];
foreach ($rows as $r) {
    $id = (int)$r['id'];

    $actions = '<a href="car_view.php?id=' . $id . '" class="btn btn-info btn-sm" title="' . e(t('view')) . '">'
             . '<i class="fas fa-eye"></i></a> ';
    if ($canEdit) {
        $actions .= '<a href="car_add.php?id=' . $id . '" class="btn btn-warning btn-sm" title="' . e(t('edit')) . '">'
                  . '<i class="fas fa-edit"></i></a> ';
    }
    if ($canDelete) {
        $actions .= '<button class="btn btn-danger btn-sm btn-delete-car" data-id="' . $id . '" title="' . e(t('delete')) . '">'
                  . '<i class="fas fa-trash"></i></button>';
    }

    $data[] = [
        'lot_number'       => e($r['lot_number'] ?? '—'),
        'vin'              => e($r['vin'] ?? '—'),
        'make'             => e($r['make']),
        'model'            => e($r['model']),
        'year'             => $r['year'] ? (int)$r['year'] : '—',
        'status_html'      => status_badge($r['status']),
        'landed_cost_html' => number_format((float)$r['landed_cost'], 0) . ' IQD',
        'actions_html'     => $actions,
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $total,
    'recordsFiltered' => $filtered,
    'data'            => $data,
], JSON_UNESCAPED_UNICODE);
