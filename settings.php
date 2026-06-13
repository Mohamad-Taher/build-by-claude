<?php
/** Placeholder page — replaced by the full module in Phase 6. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'settings.php');

$page_title  = t('settings');
$phase_label = t('phase') . ' 6';
require_once __DIR__ . '/header.php';
require __DIR__ . '/includes/coming_soon.php';
require_once __DIR__ . '/footer.php';
