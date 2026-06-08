<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function admin_login(string $username, string $password): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_name'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        session_regenerate_id(true);
        return true;
    }
    return false;
}

function admin_logout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

function current_admin(): array {
    return [
        'id'   => $_SESSION['admin_id']   ?? 0,
        'name' => $_SESSION['admin_name'] ?? '',
        'role' => $_SESSION['admin_role'] ?? '',
    ];
}