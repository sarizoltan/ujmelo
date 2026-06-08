<?php
// ── Alap konfiguráció ──
define('DB_HOST',    'mysql.omega');
define('DB_PORT',    '3306');
define('DB_NAME',    'kozmetikusnak');
define('DB_USER',    'kozmetikusnak');
define('DB_PASS',    'Kozmetikusnak1230');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL',    'https://demo4.foglalasi-rendszer.hu');
define('BASE_PATH',   dirname(__FILE__));
define('UPLOAD_PATH', '/var/www/customers/vh-89415/web/home/demo4/assets/uploads/');
define('UPLOAD_URL',  BASE_URL . '/assets/uploads/');
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

// ── Időzóna ──
date_default_timezone_set('Europe/Budapest');

// ── Session beállítások ──
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
