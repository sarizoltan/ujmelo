<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/schema.php';

// Beállítások
$site_name    = get_setting('site_name', 'Kozmetikai Szalon');
$site_phone   = get_setting('site_phone', '');
$site_email   = get_setting('site_email', '');
$site_address = get_setting('site_address', '');
$site_logo    = get_setting('site_logo', '');
$facebook_url = get_setting('facebook_url', '');
$instagram_url= get_setting('instagram_url', '');
$meta_desc    = get_setting('meta_description', '');
$favicon      = get_setting('site_favicon', '');

// Fejléc menü lekérése
$header_menu = [];
$menu_row = $pdo->query("SELECT id FROM menus WHERE location='header' LIMIT 1")->fetch();
if ($menu_row) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id=? AND parent_id=0 ORDER BY sort_order ASC");
    $stmt->execute([$menu_row['id']]);
    $header_menu = $stmt->fetchAll();
}

// Aktuális URL
$current_url = BASE_URL . '/' . trim($_SERVER['REQUEST_URI'], '/');
$current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

$page_meta_title = $page_meta_title ?? $site_name;
$page_meta_desc  = $page_meta_desc  ?? $meta_desc;
$page_schema     = $page_schema     ?? '';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_meta_title) ?></title>
    <meta name="description" content="<?= e($page_meta_desc) ?>">
    <meta property="og:title" content="<?= e($page_meta_title) ?>">
    <meta property="og:description" content="<?= e($page_meta_desc) ?>">
    <meta property="og:url" content="<?= e($current_url) ?>">
    <meta property="og:type" content="website">
    <?php if ($favicon): ?>
    <link rel="icon" href="<?= UPLOAD_URL . e($favicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?= schema_local_business() ?>
    <?= $page_schema ?>
</head>
<body>

<!-- Top bar -->
<div class="topbar">
    <div class="container">
        <div class="topbar-left">
            <?php if ($site_address): ?>
            <span><i class="fas fa-map-marker-alt"></i> <?= e($site_address) ?></span>
            <?php endif; ?>
            <?php if ($site_phone): ?>
            <a href="tel:<?= e($site_phone) ?>"><i class="fas fa-phone"></i> <?= e($site_phone) ?></a>
            <?php endif; ?>
            <?php if ($site_email): ?>
            <a href="mailto:<?= e($site_email) ?>"><i class="fas fa-envelope"></i> <?= e($site_email) ?></a>
            <?php endif; ?>
        </div>
        <div class="topbar-right">
            <?php if ($facebook_url): ?>
            <a href="<?= e($facebook_url) ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>
            <?php if ($instagram_url): ?>
            <a href="<?= e($instagram_url) ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Navigáció -->
<header class="site-header" id="siteHeader">
    <div class="container">
        <div class="header-inner">
            <!-- Logo -->
            <a href="<?= BASE_URL ?>/" class="site-logo">
                <?php if ($site_logo): ?>
                    <img src="<?= UPLOAD_URL . e($site_logo) ?>" alt="<?= e($site_name) ?>">
                <?php else: ?>
                    <span class="logo-icon"><i class="fas fa-hand-sparkles"></i></span>
                    <span class="logo-text"><?= e($site_name) ?></span>
                <?php endif; ?>
            </a>

            <!-- Navigáció -->
            <nav class="main-nav" id="mainNav">
                <ul>
                    <?php foreach ($header_menu as $item): ?>
                    <?php
                    $item_path = trim(parse_url($item['url'], PHP_URL_PATH), '/');
                    $is_active = ($current_path === $item_path) ||
                                 ($item_path === '' && $current_path === '');
                    $is_booking = str_contains($item['url'], 'foglalas');
                    ?>
                    <li>
                        <a href="<?= e($item['url']) ?>"
                           class="<?= $is_active ? 'active' : '' ?> <?= $is_booking ? 'nav-booking-btn' : '' ?>"
                           <?= $item['target'] === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                            <?= e($item['label']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Mobil toggle -->
            <button class="nav-toggle" id="navToggle" aria-label="Menü">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>