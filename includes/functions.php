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
