<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_meta_title = 'Blog – ' . get_setting('site_name');
$page_meta_desc  = 'Olvass műkörmös tippeket, stílus tanácsokat és újdonságokat szalonunktól.';

// Lapozás
$per_page    = 9;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($current_page - 1) * $per_page;

// Összes bejegyzés száma
$total = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$total_pages = ceil($total / $per_page);

// Bejegyzések
$stmt = $pdo->prepare("SELECT p.*, u.username as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.status='published' 
    ORDER BY p.published_at DESC 
    LIMIT ? OFFSET ?");
$stmt->execute([$per_page, $offset]);
$posts = $stmt->fetchAll();

require_once 'templates/header.php';
?>

<script>window._BASE_URL = '<?= BASE_URL ?>';</script>

<div class="page-hero">
    <div class="container">
        <h1>Blog</h1>
        <div class="page-breadcrumb">
            <a href="<?= BASE_URL ?>/">Főoldal</a>
            <i class="fas fa-chevron-right"></i>
            <span>Blog</span>
        </div>
    </div>
</div>

<section class="section section-dark">
    <div class="container">
        <?php if ($posts): ?>
        <div class="blog-grid">
            <?php foreach ($posts as $post): ?>
            <article class="blog-card reveal">
                <div class="blog-thumb">
                    <?php if ($post['featured_image']): ?>
                        <img src="<?= UPLOAD_URL . e($post['featured_image']) ?>"
                             alt="<?= e($post['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="blog-thumb-placeholder">
                            <i class="fas fa-blog"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="blog-body">
                    <div class="blog-date">
                        <i class="fas fa-calendar"></i>
                        <?= $post['published_at'] ? date('Y. m. d.', strtotime($post['published_at'])) : '' ?>
                        <?php if ($post['author_name']): ?>
                            <span style="margin-left:8px;"><i class="fas fa-user"></i> <?= e($post['author_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <h3>
                        <a href="<?= BASE_URL ?>/blog/<?= e($post['slug']) ?>">
                            <?= e($post['title']) ?>
                        </a>
                    </h3>
                    <?php if ($post['excerpt']): ?>
                        <p class="blog-excerpt"><?= e(mb_substr($post['excerpt'], 0, 140)) ?>...</p>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/blog/<?= e($post['slug']) ?>" class="blog-read-more">
                        Tovább olvasom <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Lapozás -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
            <a href="<?= BASE_URL ?>/blog?page=<?= $current_page - 1 ?>" class="page-btn">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?= BASE_URL ?>/blog?page=<?= $i ?>"
               class="page-btn <?= $i === $current_page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
            <a href="<?= BASE_URL ?>/blog?page=<?= $current_page + 1 ?>" class="page-btn">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div style="text-align:center;padding:80px 20px;">
            <i class="fas fa-blog" style="font-size:64px;color:var(--border);display:block;margin-bottom:20px;"></i>
            <p style="color:var(--text-muted);font-size:18px;">Még nincsenek blog bejegyzések.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-outline" style="margin-top:20px;">
                <i class="fas fa-home"></i> Vissza a főoldalra
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.pagination { display:flex; justify-content:center; gap:8px; margin-top:48px; flex-wrap:wrap; }
.page-btn { width:42px; height:42px; border-radius:var(--radius); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:14px; font-weight:600; transition:all var(--transition); }
.page-btn:hover { border-color:var(--gold); color:var(--gold); }
.page-btn.active { background:var(--gold); border-color:var(--gold); color:var(--dark); }
</style>

<?php require_once 'templates/footer.php'; ?>