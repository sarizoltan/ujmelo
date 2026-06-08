<?php
require_once 'includes/db.php';

// Megnézzük mi van a settings táblában
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'site_phone')");
$rows = $stmt->fetchAll();

echo '<pre>';
foreach ($rows as $row) {
    echo $row['setting_key'] . ' = ' . $row['setting_value'] . "\n";
}
echo '</pre>';