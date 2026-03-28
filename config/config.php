<?php
/**
 * ============================================================
 * AMANI ESCROW v2.0 — CONFIGURATION
 * ============================================================
 *
 * CPANEL SETUP — FILL IN THESE 4 VALUES:
 *
 *   1. Go to cPanel → MySQL® Databases
 *   2. Note your database name     → paste into $db_name below
 *   3. Note your MySQL username     → paste into $db_user below
 *   4. Note your MySQL password     → paste into $db_pass below
 *   5. MySQL host is usually "localhost" on shared hosting.
 *      If cPanel shows a different hostname (e.g. mysql.yourdomain.com),
 *      paste that into $db_host instead.
 *
 * IMPORTANT: Make sure the MySQL user is ADDED TO the database
 *            with ALL PRIVILEGES in cPanel → MySQL Databases.
 *
 * ALTERNATIVE: Create config/db.local.php (see bottom of this file).
 * ============================================================
 */

// ── DATABASE CREDENTIALS ────────────────────────────────────
// Paste your cPanel values here:
$db_host    = 'localhost';                // MySQL host from cPanel (usually "localhost")
$db_name    = 'zurihubc_LeadEX';         // e.g. cpaneluser_dbname
$db_user    = 'zurihubc_LeadEX';         // e.g. cpaneluser_dbuser
$db_pass    = 'Q6mlnC@8u68G,J^a';                         // ← PUT YOUR PASSWORD HERE
$db_port    = '';                         // Leave empty for default 3306
$db_socket  = '';                         // Leave empty unless cPanel specifies a socket path
$db_charset = 'utf8mb4';

// ── OPTIONAL: LOCAL OVERRIDE FILE ───────────────────────────
// If config/db.local.php exists it can override any $db_* variable above.
// This keeps secrets out of version control.
// Example db.local.php contents:
//   <?php
//   $db_pass = 'my_actual_password';
$_local_config = __DIR__ . '/db.local.php';
if (file_exists($_local_config)) {
    require $_local_config;
}

// ── PUBLISH AS CONSTANTS (do not edit below) ────────────────
define('DB_HOST',    $db_host);
define('DB_NAME',    $db_name);
define('DB_USER',    $db_user);
define('DB_PASS',    $db_pass);
define('DB_PORT',    $db_port);
define('DB_SOCKET',  $db_socket);
define('DB_CHARSET', $db_charset);

// ── DEBUG FLAG ──────────────────────────────────────────────
// ⚠️  Set TRUE *briefly* to see the real PDO error on screen.
// ⚠️  Set back to FALSE immediately after — it exposes host/user info.
define('APP_DB_DEBUG', false);

// ── APPLICATION URL ─────────────────────────────────────────
// Your live domain — no trailing slash.
// If the app is at the domain root:   https://manage.zurihub.co.ke
// If in a subfolder:                  https://manage.zurihub.co.ke/escrow
define('APP_URL', 'https://manage.zurihub.co.ke');

// ── APP META ────────────────────────────────────────────────
define('APP_NAME',    'Amani Escrow');
define('APP_VERSION', '2.0.0');

// Auto-detect environment
$_host = $_SERVER['HTTP_HOST'] ?? '';
define('APP_ENV', (
    in_array($_host, ['localhost', '127.0.0.1', '::1'], true)
    || strpos($_host, 'localhost:') === 0
) ? 'development' : 'production');

define('APP_ROOT', dirname(__DIR__));

// ── SECURITY KEYS ───────────────────────────────────────────
define('APP_KEY',          'CHANGE-ME-to-random-64-chars-zurihub-amani-2024-escrow-secure');
define('CSRF_TOKEN_NAME',  '_amani_csrf');
define('SESSION_NAME',     'amani_sid');

// ── UPLOADS ─────────────────────────────────────────────────
define('UPLOAD_DIR',   APP_ROOT . '/uploads');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'image/svg+xml', 'image/x-icon',
]);

// ── SESSION ─────────────────────────────────────────────────
define('SESSION_LIFETIME', 7200);
define('SESSION_SECURE',   !empty($_SERVER['HTTPS']));
define('SESSION_HTTPONLY',  true);

// ── TIMEZONE ────────────────────────────────────────────────
date_default_timezone_set('Africa/Nairobi');

// ── ERROR REPORTING ─────────────────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);          // still log everything
    ini_set('display_errors', 0);    // but never show to visitors
    ini_set('log_errors', 1);        // write to server error log
}
