<?php
function booking_status_label(string $status): string {
    return match($status) {
        'pending'   => 'Függőben',
        'confirmed' => 'Megerősítve',
        'cancelled' => 'Lemondva',
        'completed' => 'Teljesítve',
        default     => $status,
    };
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Admin') ?> – Műkörmös Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-hand-sparkles"></i>
        <span>Műkörmös <strong>Admin</strong></span>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <!-- ── Áttekintés ── -->
            <li class="nav-section">Áttekintés</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/index.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <!-- ── Foglalások ── -->
            <li class="nav-section">Foglalások</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/bookings.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i> Foglalások
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/staff.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-nurse"></i> Műkörmösök
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/services.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : '' ?>">
                    <i class="fas fa-concierge-bell"></i> Szolgáltatások
                </a>
            </li>

            <!-- ── Tartalom ── -->
            <li class="nav-section">Tartalom</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/homepage.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'homepage.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Főoldal szerkesztő
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/contact.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : '' ?>">
                    <i class="fas fa-address-card"></i> Kapcsolat oldal
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/pages.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'pages.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i> Oldalak
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/posts.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'posts.php' ? 'active' : '' ?>">
                    <i class="fas fa-blog"></i> Blog
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/menus.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'menus.php' ? 'active' : '' ?>">
                    <i class="fas fa-bars"></i> Menük
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/media.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'media.php' ? 'active' : '' ?>">
                    <i class="fas fa-images"></i> Média
                </a>
            </li>

            <!-- ── Egyéb ── -->
            <li class="nav-section">Egyéb</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/messages.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Üzenetek
                    <?php
                    $new_msg = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status='new'")->fetchColumn();
                    if ($new_msg > 0): ?>
                        <span class="badge-count"><?= $new_msg ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if (current_admin()['role'] === 'superadmin'): ?>
            <li>
                <a href="<?= BASE_URL ?>/admin/users.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Felhasználók
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="<?= BASE_URL ?>/admin/settings.php"
                   class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> Beállítások
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>" target="_blank">
            <i class="fas fa-external-link-alt"></i> Weboldal
        </a>
        <a href="<?= BASE_URL ?>/admin/logout.php">
            <i class="fas fa-sign-out-alt"></i> Kilépés
        </a>
    </div>
</aside>

<!-- Main -->
<div class="admin-main">
    <header class="admin-topbar">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="page-title"><?= e($page_title ?? 'Admin') ?></h2>
        <div class="topbar-right">
            <span class="admin-user">
                <i class="fas fa-user-circle"></i>
                <?= e(current_admin()['name']) ?>
            </span>
        </div>
    </header>

    <main class="admin-content">