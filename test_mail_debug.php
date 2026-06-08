<?php
// ── FONTOS: töltsd fel, teszteld, majd TÖRÖLD! ──

$to      = 'weboldalajanlatok@gmail.com';
$subject = '=?UTF-8?B?' . base64_encode('Demo1 teszt email') . '?=';
$body    = '<h1>Teszt</h1><p>Ez egy teszt email a demo1 szerverről.</p>';

$from_email = 'noreply@demo1.foglalasi-rendszer.hu';

$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
$headers .= 'From: Demo1 <' . $from_email . '>' . "\r\n";
$headers .= 'Reply-To: ' . $from_email . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion();

$result = mail($to, $subject, $body, $headers);

echo '<pre>';
echo 'mail() eredmény: ' . ($result ? '✅ true' : '❌ false') . "\n\n";

// PHP mail konfig
echo 'sendmail_path: ' . ini_get('sendmail_path') . "\n";
echo 'SMTP: '          . ini_get('SMTP')          . "\n";
echo 'smtp_port: '     . ini_get('smtp_port')     . "\n\n";

// error_log helye
echo 'error_log: ' . ini_get('error_log') . "\n";

// Próbáljunk különböző From: címekkel
$from_options = [
    'noreply@demo1.foglalasi-rendszer.hu',
    'noreply@foglalasi-rendszer.hu',
    'weboldalajanlatok@gmail.com',
];

foreach ($from_options as $from) {
    $h  = 'MIME-Version: 1.0' . "\r\n";
    $h .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
    $h .= 'From: ' . $from . "\r\n";
    $r  = mail($to, 'Teszt from: ' . $from, '<p>Teszt</p>', $h);
    echo 'From: ' . $from . ' → ' . ($r ? '✅ true' : '❌ false') . "\n";
}

echo '</pre>';