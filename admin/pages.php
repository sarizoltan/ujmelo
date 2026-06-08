<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message = '';
$message_type = 'success';
$edit_page = null;

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM pages WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Oldal törölve!';
    }
}

// ── STÁTUSZ TOGGLE ──
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    if (csrf_verify()) {
        $pdo->prepare("UPDATE pages SET status = IF(status='published','draft','published') WHERE id=?")
            ->execute([$_GET['toggle']]);
        $message = 'Státusz frissítve!';
    }
}

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id           = (int)($_POST['id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $slug         = trim($_POST['slug'] ?? '');
    $content      = $_POST['content'] ?? '';
    $meta_title   = trim($_POST['meta_title'] ?? '');
    $meta_desc    = trim($_POST['meta_description'] ?? '');
    $schema_type  = trim($_POST['schema_type'] ?? 'WebPage');
    $status       = $_POST['status'] ?? 'draft';
    $sort_order   = (int)($_POST['sort_order'] ?? 0);

    if (!$slug && $title) $slug = generate_slug($title);

    if ($title && $slug) {
        if ($id > 0) {
            $pdo->prepare("UPDATE pages SET title=?,slug=?,content=?,meta_title=?,meta_description=?,schema_type=?,status=?,sort_order=?,updated_at=NOW() WHERE id=?")
                ->execute([$title,$slug,$content,$meta_title,$meta_desc,$schema_type,$status,$sort_order,$id]);
            $message = 'Oldal frissítve!';
        } else {
            try {
                $pdo->prepare("INSERT INTO pages (title,slug,content,meta_title,meta_description,schema_type,status,sort_order) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$title,$slug,$content,$meta_title,$meta_desc,$schema_type,$status,$sort_order]);
                $message = 'Oldal létrehozva!';
            } catch (PDOException $e) {
                $message = 'Hiba: Ez a slug már foglalt!';
                $message_type = 'error';
            }
        }
    } else {
        $message = 'A cím és a slug kötelező!';
        $message_type = 'error';
    }
}

// ── SZERKESZTÉS BETÖLTÉSE ──
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_page = $stmt->fetch();
}

$pages = $pdo->query("SELECT * FROM pages ORDER BY sort_order ASC, title ASC")->fetchAll();

$schema_types = ['WebPage','AboutPage','ContactPage','LocalBusiness','FAQPage','ServicePage'];

$page_title = $edit_page ? 'Oldal szerkesztése' : 'Oldalak';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<?php if ($edit_page || isset($_GET['new'])): ?>
<!-- ══ SZERKESZTŐ NÉZET ══ -->
<div class="page-header">
    <h2><i class="fas fa-<?= $edit_page ? 'edit' : 'plus' ?>"></i> <?= $edit_page ? 'Oldal szerkesztése' : 'Új oldal' ?></h2>
    <a href="pages.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Vissza a listához</a>
</div>

<form method="POST" action="pages.php" id="pageForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="save_page" value="1">
    <input type="hidden" name="id" value="<?= $edit_page['id'] ?? 0 ?>">

    <div class="editor-layout">
        <!-- Fő tartalom -->
        <div class="editor-main">
            <div class="card">
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Oldal cím *</label>
                    <input type="text" name="title" id="pageTitle"
                           value="<?= e($edit_page['title'] ?? '') ?>"
                           placeholder="Oldal neve" required
                           oninput="autoSlug(this.value)">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-link"></i> Slug (URL) *</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span style="color:var(--text-muted);font-size:13px;"><?= BASE_URL ?>/</span>
                        <input type="text" name="slug" id="pageSlug"
                               value="<?= e($edit_page['slug'] ?? '') ?>"
                               placeholder="oldal-neve" required style="flex:1;">
                    </div>
                </div>
				
				
				<!-- Shortcode beszúró gomb az editor fölé -->
<div class="shortcode-toolbar">
    <span style="font-size:12px;color:var(--text-muted);font-weight:600;">
        <i class="fas fa-code"></i> Shortcode-ok:
    </span>
    <?php
    $shortcodes = [
        '[services]'                          => '📋 Összes szolgáltatás',
        '[services limit="3" columns="3"]'    => '📋 3 szolgáltatás',
        '[services category="Hajvágás"]'      => '📋 Kategória szerint',
        '[staff]'                             => '💈 Műkörmösök',
        '[staff limit="2"]'                   => '💈 2 műkörmös',
        '[booking_button]'                    => '📅 Foglalás gomb',
        '[opening_hours]'                     => '🕐 Nyitvatartás',
        '[map]'                               => '🗺️ Térkép',
    ];
    foreach ($shortcodes as $code => $label):
    ?>
    <button type="button" class="sc-insert-btn"
            onclick="insertShortcode('<?= htmlspecialchars($code, ENT_QUOTES) ?>')">
        <?= $label ?>
    </button>
    <?php endforeach; ?>
</div>

<style>
.shortcode-toolbar { display:flex; flex-wrap:wrap; gap:6px; padding:10px 12px; background:#f9fafb; border:1px solid var(--border); border-bottom:none; border-radius:8px 8px 0 0; }
.sc-insert-btn { padding:4px 10px; background:#fff; border:1px solid var(--border); border-radius:4px; font-size:12px; cursor:pointer; transition:all .2s; color:var(--text); }
.sc-insert-btn:hover { border-color:var(--accent); color:var(--accent); background:#fff5f6; }
</style>

<script>
function insertShortcode(code) {
    const editor = document.getElementById('content'); // TinyMCE vagy textarea
    if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
        tinymce.activeEditor.insertContent(code);
    } else if (editor) {
        const start = editor.selectionStart;
        const end   = editor.selectionEnd;
        editor.value = editor.value.substring(0, start) + code + editor.value.substring(end);
        editor.selectionStart = editor.selectionEnd = start + code.length;
        editor.focus();
    }
}
</script>
				
				
				
				
				
				
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Tartalom</label>
                    <textarea name="content" id="pageContent" rows="20"><?= e($edit_page['content'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Oldalpanel -->
        <div class="editor-sidebar">
            <!-- Publikálás -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-paper-plane"></i> Publikálás</h3></div>
                <div class="form-group">
                    <label>Státusz</label>
                    <select name="status">
                        <option value="draft"     <?= ($edit_page['status'] ?? '') === 'draft'     ? 'selected' : '' ?>>Piszkozat</option>
                        <option value="published" <?= ($edit_page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publikus</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sorrend</label>
                    <input type="number" name="sort_order" value="<?= $edit_page['sort_order'] ?? 0 ?>" min="0">
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">
                        <i class="fas fa-save"></i> Mentés
                    </button>
                    <?php if ($edit_page): ?>
                    <a href="<?= BASE_URL ?>/<?= e($edit_page['slug']) ?>" target="_blank" class="btn btn-secondary">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SEO -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-search"></i> SEO beállítások</h3></div>
                <div class="form-group">
                    <label>Meta title</label>
                    <input type="text" name="meta_title"
                           value="<?= e($edit_page['meta_title'] ?? '') ?>"
                           placeholder="SEO cím (max 60 karakter)">
                    <small id="metaTitleCount" style="color:var(--text-muted);">0/60</small>
                </div>
                <div class="form-group">
                    <label>Meta description</label>
                    <textarea name="meta_description" rows="3"
                              placeholder="SEO leírás (max 160 karakter)"
                              id="metaDesc"><?= e($edit_page['meta_description'] ?? '') ?></textarea>
                    <small id="metaDescCount" style="color:var(--text-muted);">0/160</small>
                </div>
            </div>

            <!-- Schema -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-code"></i> Schema.org típus</h3></div>
                <div class="form-group">
                    <select name="schema_type">
                        <?php foreach ($schema_types as $st): ?>
                            <option value="<?= $st ?>" <?= ($edit_page['schema_type'] ?? 'WebPage') === $st ? 'selected' : '' ?>>
                                <?= $st ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:var(--text-muted);display:block;margin-top:6px;">
                        Ez határozza meg az oldal strukturált adatát a Google számára.
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>









<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/t1ps7dpln7ehd64mx2dgfrzteiq1mfhgv8o3bbpx2wijxxlm/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#pageContent',
    language: 'hu_HU',
    height: 500,
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
        .replace(/[^a-z0-9\s-]/g,'')
        .replace(/[\s]+/g,'-').trim();
    const slugEl = document.getElementById('pageSlug');
    if (!slugEl.dataset.manual) slugEl.value = slug;
}
document.getElementById('pageSlug').addEventListener('input', function() {
    this.dataset.manual = true;
});

// SEO számlálók
const metaTitleInput = document.querySelector('[name="meta_title"]');
const metaDescInput  = document.getElementById('metaDesc');
function updateCount(input, countEl, max) {
    const len = input.value.length;
    countEl.textContent = `${len}/${max}`;
    countEl.style.color = len > max ? 'var(--red)' : len > max * 0.85 ? 'var(--orange)' : 'var(--text-muted)';
}
if (metaTitleInput) {
    metaTitleInput.addEventListener('input', () => updateCount(metaTitleInput, document.getElementById('metaTitleCount'), 60));
    updateCount(metaTitleInput, document.getElementById('metaTitleCount'), 60);
}
if (metaDescInput) {
    metaDescInput.addEventListener('input', () => updateCount(metaDescInput, document.getElementById('metaDescCount'), 160));
    updateCount(metaDescInput, document.getElementById('metaDescCount'), 160);
}
</script>

<?php else: ?>
<!-- ══ LISTA NÉZET ══ -->
<div class="page-header">
    <h2><i class="fas fa-file-alt"></i> Oldalak kezelése</h2>
    <a href="pages.php?new=1" class="btn btn-primary"><i class="fas fa-plus"></i> Új oldal</a>
</div>

<div class="card">
    <input type="text" id="tableSearch" placeholder="🔍 Keresés..." style="max-width:300px;margin-bottom:16px;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Cím</th>
                <th>Slug</th>
                <th>Schema</th>
                <th>Sorrend</th>
                <th>Státusz</th>
                <th>Frissítve</th>
                <th>Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($pages): ?>
            <?php foreach ($pages as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><strong><?= e($p['title']) ?></strong></td>
                <td>
                    <a href="<?= BASE_URL ?>/<?= e($p['slug']) ?>" target="_blank" style="color:var(--blue);font-size:12px;">
                        /<?= e($p['slug']) ?> <i class="fas fa-external-link-alt" style="font-size:10px;"></i>
                    </a>
                </td>
                <td><code style="font-size:11px;"><?= e($p['schema_type']) ?></code></td>
                <td><?= $p['sort_order'] ?></td>
                <td>
                    <a href="pages.php?toggle=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                       class="badge <?= $p['status']==='published' ? 'badge-published' : 'badge-draft' ?>"
                       style="cursor:pointer;text-decoration:none;">
                        <?= $p['status']==='published' ? 'Publikus' : 'Piszkozat' ?>
                    </a>
                </td>
                <td style="font-size:12px;color:var(--text-muted);"><?= date('Y.m.d', strtotime($p['updated_at'])) ?></td>
                <td>
                    <div class="table-actions">
                        <a href="pages.php?edit=<?= $p['id'] ?>" class="action-btn edit" title="Szerkesztés">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/<?= e($p['slug']) ?>" target="_blank" class="action-btn view" title="Megtekintés">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="pages.php?delete=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="action-btn delete" title="Törlés"
                           data-confirm="Biztosan törölni szeretnéd ezt az oldalt?">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="8"><div class="empty-state"><i class="fas fa-file"></i><p>Még nincs oldal létrehozva.</p></div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
.editor-layout { display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; }
@media(max-width:900px) { .editor-layout { grid-template-columns:1fr; } }
</style>

<?php require_once 'partials/footer.php'; ?>