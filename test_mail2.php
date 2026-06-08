<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

echo '<pre>';

// 1. get_setting teszt
$site_email = get_setting('site_email', '');
$site_name  = get_setting('site_name', '');
echo 'site_email: ' . $site_email . "\n";
echo 'site_name: '  . $site_name  . "\n\n";

// 2. Közvetlen send_mail teszt
$result = send_mail(
    'weboldalajanlatok@gmail.com',
    'Teszt subject',
    '<h1>Teszt body</h1>'
);
echo 'send_mail(): ' . ($result ? '✅ true' : '❌ false') . "\n";

echo '</pre>';