<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        if (admin_login($username, $password)) {
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        } else {
            $error = 'Hibás felhasználónév vagy jelszó!';
        }
    } else {
        $error = 'Kérjük töltsd ki az összes mezőt!';
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Belépés – Kozmetikus Szalon</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-page">

<div class="login-wrapper">
    <div class="login-box">
        <div class="login-logo">
            <i class="fas fa-cut"></i>
            <h1>Kozmetikus <span>Admin</span></h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Felhasználónév</label>
                <input type="text" id="username" name="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       placeholder="admin" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Jelszó</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Belépés
            </button>
        </form>

        <p class="login-hint">Alapértelmezett: <strong>admin</strong> / <strong>admin1234</strong></p>
    </div>
</div>

</body>
</html>