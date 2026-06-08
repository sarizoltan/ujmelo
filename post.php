<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/schema.php';

$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($slug)));

if (!$slug) {
    header('Location: ' . BASE_URL . '/blog');
    exit;
}

// Bejegyzés lekérése
$stmt = $pdo->prepare("SELECT p.*, u.username as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.slug=? AND p.status='published' LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    header('Location: ' . BASE_URL . '/blog');
    exit;
}

// Előző / következő bejegyzés
$prev_post = $pdo->prepare("SELECT title,slug FROM posts WHERE status='published' AND published_at < ? ORDER BY published_at DESC LIMIT 1");
$prev_post->execute([$post['published_at']]);
$prev_post = $prev_post->fetch();

$next_post = $pdo->prepare("SELECT title,slug FROM posts WHERE status='published' AND published_at > ? ORDER BY published_at ASC LIMIT 1");
$next_post->execute([$post['published_at']]);
$next_post = $next_post->fetch();

// Kapcsolódó bejegyzések
$related = $pdo->prepare("SELECT * FROM posts WHERE status='published' AND id != ? ORDER BY published_at DESC LIMIT 3");
$related->execute([$post['id']]);
$related = $related->fetchAll();

$page_meta_title = $post['meta_title'] ?: ($post['title'] . ' – ' . get_setting('site_name'));
$page_meta_desc  = $post['meta_description'] ?: $post['excerpt'];
$page_schema     = schema_blog_post($post);

require_once 'templates/header.php';
?>

<script>window._BASE_URL = '<?= BASE_URL ?>';</script>

<!-- Bejegyzés hero -->
<div class="post-hero" <?= $post['featured_image'] ? 'style="background-image:url(\''.UPLOAD_URL.e($post['featured_image']).'\')"' : '' ?>>
    <div class="post-hero-overlay"></div>
    <div class="container">
        <div class="post-hero-content">
            <div class="blog-date" style="justify-content:center;margin-bottom:16px;">
                <i class="fas fa-calendar"></i>
                <?= $post['published_at'] ? date('Y. m. d.', strtotime($post['published_at'])) : '' ?>
                <?php if ($post['author_name']): ?>
                    <span><i class="fas fa-user"></i> <?= e($post['author_name']) ?></span>
                <?php endif; ?>
            </div>
            <h1><?= e($post['title']) ?></h1>
            <?php if ($post['excerpt']): ?>
                <p><?= e($post['excerpt']) ?></p>
            <?php endif; ?>
            <div class="page-breadcrumb" style="justify-content:center;margin-top:16px;">
                <a href="<?= BASE_URL ?>/">Főoldal</a>
                <i class="fas fa-chevron-right"></i>
                <a href="<?= BASE_URL ?>/blog">Blog</a>
                <i class="fas fa-chevron-right"></i>
                <span><?= e(mb_substr($post['title'],0,40)) ?>...</span>
            </div>
        </div>
    </div>
</div>

<section class="section section-dark">
    <div class="container">
        <div class="post-layout">

            <!-- Cikk tartalom -->
            <article class="post-content page-content">
                <?= $post['content'] ?>

                <!-- Megosztás -->
                <div class="post-share">
                    <span><i class="fas fa-share-alt"></i> Megosztás:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL.'/blog/'.e($post['slug'])) ?>"
                       target="_blank" class="share-btn facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode(BASE_URL.'/blog/'.e($post['slug'])) ?>&text=<?= urlencode($post['title']) ?>"
                       target="_blank" class="share-btn twitter">
                        <i class="fab fa-x-twitter"></i> Twitter
                    </a>
                </div>

                <!-- Előző / következő -->
                <div class="post-nav">
                    <?php if ($prev_post): ?>
                    <a href="<?= BASE_URL ?>/blog/<?= e($prev_post['slug']) ?>" class="post-nav-btn">
                        <i class="fas fa-arrow-left"></i>
                        <div>
                            <small>Előző bejegyzés</small>
                            <span><?= e(mb_substr($prev_post['title'],0,50)) ?>...</span>
                        </div>
                    </a>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <?php if ($next_post): ?>
                    <a href="<?= BASE_URL ?>/blog/<?= e($next_post['slug']) ?>" class="post-nav-btn next">
                        <div style="text-align:right;">
                            <small>Következő bejegyzés</small>
                            <span><?= e(mb_substr($next_post['title'],0,50)) ?>...</span>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                </div>
            </article>

            <!-- Oldalsáv -->
            <aside class="post-sidebar">
                <!-- Szerző -->
                <div class="sidebar-card">
                    <div class="sidebar-author">
                        <div class="author-avatar"><i class="fas fa-user-tie"></i></div>
                        <div>
                            <strong><?= e($post['author_name'] ?? get_setting('site_name')) ?></strong>
                            <span><?= e(get_setting('site_name')) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Foglalás CTA -->
                <div class="sidebar-card sidebar-booking-cta">
                    <i class="fas fa-cut"></i>
                    <h4>Foglalj időpontot!</h4>
                    <p>Próbáld ki te is prémium szolgáltatásainkat.</p>
                    <a href="<?= BASE_URL ?>/foglalas" class="btn btn-gold" style="width:100%;justify-content:center;margin-top:12px;">
                        <i class="fas fa-calendar-check"></i> Foglalás
                    </a>
                </div>

                <!-- Kapcsolódó -->
                <?php if ($related): ?>
                <div class="sidebar-card">
                    <h4 class="sidebar-title">Kapcsolódó bejegyzések</h4>
                    <?php foreach ($related as $r): ?>
                    <div class="related-post">
                        <?php if ($r['featured_image']): ?>
                            <img src="<?= UPLOAD_URL . e($r['featured_image']) ?>"
                                 alt="<?= e($r['title']) ?>">
                        <?php else: ?>
                            <div class="related-post-placeholder"><i class="fas fa-blog"></i></div>
                        <?php endif; ?>
                        <div>
                            <a href="<?= BASE_URL ?>/blog/<?= e($r['slug']) ?>">
                                <?= e(mb_substr($r['title'],0,55)) ?>...
                            </a>
                            <small><?= $r['published_at'] ? date('Y.m.d', strtotime($r['published_at'])) : '' ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<style>
