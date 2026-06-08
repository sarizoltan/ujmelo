<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../includes/db.php';
require_once '../includes/functions.php'; // ← EZ HIÁNYZOTT!

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Csak POST kérés engedélyezett.']);
    exit;
}

// ── Bemeneti adatok ──
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$staff_id   = (int)($input['staff_id']      ?? 0);
$service_id = (int)($input['service_id']    ?? 0);
$date       = trim($input['booking_date']   ?? '');
$start_time = trim($input['start_time']     ?? '');
$cust_name  = trim($input['customer_name']  ?? '');
$cust_email = trim($input['customer_email'] ?? '');
$cust_phone = trim($input['customer_phone'] ?? '');
$notes      = trim($input['notes']          ?? '');

// ── Validálás ──
$errors = [];
if (!$staff_id)   $errors[] = 'Kozmetikus kiválasztása kötelező.';
if (!$service_id) $errors[] = 'Kezelés kiválasztása kötelező.';
if (!$date)       $errors[] = 'Dátum megadása kötelező.';
if (!$start_time) $errors[] = 'Időpont megadása kötelező.';
if (!$cust_name)  $errors[] = 'Név megadása kötelező.';
if (!$cust_email) $errors[] = 'Email cím megadása kötelező.';
elseif (!filter_var($cust_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Érvénytelen email cím.';

$date_obj = DateTime::createFromFormat('Y-m-d', $date);
if (!$date_obj || $date_obj->format('Y-m-d') !== $date) $errors[] = 'Érvénytelen dátum formátum.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── Múltbeli dátum ──
if ($date_obj < new DateTime('today')) {
    echo json_encode(['success' => false, 'errors' => ['Múltbeli dátumra nem lehet foglalni.']]);
    exit;
}

// ── Kozmetikus ellenőrzés ──
$stmt = $pdo->prepare("SELECT * FROM staff WHERE id=? AND active=1");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();
if (!$staff) {
    echo json_encode(['success' => false, 'errors' => ['Érvénytelen kozmetikus.']]);
    exit;
}

// ── Szolgáltatás ellenőrzés ──
$stmt = $pdo->prepare("SELECT * FROM services WHERE id=? AND active=1");
$stmt->execute([$service_id]);
$service = $stmt->fetch();
if (!$service) {
    echo json_encode(['success' => false, 'errors' => ['Érvénytelen szolgáltatás.']]);
    exit;
}

// ── staff_services ellenőrzés (ha létezik a tábla) ──
try {
    $ss = $pdo->prepare("SELECT 1 FROM staff_services WHERE staff_id=? AND service_id=?");
    $ss->execute([$staff_id, $service_id]);
    if (!$ss->fetch()) {
        echo json_encode(['success' => false, 'errors' => ['Ez a kozmetikus nem nyújtja ezt a szolgáltatást.']]);
        exit;
    }
} catch (PDOException $e) {
    // Ha a tábla nem létezik, átugorjuk az ellenőrzést
}

$duration = (int)$service['duration'];
$end_time = date('H:i', strtotime($date . ' ' . $start_time) + $duration * 60);

// ── Szabad időpont ellenőrzés ──
$available  = get_available_slots($pdo, $staff_id, $date, $duration);
$slot_valid = false;
foreach ($available as $slot) {
    if ($slot['start'] === $start_time) {
        $slot_valid = true;
        break;
    }
}
if (!$slot_valid) {
    echo json_encode(['success' => false, 'errors' => ['Ez az időpont már nem elérhető.']]);
    exit;
}

// ── Rate limiting (5 foglalás/óra emailenként) ──
$rate = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_email=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$rate->execute([$cust_email]);
if ($rate->fetchColumn() >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'errors' => ['Túl sok foglalási kísérlet. Próbáld újra egy óra múlva.']]);
    exit;
}

