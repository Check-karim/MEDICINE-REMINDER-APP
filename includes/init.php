<?php

define('MEDAPP', true);

$config = require dirname(__DIR__) . '/config.php';
$GLOBALS['config'] = $config;

date_default_timezone_set($config['timezone']);
define('PASSWORD_MIN', (int) $config['password_min']);
define('DUE_GRACE_MINUTES', (int) $config['due_grace_minutes']);

ini_set('session.use_strict_mode', '1');
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $https,
]);
session_start();

require __DIR__ . '/db.php';
require __DIR__ . '/validators.php';
require __DIR__ . '/records.php';
require __DIR__ . '/pdf.php';
require __DIR__ . '/layout.php';

try {
    db();
    if (!empty($_SESSION['user_id'])) {
        $user = user_by_id($_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id']);
        } else {
            $GLOBALS['auth_user'] = $user;
        }
    }
} catch (Throwable $e) {
    boot_fail($e);
}

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

function boot_fail($e)
{
    http_response_code(500);
    $detail = '';
    if (!empty($GLOBALS['config']['debug'])) {
        $detail = ' ' . $e->getMessage();
    }
    $message = 'The app could not reach MySQL. Start MySQL in XAMPP, then refresh this page.' . $detail;
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Database unavailable</title></head>';
    echo '<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#f3efe8;color:#1a2824;font-family:Segoe UI,sans-serif;padding:24px;">';
    echo '<main style="max-width:440px;background:#fff;border-radius:24px;padding:24px;">';
    echo '<h1 style="font-size:1.4rem;margin:0 0 8px;">MySQL is not available</h1>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</main></body></html>';
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_base()
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/' || $dir === '.' || $dir === '') {
        return $base = '';
    }
    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }
    if ($dir === '/' || $dir === '') {
        return $base = '';
    }
    return $base = rtrim($dir, '/');
}

function url($path)
{
    $path = str_replace(["\r", "\n", '\\'], '', (string) $path);
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        $path = '';
    }
    $path = ltrim($path, '/');
    $base = app_base();
    if ($base === '') {
        return '/' . $path;
    }
    return $base . '/' . $path;
}

function asset($path)
{
    $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $version = is_file($full) ? (string) filemtime($full) : '1';
    return url($path . '?v=' . $version);
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function is_post()
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function current_user()
{
    return isset($GLOBALS['auth_user']) ? $GLOBALS['auth_user'] : null;
}

function require_login()
{
    if (!current_user()) {
        redirect('login.php');
    }
}

function require_patient()
{
    require_login();
    if (current_user()['role'] !== 'patient') {
        redirect('admin/index.php');
    }
}

function require_admin()
{
    require_login();
    if (current_user()['role'] !== 'admin') {
        redirect('dashboard.php');
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf()
{
    $sent = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    $known = isset($_SESSION['csrf']) ? (string) $_SESSION['csrf'] : '';
    if ($known === '' || $sent === '' || !hash_equals($known, $sent)) {
        set_flash('error', 'The form expired. Please try again.');
        if (current_user()) {
            redirect(current_user()['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
        }
        redirect('login.php');
    }
}

function set_flash($type, $message)
{
    if ($type !== 'error') {
        $type = 'success';
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function render_flashes()
{
    $items = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    foreach ($items as $item) {
        echo '<div class="flash flash-' . e($item['type']) . '" role="status">' . e($item['message']) . '</div>';
    }
}

function current_path()
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = str_replace('\\', '/', (string) $path);
    $base = app_base();
    if ($base !== '' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base));
    }
    $path = ltrim($path, '/');
    $query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    if ($query !== '') {
        $path .= '?' . $query;
    }
    return $path;
}

function safe_return($path)
{
    $path = str_replace('\\', '/', (string) $path);
    if (strlen($path) > 200) {
        return 'reminders.php';
    }
    if (preg_match('#^(dashboard|reminders|activity)\.php(\?[A-Za-z0-9&=%._:-]*)?$#', $path)) {
        return $path;
    }
    return 'reminders.php';
}

function greeting()
{
    $hour = (int) date('G');
    if ($hour < 12) {
        return 'Good morning';
    }
    if ($hour < 17) {
        return 'Good afternoon';
    }
    return 'Good evening';
}

function first_name($full)
{
    $parts = preg_split('/\s+/', trim((string) $full));
    if (!$parts || $parts[0] === '') {
        return 'there';
    }
    return $parts[0];
}

function initials($full)
{
    $parts = preg_split('/\s+/', trim((string) $full));
    $letters = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $letters .= strtoupper(substr($part, 0, 1));
        if (strlen($letters) === 2) {
            break;
        }
    }
    return $letters === '' ? '?' : $letters;
}
