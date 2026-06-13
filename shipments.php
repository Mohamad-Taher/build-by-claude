<?php
/** Placeholder page — replaced by the full module in Phase 4. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'shipments.php');

$page_title  = t('shipments');
$phase_label = t('phase') . ' 4';
require_once __DIR__ . '/header.php';
require __DIR__ . '/includes/coming_soon.php';
require_once __DIR__ . '/footer.php';
