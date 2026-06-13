<?php
/** Placeholder page — replaced by the full module in Phase 2. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'cars.php');

$page_title  = t('cars');
$phase_label = t('phase') . ' 2';
require_once __DIR__ . '/header.php';
require __DIR__ . '/includes/coming_soon.php';
require_once __DIR__ . '/footer.php';
