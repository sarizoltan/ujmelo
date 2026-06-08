<?php
define('DB_HOST',    'mysql.omega');
define('DB_PORT',    '3306');
define('DB_NAME',    'kozmetikusnak');
define('DB_USER',    'kozmetikusnak');
define('DB_PASS',    'Kozmetikusnak1230');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL',    'https://demo4.foglalasi-rendszer.hu');
define('BASE_PATH',   dirname(__FILE__));  // ← EZT IS!
define('UPLOAD_PATH', '/var/www/customers/vh-89415/web/home/demo4/assets/uploads/');
define('UPLOAD_URL',  BASE_URL . '/assets/uploads/');
define('ADMIN_URL',   BASE_URL . '/admin');
define('SYSTEM_URL',  BASE_URL . '/rendszer/');

// ... REST ...

try {
    $dsn     = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die('Adatbázis kapcsolódási hiba: ' . $e->getMessage());
}

// Függvények betöltése
require_once __DIR__ . '/functions.php';

ini_set('display_errors', 1);  // CSAK TESZTELÉSHEZ!
