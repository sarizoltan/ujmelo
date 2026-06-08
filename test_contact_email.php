<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Szimulált contact form küldés
$result1 = send_admin_notification_contact(
    'Teszt Elek',
    'weboldalajanlatok@gmail.com',
    '+36301234567',
    'Teszt Kft',
    'Ez egy teszt üzenet a debug szkriptből.',
    null
);

$result2 = send_client_autoreply('Teszt Elek', 'weboldalajanlatok@gmail.com');

echo '<pre>';
echo 'Admin értesítő: ' . ($result1 ? '✅ true' : '❌ false') . "\n";
echo 'Auto-reply:     ' . ($result2 ? '✅ true' : '❌ false') . "\n";
echo '</pre>';