// ── Mentés ──
try {
    $ref = generate_booking_ref();
    $pdo->prepare("INSERT INTO bookings
        (booking_ref, staff_id, service_id, customer_name, customer_email, customer_phone,
         booking_date, start_time, end_time, status, notes)
        VALUES (?,?,?,?,?,?,?,?,?,'pending',?)")
        ->execute([$ref, $staff_id, $service_id, $cust_name, $cust_email, $cust_phone,
                   $date, $start_time, $end_time, $notes]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Adatbázis hiba. Kérjük próbáld újra.']]);
    exit;
}

// ── Email értesítők ──
send_booking_confirmation($cust_email, $cust_name, $ref, $service, $staff, $date, $start_time, $end_time);
send_admin_notification($ref, $cust_name, $cust_email, $cust_phone, $service, $staff, $date, $start_time, $end_time, $notes);

// ── Válasz ──
echo json_encode([
    'success'      => true,
    'booking_ref'  => $ref,
    'message'      => 'Kezelés foglalva!',
    'booking_date' => $date,
    'start_time'   => $start_time,
    'end_time'     => $end_time,
]);

// ══════════════════════════════════════
// EMAIL FÜGGVÉNYEK
// ══════════════════════════════════════

function send_booking_confirmation(
    string $to_email, string $to_name,
    string $ref, array $service, array $staff,
    string $date, string $start, string $end
): void {
    $site_name  = get_setting('site_name',   'Kozmetikai Szalon');
    $site_phone = get_setting('site_phone',  '');
    $site_email = get_setting('site_email',  '');
    $site_addr  = get_setting('site_address','');
    $date_hu    = date('Y. m. d.', strtotime($date));
    $price_fmt  = number_format((float)$service['price'], 0, ',', ' ') . ' Ft';
    $subject    = "💅 Kezelés visszaigazolása – {$ref}";

    $body = "<!DOCTYPE html><html lang='hu'><head><meta charset='UTF-8'>
<style>
  body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:0;}
  .wrap{max-width:580px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.1);}
  .hdr{background:#1a1a1a;padding:32px;text-align:center;}
  .hdr h1{color:#c8a96e;font-size:22px;margin:0;letter-spacing:1px;}
  .hdr p{color:#999;font-size:13px;margin:6px 0 0;}
  .bdy{padding:32px;}
  .ref-box{background:#f9f5ef;border:2px solid #c8a96e;border-radius:8px;padding:16px 24px;text-align:center;margin:20px 0;}
  .ref-box .lbl{font-size:12px;color:#999;text-transform:uppercase;letter-spacing:1px;}
  .ref-box .ref{font-size:24px;font-weight:700;color:#1a1a1a;letter-spacing:2px;font-family:monospace;}
  .details{background:#f9f9f9;border-radius:8px;padding:20px;margin:20px 0;}
  .dr{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:14px;}
  .dr:last-child{border-bottom:none;}
  .dl{color:#888;} .dv{font-weight:600;color:#333;}
  .info{background:#fffbf0;border-left:4px solid #c8a96e;padding:14px 18px;border-radius:0 8px 8px 0;font-size:13px;color:#666;margin:20px 0;}
  .cta{text-align:center;margin:28px 0;}
  .cta a{background:#c8a96e;color:#1a1a1a;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:700;font-size:15px;display:inline-block;}
  .ftr{background:#1a1a1a;padding:20px 32px;text-align:center;}
  .ftr p{color:#666;font-size:12px;margin:4px 0;}
  .ftr a{color:#c8a96e;text-decoration:none;}
</style>
</head><body>
<div class='wrap'>
  <div class='hdr'><h1>💅 {$site_name}</h1><p>Kezelés visszaigazolása</p></div>
  <div class='bdy'>
    <p>Kedves <strong>{$to_name}</strong>!</p>
    <p>Köszönjük a foglalásod! Az alábbiakban találod a kezelés részleteit:</p>
    <div class='ref-box'>
      <div class='lbl'>Foglalási azonosító</div>
      <div class='ref'>{$ref}</div>
    </div>
    <div class='details'>
      <div class='dr'><span class='dl'>Kezelés</span><span class='dv'>{$service['name']}</span></div>
      <div class='dr'><span class='dl'>Kozmetikus</span><span class='dv'>{$staff['name']}</span></div>
      <div class='dr'><span class='dl'>Dátum</span><span class='dv'>{$date_hu}</span></div>
      <div class='dr'><span class='dl'>Időpont</span><span class='dv'>{$start} – {$end}</span></div>
      <div class='dr'><span class='dl'>Időtartam</span><span class='dv'>{$service['duration']} perc</span></div>
      <div class='dr'><span class='dl'>Ár</span><span class='dv'>{$price_fmt}</span></div>
    </div>
    <div class='info'>ℹ️ Ha módosítani vagy lemondani szeretnéd a foglalásod, kérjük vedd fel velünk a kapcsolatot legalább <strong>24 órával</strong> előtte.</div>
    <div class='cta'><a href='" . BASE_URL . "/foglalas'>Új kezelés foglalása</a></div>
  </div>
  <div class='ftr'>
    " . ($site_addr  ? "<p>📍 {$site_addr}</p>"  : '') . "
    " . ($site_phone ? "<p>📞 <a href='tel:{$site_phone}'>{$site_phone}</a></p>" : '') . "
    " . ($site_email ? "<p>✉️ <a href='mailto:{$site_email}'>{$site_email}</a></p>" : '') . "
    <p style='margin-top:12px;'>© " . date('Y') . " {$site_name}</p>
  </div>
</div>
</body></html>";

    send_mail($to_email, $subject, $body);
}

function send_admin_notification(
    string $ref, string $cust_name, string $cust_email, string $cust_phone,
    array $service, array $staff,
    string $date, string $start, string $end, string $notes
): void {
    $site_name   = get_setting('site_name',  'Kozmetikai Szalon');
    $admin_email = get_setting('site_email', '');
    if (!$admin_email) return;

    $date_hu = date('Y. m. d.', strtotime($date));
    $subject = "🔔 Új kezelés érkezett – {$ref}";

    $body = "<!DOCTYPE html><html lang='hu'><head><meta charset='UTF-8'>
<style>
  body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:0;}
  .wrap{max-width:560px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.1);}
  .hdr{background:#1a1a1a;padding:24px 32px;}
  .hdr h1{color:#c8a96e;font-size:20px;margin:0;}
  .hdr p{color:#999;font-size:13px;margin:4px 0 0;}
  .bdy{padding:28px 32px;}
  .dr{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #eee;font-size:14px;}
  .dr:last-child{border-bottom:none;}
  .dl{color:#888;} .dv{font-weight:600;color:#333;}
  .cta{text-align:center;margin:24px 0 8px;}
  .cta a{background:#c8a96e;color:#1a1a1a;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:700;font-size:14px;display:inline-block;}
  .ftr{background:#f9f9f9;border-top:1px solid #eee;padding:14px 32px;text-align:center;}
  .ftr p{color:#aaa;font-size:12px;margin:0;}
</style>
</head><body>
<div class='wrap'>
  <div class='hdr'><h1>🔔 Új kezelés érkezett</h1><p>{$site_name} – Admin értesítő</p></div>
  <div class='bdy'>
    <div class='dr'><span class='dl'>Referencia</span><span class='dv'>{$ref}</span></div>
    <div class='dr'><span class='dl'>Ügyfél neve</span><span class='dv'>{$cust_name}</span></div>
    <div class='dr'><span class='dl'>Email</span><span class='dv'>{$cust_email}</span></div>
    <div class='dr'><span class='dl'>Telefon</span><span class='dv'>" . ($cust_phone ?: '–') . "</span></div>
    <div class='dr'><span class='dl'>Kozmetikus</span><span class='dv'>{$staff['name']}</span></div>
    <div class='dr'><span class='dl'>Kezelés</span><span class='dv'>{$service['name']}</span></div>
    <div class='dr'><span class='dl'>Dátum</span><span class='dv'>{$date_hu}</span></div>
    <div class='dr'><span class='dl'>Időpont</span><span class='dv'>{$start} – {$end}</span></div>
    " . ($notes ? "<div class='dr'><span class='dl'>Megjegyzés</span><span class='dv'>{$notes}</span></div>" : '') . "
    <div class='cta'><a href='" . BASE_URL . "/admin/bookings.php'>Foglalások kezelése →</a></div>
  </div>
  <div class='ftr'><p>Automatikus értesítő – {$site_name}</p></div>
</div>
</body></html>";

    send_mail($admin_email, $subject, $body);
}