<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'errors' => ['Hibás kérés']]);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$errors  = [];

$name    = trim($data['name']    ?? '');
$email   = trim($data['email']   ?? '');
$phone   = trim($data['phone']   ?? '');
$message = trim($data['message'] ?? '');

if (!$name)                                                    $errors[] = 'Név megadása kötelező.';
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Érvényes email megadása kötelező.';
if (!$message)                                                 $errors[] = 'Üzenet megadása kötelező.';

if ($errors) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── MENTÉS ADATBÁZISBA ──
$pdo->prepare("INSERT INTO contact_messages (name, email, phone, message, status)
               VALUES (?,?,?,?,'new')")
    ->execute([$name, $email, $phone, $message]);

// ── EMAIL AZ ADMINNAK ──
$site_name   = get_setting('site_name',  'Műkörmös Szalon');
$admin_email = get_setting('site_email', '');

if ($admin_email) {
    $subject = "📩 Új üzenet a weboldalról – {$name}";

    $body = "
<!DOCTYPE html>
<html lang='hu'>
<head><meta charset='UTF-8'>
<style>
  body { font-family:Arial,sans-serif; background:#f5f5f5; margin:0; padding:0; }
  .wrap { max-width:520px; margin:30px auto; background:#fff; border-radius:12px;
          overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.1); }
  .header { background:#1a1a1a; padding:24px 32px; }
  .header h1 { color:#c8a96e; font-size:20px; margin:0; }
  .body { padding:28px 32px; }
  .detail-row { display:flex; justify-content:space-between; padding:9px 0;
                border-bottom:1px solid #eee; font-size:14px; }
  .detail-row:last-child { border-bottom:none; }
  .detail-label { color:#888; }
  .detail-value { font-weight:600; color:#333; }
  .message-box { background:#f9f9f9; border-radius:8px; padding:16px;
                 margin-top:16px; font-size:14px; color:#444; line-height:1.7; }
  .cta { text-align:center; margin:24px 0 8px; }
  .cta a { background:#c8a96e; color:#1a1a1a; padding:12px 28px; border-radius:6px;
           text-decoration:none; font-weight:700; font-size:14px; display:inline-block; }
  .footer { background:#f9f9f9; border-top:1px solid #eee; padding:14px 32px; text-align:center; }
  .footer p { color:#aaa; font-size:12px; margin:0; }
</style>
</head>
<body>
<div class='wrap'>
  <div class='header'><h1>📩 Új kapcsolatfelvételi üzenet</h1></div>
  <div class='body'>
    <div class='detail-row'>
      <span class='detail-label'>Név</span>
      <span class='detail-value'>" . e($name) . "</span>
    </div>
    <div class='detail-row'>
      <span class='detail-label'>Email</span>
      <span class='detail-value'>" . e($email) . "</span>
    </div>
    " . ($phone ? "
    <div class='detail-row'>
      <span class='detail-label'>Telefon</span>
      <span class='detail-value'>" . e($phone) . "</span>
    </div>" : '') . "
    <div class='message-box'>" . nl2br(e($message)) . "</div>
    <div class='cta'>
      <a href='" . BASE_URL . "/admin/messages.php'>Üzenetek kezelése →</a>
    </div>
  </div>
  <div class='footer'><p>Automatikus értesítő – " . e($site_name) . "</p></div>
</div>
</body>
</html>";

    // ── send_mail() használata – NEM közvetlen mail() hívás! ──
    $sent = send_mail($admin_email, $subject, $body);
    error_log('Contact admin mail: ' . ($sent ? 'OK' : 'FAIL') . ' → ' . $admin_email);
}

echo json_encode(['success' => true]);