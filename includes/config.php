<?php
// ── Alap konfiguráció ──
define('BASE_PATH', dirname(__FILE__));  // ← EZT VÁLTOZTASD MEG!
define('BASE_URL',    'https://demo3.foglalasi-rendszer.hu');
define('UPLOAD_URL',  'https://demo3.foglalasi-rendszer.hu/assets/uploads/');
define('UPLOAD_PATH', '/var/www/customers/vh-89415/web/home/demo3/assets/uploads/');
define('ADMIN_URL',   BASE_URL . '/admin');
define('SYSTEM_URL',  BASE_URL . '/rendszer/');

// ── Hibakezelés ──
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$log_dir = BASE_PATH . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

ini_set('error_log', BASE_PATH . '/logs/error.log');

// ... REST ...

// Hozd létre a logs mappát ha nincs meg
$log_dir = BASE_PATH . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

ini_set('error_log', BASE_PATH . '/logs/error.log');

// ── Időzóna ──
date_default_timezone_set('Europe/Budapest');

// ── Session beállítások ──
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// ── Adatbázis ──
define('DB_HOST', 'mysql.omega');
define('DB_PORT', '3306');
define('DB_NAME', 'mukormosnek');
define('DB_USER', 'mukormosnek');
define('DB_PASS', 'Mukormosnek1230');

// ── URL-ek ──

define('UPLOAD_PATH', '/var/www/html/assets/uploads/'); // ← info.php-val ellenőrizd!