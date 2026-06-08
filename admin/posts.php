<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message      = '';
$message_type = 'success';
$edit_post    = null;

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Bejegyzés törölve!';
    }
}

// ── STÁTUSZ TOGGLE ──
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    if (csrf_verify()) {
        $pdo->prepare("UPDATE posts SET status=IF(status='published','draft','published'), published_at=IF(status='draft',NOW(),published_at) WHERE id=?")
            ->execute([$_GET['toggle']]);
        $message = 'Státusz frissítve!';
    }
}

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id        = (int)($_POST['id'] ?? 0);
    $title     = trim($_POST['title']   ?? '');
    $slug      = trim($_POST['slug']    ?? '');
    $excerpt   = trim($_POST['excerpt'] ?? '');
    $content   = $_POST['content']      ?? '';
    $meta_t    = trim($_POST['meta_title']       ?? '');
    $meta_d    = trim($_POST['meta_description'] ?? '');
    $status    = $_POST['status']  ?? 'draft';
    $author_id = current_admin()['id'];
    $feat_img  = '';

    // Meglévő kép megtartása
    if ($id > 0) {
        $existing = $pdo->prepare("SELECT featured_image FROM posts WHERE id=?");
        $existing->execute([$id]);
        $feat_img = $existing->fetchColumn() ?? '';
    }

    // Kép eltávolítás
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if ($feat_img && file_exists(UPLOAD_PATH . $feat_img)) {
            unlink(UPLOAD_PATH . $feat_img);
        }
        $feat_img = '';
    }

    // Kiemelt kép feltöltés
    if (!empty($_FILES['featured_image']['name'])) {
        $upload_dir = UPLOAD_PATH;
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed) && $_FILES['featured_image']['size'] < 3145728) {
            $filename = 'post_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $filename)) {
                $feat_img = $filename;
            }
        }
    }

    if (!$slug && $title) $slug = generate_slug($title);

    if ($title && $slug) {
        $pub_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;

        if ($id > 0) {
            $pdo->prepare("UPDATE posts SET title=?,slug=?,excerpt=?,content=?,meta_title=?,meta_description=?,status=?,featured_image=?,published_at=COALESCE(published_at,?) WHERE id=?")
                ->execute([$title,$slug,$excerpt,$content,$meta_t,$meta_d,$status,$feat_img,$pub_at,$id]);
            $message = 'Bejegyzés frissítve!';
        } else {
            try {
                $pdo->prepare("INSERT INTO posts (title,slug,excerpt,content,meta_title,meta_description,status,featured_image,author_id,published_at) VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$title,$slug,$excerpt,$content,$meta_t,$meta_d,$status,$feat_img,$author_id,$pub_at]);
                $id = (int)$pdo->lastInsertId();
                $message = 'Bejegyzés létrehozva!';
            } catch (PDOException $e) {
                $message      = 'Hiba: Ez a slug már foglalt!';
                $message_type = 'error';
            }
        }

        // ── Kategóriák mentése ──
        if ($id > 0 && !($message_type === 'error')) {
            $pdo->prepare("DELETE FROM post_category_pivot WHERE post_id=?")->execute([$id]);
            $cats = $_POST['categories'] ?? [];
            foreach ($cats as $cat_id) {
                $pdo->prepare("INSERT IGNORE INTO post_category_pivot (post_id,category_id) VALUES (?,?)")
                    ->execute([$id, (int)$cat_id]);
            }
        }

        // ── Címkék mentése ──
        if ($id > 0 && !($message_type === 'error')) {
            $pdo->prepare("DELETE FROM post_tag_pivot WHERE post_id=?")->execute([$id]);
            $tags_raw = trim($_POST['tags_input'] ?? '');
            if ($tags_raw) {
                $tag_names = array_unique(array_map('trim', explode(',', $tags_raw)));
                foreach ($tag_names as $tag_name) {
                    if (!$tag_name) continue;
                    $tag_slug = generate_slug($tag_name);
                    // Létrehozás ha nem létezik
                    $pdo->prepare("INSERT IGNORE INTO post_tags (name, slug) VALUES (?,?)")
                        ->execute([$tag_name, $tag_slug]);
                    $tag_id = $pdo->query("SELECT id FROM post_tags WHERE slug='$tag_slug' LIMIT 1")->fetchColumn();
                    if ($tag_id) {
                        $pdo->prepare("INSERT IGNORE INTO post_tag_pivot (post_id,tag_id) VALUES (?,?)")
                            ->execute([$id, (int)$tag_id]);
                    }
                }
            }
        }

    } else {
        $message      = 'A cím kötelező!';
        $message_type = 'error';
    }
}

