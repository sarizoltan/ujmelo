<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$days = $pdo->query("SELECT * FROM working_hours WHERE staff_id=1")->fetchAll();

foreach ($days as $h) {
    $day      = $h['day_of_week'];
    $is_open  = isset($_POST['day_open_' . $day]) && $_POST['day_open_' . $day] == '1';
    $start    = $_POST['start_' . $day] ?? '09:00';
    $end      = $_POST['end_'   . $day] ?? '18:00';

    $pdo->prepare("UPDATE working_hours SET is_day_off=?, start_time=?, end_time=? WHERE staff_id=1 AND day_of_week=?")
        ->execute([$is_open ? 0 : 1, $start, $end, $day]);
}

echo json_encode(['success' => true]);