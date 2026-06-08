<?php
$to      = 'weboldalajanlatok@gmail.com';
$subject = 'XAMPP teszt email';
$message = 'Ha ezt olvasod, működik az email küldés!';
$headers = 'From: weboldalajanlatok@gmail.com';

if (mail($to, $subject, $message, $headers)) {
    echo '✅ Email elküldve!';
} else {
    echo '❌ Hiba történt!';
}