.post-hero { position:relative; padding:100px 0 60px; background:var(--dark-2); background-size:cover; background-position:center; }
.post-hero-overlay { position:absolute; inset:0; background:rgba(10,10,10,.8); }
.post-hero-content { position:relative; z-index:2; text-align:center; max-width:760px; margin:0 auto; }
.post-hero-content h1 { font-family:var(--font-serif); font-size:clamp(28px,4vw,48px); color:var(--white); margin-top:12px; line-height:1.3; }
.post-hero-content p { color:var(--text-muted); font-size:16px; margin-top:12px; }
.post-layout { display:grid; grid-template-columns:1fr 300px; gap:48px; align-items:start; }
@media(max-width:900px) { .post-layout { grid-template-columns:1fr; } }
.post-share { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:20px 0; border-top:1px solid var(--border); border-bottom:1px solid var(--border); margin:32px 0; }
.post-share span { color:var(--text-muted); font-size:14px; }
.share-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:4px; font-size:13px; font-weight:600; transition:all var(--transition); }
.share-btn.facebook { background:#1877f2; color:#fff; }
.share-btn.twitter { background:#000; color:#fff; }
.share-btn:hover { opacity:.85; transform:translateY(-1px); }
.post-nav { display:flex; justify-content:space-between; gap:16px; margin-top:32px; }
.post-nav-btn { display:flex; align-items:center; gap:12px; padding:16px; background:var(--dark-3); border:1px solid var(--border); border-radius:var(--radius); text-decoration:none; color:var(--text); flex:1; transition:all var(--transition); max-width:48%; }
.post-nav-btn:hover { border-color:var(--gold); color:var(--gold); }
.post-nav-btn.next { justify-content:flex-end; }
.post-nav-btn i { color:var(--gold); flex-shrink:0; }
.post-nav-btn small { display:block; font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; }
.post-nav-btn span { display:block; font-size:13px; font-weight:600; }
.sidebar-card { background:var(--dark-3); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px; margin-bottom:20px; }
.sidebar-author { display:flex; align-items:center; gap:12px; }
.author-avatar { width:48px; height:48px; border-radius:50%; background:rgba(200,169,110,.1); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:20px; flex-shrink:0; }
.sidebar-author strong { display:block; color:var(--white); font-size:15px; }
.sidebar-author span { color:var(--text-muted); font-size:13px; }
.sidebar-booking-cta { text-align:center; }
.sidebar-booking-cta i { font-size:32px; color:var(--gold); margin-bottom:12px; display:block; }
.sidebar-booking-cta h4 { font-family:var(--font-serif); color:var(--white); font-size:18px; margin-bottom:8px; }
.sidebar-booking-cta p { color:var(--text-muted); font-size:13px; }
.sidebar-title { font-family:var(--font-serif); color:var(--white); font-size:16px; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border); }
.related-post { display:flex; gap:12px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px solid var(--border); }
.related-post:last-child { margin-bottom:0; padding-bottom:0; border-bottom:none; }
.related-post img { width:60px; height:50px; object-fit:cover; border-radius:6px; flex-shrink:0; }
.related-post-placeholder { width:60px; height:50px; background:var(--dark-4); border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--text-muted); flex-shrink:0; }
.related-post a { color:var(--text); font-size:13px; font-weight:500; line-height:1.4; display:block; margin-bottom:4px; }
.related-post a:hover { color:var(--gold); }
.related-post small { color:var(--text-muted); font-size:11px; }
</style>

<?php require_once 'templates/footer.php'; ?>