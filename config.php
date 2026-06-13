<?php
/**
 * Global configuration — Car Import Management System
 * Plain PHP 8 on XAMPP. Adjust DB credentials to your local setup.
 */

// ---- Database --------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'carimport');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---- Application -----------------------------------------------------
define('BASE_CURRENCY', 'IQD');            // all *_base amounts are stored in IQD
define('UPLOAD_PATH', __DIR__ . '/uploads');
define('UPLOAD_URL', 'uploads');           // relative URL for stored files
define('APP_LANGS', ['en', 'ar', 'ku']);   // supported languages (en = fallback)
define('RTL_LANGS', ['ar', 'ku']);

date_default_timezone_set('Asia/Baghdad');

// ---- Session ---------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
