<?php

// ── CSRF ──
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token']
           ?? $_GET['csrf_token']
           ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ── BEÁLLÍTÁSOK ──
function get_setting(string $key, string $default = ''): string {
    global $pdo;
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    $cache[$key] = ($val !== false) ? $val : $default;
    return $cache[$key];
}

function save_setting(string $key, string $value): void {
    global $pdo;
    $pdo->prepare("INSERT INTO settings (setting_key, setting_value)
                   VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
        ->execute([$key, $value]);
}

// ── XSS VÉDELEM ──
function e(?string $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

// ── SLUG GENERÁLÁS ──
function generate_slug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ő'=>'o','ö'=>'o',
        'ú'=>'u','ű'=>'u','ü'=>'u','Á'=>'a','É'=>'e','Í'=>'i',
        'Ó'=>'o','Ő'=>'o','Ö'=>'o','Ú'=>'u','Ű'=>'u','Ü'=>'u',
    ]);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return trim($text, '-');
}

// ── FOGLALÁSI AZONOSÍTÓ ──
function generate_booking_ref(): string {
    return 'BB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

// ── SZABAD IDŐPONTOK ──
function get_available_slots(PDO $pdo, int $staff_id, string $date, int $duration): array {
    $dayOfWeek = (int)date('N', strtotime($date)) - 1;

    $stmt = $pdo->prepare("SELECT * FROM working_hours WHERE staff_id = ? AND day_of_week = ?");
    $stmt->execute([$staff_id, $dayOfWeek]);
    $hours = $stmt->fetch();

    if (!$hours || $hours['is_day_off']) return [];

    $stmt = $pdo->prepare("SELECT * FROM schedule_exceptions WHERE staff_id = ? AND exception_date = ?");
    $stmt->execute([$staff_id, $date]);
    $exception = $stmt->fetch();

    if ($exception) {
        if ($exception['is_closed']) return [];
        $startTime = $exception['start_time'];
        $endTime   = $exception['end_time'];
    } else {
        $startTime = $hours['start_time'];
        $endTime   = $hours['end_time'];
    }

    $stmt = $pdo->prepare("SELECT start_time, end_time FROM bookings
                           WHERE staff_id = ? AND booking_date = ? AND status != 'cancelled'");
    $stmt->execute([$staff_id, $date]);
    $existing = $stmt->fetchAll();

    $slots   = [];
    $current = strtotime($date . ' ' . $startTime);
    $end     = strtotime($date . ' ' . $endTime);
    $interval = $duration * 60;

    while (($current + $interval) <= $end) {
        $slotStart = date('H:i', $current);
        $slotEnd   = date('H:i', $current + $interval);
        $conflict  = false;

        foreach ($existing as $booking) {
            $bStart = strtotime($date . ' ' . $booking['start_time']);
            $bEnd   = strtotime($date . ' ' . $booking['end_time']);
            if ($current < $bEnd && ($current + $interval) > $bStart) {
                $conflict = true;
                break;
            }
        }

        if (!$conflict) {
            $slots[] = ['start' => $slotStart, 'end' => $slotEnd];
        }
        $current += $interval;
    }

    return $slots;
}

// ── DÁTUM / IDŐ FORMÁZÁS ──
function format_date(string $date): string {
    return date('Y. m. d.', strtotime($date));
}

function format_time(string $time): string {
    return date('H:i', strtotime($time));
}

// ── FÁJLMÉRET FORMÁZÁS ──
function format_filesize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2)    . ' KB';
    return $bytes . ' B';
}

// ── AKTÍV NAV LINK ──
function is_active_page(string $slug): string {
    $current = trim($_SERVER['REQUEST_URI'], '/');
    return str_contains($current, $slug) ? 'active' : '';
}





// ── Email küldés ──
function send_mail(string $to, string $subject, string $body): bool {
    $site_name  = get_setting('site_name',       'Műkörmös Szalon');
    $from_email = get_setting('smtp_from_email', 'hello@foglalasi-rendszer.hu');

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$site_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";

    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}