// ── SZERKESZTÉS ──
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_post = $stmt->fetch();
}

// Kategória és tag lista
$all_categories = $pdo->query("SELECT * FROM post_categories ORDER BY sort_order ASC")->fetchAll();
$all_tags       = $pdo->query("SELECT * FROM post_tags ORDER BY name ASC")->fetchAll();

// Aktuális post kategóriái és tagjai
$post_category_ids = [];
$post_tags_str     = '';
if ($edit_post) {
    $stmt = $pdo->prepare("SELECT category_id FROM post_category_pivot WHERE post_id=?");
    $stmt->execute([$edit_post['id']]);
    $post_category_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT t.name FROM post_tags t JOIN post_tag_pivot p ON p.tag_id=t.id WHERE p.post_id=? ORDER BY t.name");
    $stmt->execute([$edit_post['id']]);
    $post_tags_str = implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

// Lista – szűrők
$filter_cat    = (int)($_GET['cat']    ?? 0);
$filter_status = $_GET['status']       ?? '';
$search        = trim($_GET['search']  ?? '');

$where  = ['1=1'];
$params = [];
if ($filter_cat) {
    $where[]  = "p.id IN (SELECT post_id FROM post_category_pivot WHERE category_id=?)";
    $params[] = $filter_cat;
}
if ($filter_status) {
    $where[]  = "p.status = ?";
    $params[] = $filter_status;
}
if ($search) {
    $where[]  = "(p.title LIKE ? OR p.excerpt LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT p.*, u.username as author_name
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$page_title = $edit_post ? 'Bejegyzés szerkesztése' : 'Blog';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<?php if ($edit_post || isset($_GET['new'])): ?>
<!-- ══ SZERKESZTŐ NÉZET ══ -->
<div class="page-header">
    <h2><i class="fas fa-<?= $edit_post ? 'edit' : 'plus' ?>"></i>
        <?= $edit_post ? 'Bejegyzés szerkesztése' : 'Új bejegyzés' ?>
    </h2>
    <a href="posts.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Vissza</a>
</div>

<form method="POST" action="posts.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token"  value="<?= csrf_token() ?>">
    <input type="hidden" name="save_post"   value="1">
    <input type="hidden" name="id"          value="<?= $edit_post['id'] ?? 0 ?>">

    <div class="editor-layout">
        <!-- Fő tartalom -->
        <div class="editor-main">
            <div class="card">
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Bejegyzés címe *</label>
                    <input type="text" name="title"
                           value="<?= e($edit_post['title'] ?? '') ?>"
                           placeholder="Bejegyzés neve" required
                           oninput="autoSlug(this.value)">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-link"></i> Slug</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span style="color:var(--text-muted);font-size:13px;"><?= BASE_URL ?>/blog/</span>
                        <input type="text" name="slug" id="pageSlug"
                               value="<?= e($edit_post['slug'] ?? '') ?>"
                               placeholder="bejegyzes-neve" style="flex:1;">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-quote-left"></i> Kivonat (excerpt)</label>
                    <textarea name="excerpt" rows="3"
                              placeholder="Rövid összefoglaló (listában jelenik meg)..."><?= e($edit_post['excerpt'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Tartalom</label>
                    <textarea name="content" id="postContent" rows="25"><?= e($edit_post['content'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Oldalpanel -->
        <div class="editor-sidebar">

            <!-- Publikálás -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-paper-plane"></i> Publikálás</h3></div>
                <div class="form-group">
                    <select name="status">
                        <option value="draft"     <?= ($edit_post['status'] ?? '')      === 'draft'     ? 'selected' : '' ?>>📝 Piszkozat</option>
                        <option value="published" <?= ($edit_post['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>✅ Publikus</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">
                        <i class="fas fa-save"></i> Mentés
                    </button>
                    <?php if ($edit_post && $edit_post['status'] === 'published'): ?>
                    <a href="<?= BASE_URL ?>/blog/<?= e($edit_post['slug']) ?>"
                       target="_blank" class="btn btn-secondary" title="Előnézet">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php if ($edit_post && $edit_post['published_at']): ?>
                <p style="font-size:12px;color:var(--text-muted);margin-top:10px;">
                    <i class="fas fa-clock"></i>
                    Publikálva: <?= date('Y. m. d. H:i', strtotime($edit_post['published_at'])) ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Kategóriák -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-folder"></i> Kategóriák</h3>
                    <button type="button" class="btn-sm" onclick="openCatModal()">
                        <i class="fas fa-plus"></i> Új
                    </button>
                </div>
                <?php if ($all_categories): ?>
                <div class="category-checklist">
                    <?php foreach ($all_categories as $cat): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="categories[]"
                               value="<?= $cat['id'] ?>"
                               <?= in_array($cat['id'], $post_category_ids) ? 'checked' : '' ?>>
                        <?= e($cat['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="font-size:13px;color:var(--text-muted);">
                    Még nincs kategória.
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openCatModal()" style="margin-top:8px;width:100%;">
                        <i class="fas fa-plus"></i> Kategória létrehozása
                    </button>
                </p>
                <?php endif; ?>
            </div>

            <!-- Címkék -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-tags"></i> Címkék</h3></div>
                <div class="form-group">
                    <input type="text" name="tags_input" id="tagsInput"
                           value="<?= e($post_tags_str) ?>"
                           placeholder="pl. hajvágás, kozmetikus, tipp">
                    <small style="color:var(--text-muted);">Vesszővel elválasztva. Új címke automatikusan létrejön.</small>
                </div>
                <!-- Meglévő tag-ek gyors hozzáadás -->
                <?php if ($all_tags): ?>
                <div class="tags-cloud">
                    <?php foreach ($all_tags as $tag): ?>
                    <button type="button" class="tag-chip"
                            onclick="addTag('<?= e($tag['name']) ?>')">
                        <?= e($tag['name']) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Kiemelt kép -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-image"></i> Kiemelt kép</h3></div>
                <?php if (!empty($edit_post['featured_image'])): ?>
                <div style="margin-bottom:12px;">
                    <img src="<?= UPLOAD_URL . e($edit_post['featured_image']) ?>"
                         style="width:100%;border-radius:8px;object-fit:cover;max-height:160px;" alt="">
                    <label class="checkbox-label" style="margin-top:8px;">
                        <input type="checkbox" name="remove_image" value="1">
                        Kép eltávolítása
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp">
                <small style="color:var(--text-muted);display:block;margin-top:6px;">Max 3MB – JPG, PNG, WEBP</small>
            </div>

            <!-- SEO -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-search"></i> SEO</h3></div>
                <div class="form-group">
                    <label>Meta title</label>
                    <input type="text" name="meta_title" id="metaTitleInput"
                           value="<?= e($edit_post['meta_title'] ?? '') ?>"
                           placeholder="SEO cím (max 60 kar.)">
                    <small id="metaTitleCount" style="color:var(--text-muted);">0/60</small>
                </div>
                <div class="form-group">
                    <label>Meta description</label>
                    <textarea name="meta_description" id="metaDescInput"
                              rows="3" placeholder="SEO leírás (max 160 kar.)"><?= e($edit_post['meta_description'] ?? '') ?></textarea>
                    <small id="metaDescCount" style="color:var(--text-muted);">0/160</small>
                </div>
            </div>

            <!-- Szerző -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-user"></i> Szerző</h3></div>
                <p style="font-size:13px;color:var(--text-light);">
                    <i class="fas fa-user-circle" style="color:var(--accent);"></i>
                    <?= e(current_admin()['name']) ?>
                </p>
            </div>
        </div>
    </div>
</form>

<!-- ── MODAL: Új kategória ── -->
<div class="modal-overlay" id="catModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <h3><i class="fas fa-folder-plus"></i> Új kategória</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="post_categories.php">
            <input type="hidden" name="csrf_token"  value="<?= csrf_token() ?>">
            <input type="hidden" name="save_cat"    value="1">
            <input type="hidden" name="redirect_to" value="posts.php?<?= isset($_GET['edit']) ? 'edit='.$edit_post['id'] : 'new=1' ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label>Kategória neve *</label>
                    <input type="text" name="cat_name" placeholder="pl. Tippek és trükkök" required>
                </div>
                <div class="form-group">
                    <label>Leírás <small style="color:var(--text-muted);">(opcionális)</small></label>
                    <textarea name="cat_description" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Mégse</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Létrehozás
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/t1ps7dpln7ehd64mx2dgfrzteiq1mfhgv8o3bbpx2wijxxlm/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#postContent',
    language: 'hu_HU',
    height: 550,
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code',
    promotion: false,
    branding: false,
    content_style: 'body { font-family: Segoe UI, sans-serif; font-size: 15px; padding: 16px; }'
});

function autoSlug(val) {
    const slug = val.toLowerCase()
        .replace(/[áä]/g,'a').replace(/[éë]/g,'e').replace(/[íï]/g,'i')
        .replace(/[óöő]/g,'o').replace(/[úüű]/g,'u')
        .replace(/[^a-z0-9\s-]/g,'').replace(/[\s]+/g,'-').trim();
    const el = document.getElementById('pageSlug');
    if (!el.dataset.manual) el.value = slug;
}
document.getElementById('pageSlug')?.addEventListener('input', function() {
    this.dataset.manual = true;
});

// SEO számlálók
function updateCount(inputId, countId, max) {
    const input = document.getElementById(inputId);
    const count = document.getElementById(countId);
    if (!input || !count) return;
    const update = () => {
        const len = input.value.length;
        count.textContent = `${len}/${max}`;
        count.style.color = len > max ? 'var(--red)' : len > max * .85 ? 'var(--orange)' : 'var(--text-muted)';
    };
    input.addEventListener('input', update);
    update();
}
updateCount('metaTitleInput', 'metaTitleCount', 60);
updateCount('metaDescInput',  'metaDescCount',  160);

// Tag hozzáadás kattintásra
function addTag(name) {
    const input = document.getElementById('tagsInput');
    const current = input.value.split(',').map(t => t.trim()).filter(t => t);
    if (!current.includes(name)) {
        current.push(name);
        input.value = current.join(', ');
    }
}

function openCatModal() {
    document.getElementById('catModal').classList.add('open');
}
</script>

<?php else: ?>
<!-- ══ LISTA NÉZET ══ -->
<div class="page-header">
    <h2><i class="fas fa-blog"></i> Blog bejegyzések</h2>
    <div style="display:flex;gap:8px;">
        <a href="post_categories.php" class="btn btn-secondary">
            <i class="fas fa-folder"></i> Kategóriák
        </a>
        <a href="posts.php?new=1" class="btn btn-primary">
            <i class="fas fa-plus"></i> Új bejegyzés
        </a>
    </div>
</div>

<!-- Szűrők -->
<div class="card" style="margin-bottom:16px;">
    <form method="GET" action="posts.php"
          style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="search" value="<?= e($search) ?>"
               placeholder="🔍 Keresés..." style="max-width:240px;">
        <select name="cat" style="max-width:180px;">
            <option value="">Minden kategória</option>
            <?php foreach ($all_categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $filter_cat == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="status" style="max-width:160px;">
            <option value="">Minden státusz</option>
            <option value="published" <?= $filter_status === 'published' ? 'selected' : '' ?>>✅ Publikus</option>
            <option value="draft"     <?= $filter_status === 'draft'     ? 'selected' : '' ?>>📝 Piszkozat</option>
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Szűrés</button>
        <a href="posts.php" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
    </form>
</div>

<div class="card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Kép</th>
                <th>Cím</th>
                <th>Kategóriák</th>
                <th>Címkék</th>
                <th>Szerző</th>
                <th>Státusz</th>
                <th>Publikálva</th>
                <th>Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($posts): ?>
            <?php foreach ($posts as $p): ?>
            <?php
            // Kategóriák lekérése
            $p_cats = $pdo->prepare("SELECT c.name FROM post_categories c JOIN post_category_pivot pv ON pv.category_id=c.id WHERE pv.post_id=?");
            $p_cats->execute([$p['id']]);
            $p_cats = $p_cats->fetchAll(PDO::FETCH_COLUMN);

            // Tagek lekérése
            $p_tags = $pdo->prepare("SELECT t.name FROM post_tags t JOIN post_tag_pivot pv ON pv.tag_id=t.id WHERE pv.post_id=?");
            $p_tags->execute([$p['id']]);
            $p_tags = $p_tags->fetchAll(PDO::FETCH_COLUMN);
            ?>
            <tr>
                <td>
                    <?php if ($p['featured_image']): ?>
                        <img src="<?= UPLOAD_URL . e($p['featured_image']) ?>"
                             style="width:60px;height:45px;object-fit:cover;border-radius:6px;" alt="">
                    <?php else: ?>
                        <div style="width:60px;height:45px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#ccc;">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= e($p['title']) ?></strong>
                    <br><small style="color:#999;">/blog/<?= e($p['slug']) ?></small>
                </td>
                <td>
                    <?php foreach ($p_cats as $cn): ?>
                        <span class="tag-badge cat-badge"><?= e($cn) ?></span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php foreach ($p_tags as $tn): ?>
                        <span class="tag-badge"><?= e($tn) ?></span>
                    <?php endforeach; ?>
                </td>
                <td><?= e($p['author_name'] ?? 'N/A') ?></td>
                <td>
                    <a href="posts.php?toggle=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                       class="badge <?= $p['status']==='published' ? 'badge-published' : 'badge-draft' ?>"
                       style="cursor:pointer;text-decoration:none;">
                        <?= $p['status']==='published' ? 'Publikus' : 'Piszkozat' ?>
                    </a>
                </td>
                <td style="font-size:12px;color:var(--text-muted);">
                    <?= $p['published_at'] ? date('Y.m.d', strtotime($p['published_at'])) : '–' ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="posts.php?edit=<?= $p['id'] ?>"
                           class="action-btn edit" title="Szerkesztés">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($p['status']==='published'): ?>
                        <a href="<?= BASE_URL ?>/blog/<?= e($p['slug']) ?>"
                           target="_blank" class="action-btn view" title="Megtekintés">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php endif; ?>
                        <a href="posts.php?delete=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="action-btn delete" title="Törlés"
                           data-confirm="Biztosan törlöd ezt a bejegyzést?">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <i class="fas fa-blog"></i>
                        <p>Még nincs bejegyzés.</p>
                        <a href="posts.php?new=1" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Első bejegyzés létrehozása
                        </a>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
.editor-layout { display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; }
@media(max-width:900px) { .editor-layout { grid-template-columns:1fr; } }
.category-checklist { display:flex; flex-direction:column; gap:8px; max-height:200px; overflow-y:auto; }
.tags-cloud { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
.tag-chip { padding:4px 10px; background:#f0f0f0; border:1px solid var(--border); border-radius:20px; font-size:12px; cursor:pointer; transition:all .2s; }
.tag-chip:hover { background:var(--accent); color:#fff; border-color:var(--accent); }
.tag-badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:11px; background:#f0f0f0; color:#555; margin:1px; }
.cat-badge { background:rgba(233,69,96,.1); color:var(--accent); }
</style>

<?php require_once 'partials/footer.php'; ?>