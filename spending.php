<?php
/** Placeholder page — replaced by the full module in Phase 5. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'spending.php');

$page_title  = t('spending');
$phase_label = t('phase') . ' 5';
require_once __DIR__ . '/header.php';
require __DIR__ . '/includes/coming_soon.php';
require_once __DIR__ . '/footer.php';
