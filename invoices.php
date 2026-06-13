<?php
/** Placeholder page — replaced by the full module in Phase 3. */
require_once __DIR__ . '/includes/auth_check.php';
require_permission('view', 'invoices.php');

$page_title  = t('invoices');
$phase_label = t('phase') . ' 3';
require_once __DIR__ . '/header.php';
require __DIR__ . '/includes/coming_soon.php';
require_once __DIR__ . '/footer.php';
