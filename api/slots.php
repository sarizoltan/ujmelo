<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../includes/db.php';
require_once '../includes/functions.php';

// ── Bemeneti adatok validálása ──
$staff_id   = filter_input(INPUT_GET, 'staff_id',   FILTER_VALIDATE_INT);
$service_id = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
$date       = filter_input(INPUT_GET, 'date',       FILTER_SANITIZE_SPECIAL_CHARS);
$duration   = filter_input(INPUT_GET, 'duration',   FILTER_VALIDATE_INT);

// ── Kötelező mezők ──
if (!$staff_id || !$date || !$duration) {
    http_response_code(400);
    echo json_encode(['error' => 'Hiányzó paraméterek: staff_id, date, duration kötelező.']);
    exit;
}

// ── Dátum validálás ──
$date_obj = DateTime::createFromFormat('Y-m-d', $date);
if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
    http_response_code(400);
    echo json_encode(['error' => 'Érvénytelen dátum formátum. Elvárt: YYYY-MM-DD']);
    exit;
}

// ── Ne fogadjon el múltbeli dátumot ──
$today = new DateTime('today');
if ($date_obj < $today) {
    echo json_encode([]);
    exit;
}

// ── Előre foglalható napok limit ──
$advance_days = (int)get_setting('booking_advance_days', '30');
$max_date = new DateTime("+{$advance_days} days");
if ($date_obj > $max_date) {
    echo json_encode([]);
    exit;
}

// ── Műkörmös létezik-e és aktív-e ──
$stmt = $pdo->prepare("SELECT id FROM staff WHERE id=? AND active=1");
$stmt->execute([$staff_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'A megadott műkörmös nem található vagy inaktív.']);
    exit;
}

// ── Időtartam validálás ──
if ($duration < 5 || $duration > 480) {
    http_response_code(400);
    echo json_encode(['error' => 'Érvénytelen időtartam.']);
    exit;
}

// ── Ha service_id meg van adva, ellenőrzés ──
if ($service_id) {
    $svc = $pdo->prepare("SELECT duration FROM services WHERE id=? AND active=1");
    $svc->execute([$service_id]);
    $svc = $svc->fetch();
    if ($svc) {
        $duration = (int)$svc['duration'];
    }

    // Műkörmös nyújtja-e ezt a szolgáltatást?
    $ss = $pdo->prepare("SELECT 1 FROM staff_services WHERE staff_id=? AND service_id=?");
    $ss->execute([$staff_id, $service_id]);
    if (!$ss->fetch()) {
        echo json_encode([]);
        exit;
    }
}

// ── Szabad időpontok lekérése ──
$slots = get_available_slots($pdo, $staff_id, $date, $duration);

echo json_encode($slots);