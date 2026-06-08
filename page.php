<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/schema.php';
require_once 'includes/shortcodes.php';

// Slug meghatározása
$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($slug)));

if (!$slug) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

// Oldal lekérése
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug=? AND status='published' LIMIT 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    $page_meta_title = '404 – Oldal nem található';
    $page_meta_desc  = '';
    require_once 'templates/header.php';
    ?>
    <div class="page-hero">
        <div class="container">
            <h1>404 – Oldal nem található</h1>
        </div>
    </div>
    <section class="section section-dark" style="text-align:center;">
        <div class="container">
            <p style="color:var(--text-muted);font-size:18px;margin-bottom:32px;">
                A keresett oldal nem létezik vagy el lett távolítva.
            </p>
            <a href="<?= BASE_URL ?>/" class="btn btn-gold">
                <i class="fas fa-home"></i> Vissza a főoldalra
            </a>
        </div>
    </section>
    <?php
    require_once 'templates/footer.php';
    exit;
}

$page_meta_title = $page['meta_title'] ?: ($page['title'] . ' – ' . get_setting('site_name'));
$page_meta_desc  = $page['meta_description'] ?: get_setting('meta_description');
$page_schema = schema_webpage(
    $page['meta_title'] ?: ($page['title'] . ' – ' . get_setting('site_name')),
    $page['meta_description'] ?: get_setting('meta_description'),
    BASE_URL . '/' . $page['slug']
);

require_once 'templates/header.php';
?>

<script>window._BASE_URL = '<?= BASE_URL ?>';</script>

<!-- Oldal hero -->
<div class="page-hero">
    <div class="container">
        <h1><?= e($page['title']) ?></h1>
        <div class="page-breadcrumb">
            <a href="<?= BASE_URL ?>/">Főoldal</a>
            <i class="fas fa-chevron-right"></i>
            <span><?= e($page['title']) ?></span>
        </div>
    </div>
</div>

<!-- Tartalom -->
<section class="section section-dark">
    <div class="container">
        <div class="page-content">
    <?= process_shortcodes($page['content']) ?>
</div>
    </div>
</section>

<style>
.page-content { max-width: 820px; margin: 0 auto; }
.page-content h1,.page-content h2,.page-content h3 { font-family:var(--font-serif); color:var(--white); margin:32px 0 16px; }
.page-content h2 { font-size:28px; border-bottom:1px solid var(--border); padding-bottom:12px; }
.page-content h3 { font-size:22px; color:var(--gold); }
.page-content p { color:var(--text); line-height:1.8; margin-bottom:16px; }
.page-content a { color:#201f1d; }
.page-content a:hover { text-decoration:underline; }
.page-content ul,.page-content ol { color:var(--text); padding-left:24px; margin-bottom:16px; line-height:1.8; }
.page-content li { margin-bottom:6px; }
.page-content blockquote { border-left:3px solid var(--gold); padding:16px 20px; background:var(--dark-3); border-radius:0 var(--radius) var(--radius) 0; margin:24px 0; color:var(--text-muted); font-style:italic; }
.page-content img { border-radius:var(--radius-lg); margin:24px 0; max-width:100%; }
.page-content table { width:100%; border-collapse:collapse; margin:24px 0; }
.page-content th { background:var(--dark-3); color:var(--gold); padding:12px 16px; text-align:left; font-size:13px; text-transform:uppercase; letter-spacing:.5px; }
.page-content td { padding:12px 16px; border-bottom:1px solid var(--border); color:var(--text); font-size:14px; }
.page-content tr:hover td { background:var(--dark-3); }
.page-content code { background:var(--dark-3); color:var(--gold); padding:2px 6px; border-radius:4px; font-size:13px; }
.page-content pre { background:var(--dark-3); border:1px solid var(--border); border-radius:var(--radius); padding:20px; overflow-x:auto; margin:20px 0; }
</style>

<?php require_once 'templates/footer.php'; ?>

