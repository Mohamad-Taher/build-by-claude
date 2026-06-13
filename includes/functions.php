<?php
/**
 * Shared helpers: translation, settings, money, currency conversion,
 * audit logging, JSON responses, escaping.
 */
require_once __DIR__ . '/../connectdb.php';

// ----------------------------------------------------------------------
// Settings (cached per request)
// ----------------------------------------------------------------------
function get_setting(string $key, ?string $default = null): ?string
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        $stmt = db()->query('SELECT setting_key, setting_value FROM tbl_settings');
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings[$key] ?? $default;
}

// ----------------------------------------------------------------------
// Language / translation
// ----------------------------------------------------------------------

/** Active language code: session > system default > 'en'. */
function current_lang(): string
{
    $lang = $_SESSION['lang'] ?? get_setting('default_language', 'en');
    return in_array($lang, APP_LANGS, true) ? $lang : 'en';
}

/** True when the active language is written right-to-left. */
function is_rtl(): bool
{
    return in_array(current_lang(), RTL_LANGS, true);
}

/**
 * Translate a UI key for the active language, falling back to English,
 * then to the key itself (so missing keys are visible, never fatal).
 */
function t(string $key): string
{
    static $strings = null, $fallback = null;
    if ($strings === null) {
        $lang     = current_lang();
        $fallback = require __DIR__ . '/../lang/en.php';
        $strings  = ($lang === 'en') ? $fallback : require __DIR__ . '/../lang/' . $lang . '.php';
    }
    return $strings[$key] ?? $fallback[$key] ?? $key;
}

/** Pick the page name column matching the active language. */
function page_name(array $page): string
{
    $col = 'name_' . current_lang();
    return $page[$col] ?: $page['name_en'];
}

// ----------------------------------------------------------------------
// Money / currency
// ----------------------------------------------------------------------

/**
 * Convert a native amount to base currency (IQD) using the locked rate.
 * The result must be persisted in amount_base and never recomputed.
 */
function to_base(float $amount, float $rate): float
{
    return round($amount * $rate, 2);
}

/**
 * Format money for display: IQD with thousands separators and no decimals,
 * USD (and others) with 2 decimals.
 */
function money(float $amount, string $currency = BASE_CURRENCY): string
{
    if (strtoupper($currency) === 'IQD') {
        return number_format($amount, 0) . ' IQD';
    }
    return '$' . number_format($amount, 2);
}

// ----------------------------------------------------------------------
// Audit log
// ----------------------------------------------------------------------
function audit_log(string $action, string $table, ?int $recordId = null, string $details = ''): void
{
    $stmt = db()->prepare(
        'INSERT INTO tbl_audit_log (user_id, action, table_name, record_id, details)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$_SESSION['user_id'] ?? null, $action, $table, $recordId, $details]);
}

// ----------------------------------------------------------------------
// Output helpers
// ----------------------------------------------------------------------

/** HTML-escape shortcut. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Send a JSON response from an AJAX endpoint and stop. */
function json_response(bool $success, string $message = '', array $extra = []): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------------------------------------------------------------
// Car status pipeline helpers
// ----------------------------------------------------------------------

/** Ordered list of car pipeline statuses (matches the tbl_cars ENUM). */
function car_statuses(): array
{
    return ['purchased', 'paid', 'shipped', 'in_transit', 'at_port', 'cleared', 'in_showroom', 'sold'];
}

/** AdminLTE badge color for a car status. */
function status_badge_class(string $status): string
{
    return [
        'purchased'   => 'secondary',
        'paid'        => 'info',
        'shipped'     => 'primary',
        'in_transit'  => 'warning',
        'at_port'     => 'warning',
        'cleared'     => 'info',
        'in_showroom' => 'success',
        'sold'        => 'success',
    ][$status] ?? 'secondary';
}

/** Rendered status badge (translated label + colored badge). */
function status_badge(string $status): string
{
    return '<span class="badge badge-' . status_badge_class($status) . '">'
         . e(t('status_' . $status)) . '</span>';
}

// ----------------------------------------------------------------------
// Landed cost
// ----------------------------------------------------------------------

/** Total landed cost of a car in base currency = SUM(amount_base) of its cost lines. */
function car_landed_cost(int $carId): float
{
    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(amount_base), 0) AS total
           FROM tbl_car_costs
          WHERE car_id = ? AND deleted_at IS NULL'
    );
    $stmt->execute([$carId]);
    return (float)$stmt->fetch()['total'];
}

// ----------------------------------------------------------------------
// File uploads (invoice attachments / photos) — images + PDF only
// ----------------------------------------------------------------------

/**
 * Validate and store an uploaded file under /uploads with a unique name.
 * Returns the stored relative path, or null when no file was submitted.
 * Throws RuntimeException (message = a t() key) on a rejected/failed upload.
 */
function handle_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('upload_failed');
    }

    // Whitelist by extension AND verified MIME type
    $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'pdf'  => 'application/pdf',
    ];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        throw new RuntimeException('invalid_file');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('invalid_file');
    }

    if (!is_dir(UPLOAD_PATH) && !mkdir(UPLOAD_PATH, 0775, true) && !is_dir(UPLOAD_PATH)) {
        throw new RuntimeException('upload_failed');
    }
    $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $dest = UPLOAD_PATH . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('upload_failed');
    }
    return UPLOAD_URL . '/' . $name;
}
