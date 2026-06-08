<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message      = '';
$message_type = 'success';

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cat'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $name = trim($_POST['cat_name'] ?? '');
    $desc = trim($_POST['cat_description'] ?? '');
    $id   = (int)($_POST['cat_id'] ?? 0);

    if ($name) {
        $slug = generate_slug($name);
        if ($id > 0) {
            $pdo->prepare("UPDATE post_categories SET name=?,slug=?,description=? WHERE id=?")
                ->execute([$name, $slug, $desc, $id]);
            $message = 'Kategória frissítve!';
        } else {
            try {
                $pdo->prepare("INSERT INTO post_categories (name,slug,description) VALUES (?,?,?)")
                    ->execute([$name, $slug, $desc]);
                $message = 'Kategória létrehozva!';
            } catch (PDOException $e) {
                $message      = 'Ez a kategória már létezik!';
                $message_type = 'error';
            }
        }
        // Visszairányítás ha posts.php-ból jöttünk
        if (!empty($_POST['redirect_to'])) {
            header('Location: ' . $_POST['redirect_to']);
            exit;
        }
    }
}

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM post_categories WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Kategória törölve!';
    }
}

$categories = $pdo->query("
    SELECT c.*, COUNT(pv.post_id) as post_count
    FROM post_categories c
    LEFT JOIN post_category_pivot pv ON pv.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.name ASC
")->fetchAll();

$page_title = 'Blog kategóriák';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-folder"></i> Blog kategóriák</h2>
    <a href="posts.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Vissza a bejegyzésekhez
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

    <!-- Lista -->
    <div class="card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Név</th>
                    <th>Slug</th>
                    <th>Leírás</th>
                    <th>Bejegyzések</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories): ?>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><strong><?= e($cat['name']) ?></strong></td>
                    <td><small style="color:var(--blue);"><?= e($cat['slug']) ?></small></td>
                    <td><small><?= e(mb_substr($cat['description'] ?? '',0,60)) ?></small></td>
                    <td><span class="badge badge-confirmed"><?= $cat['post_count'] ?> db</span></td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn edit"
                                    onclick="editCat(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="post_categories.php?delete=<?= $cat['id'] ?>&csrf_token=<?= csrf_token() ?>"
                               class="action-btn delete"
                               data-confirm="Törlöd ezt a kategóriát? A bejegyzések megmaradnak.">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-folder"></i>
                            <p>Még nincs kategória.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Form -->
    <div class="card">
        <div class="card-header">
            <h3 id="catFormTitle"><i class="fas fa-folder-plus"></i> Új kategória</h3>
        </div>
        <form method="POST" action="post_categories.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_cat"   value="1">
            <input type="hidden" name="cat_id"     id="catId" value="0">
            <div class="form-group">
                <label>Kategória neve *</label>
                <input type="text" name="cat_name" id="catName"
                       placeholder="pl. Hajvágás tippek" required>
            </div>
            <div class="form-group">
                <label>Leírás <small style="color:var(--text-muted);">(opcionális)</small></label>
                <textarea name="cat_description" id="catDesc" rows="3"
                          placeholder="Rövid leírás a kategóriáról..."></textarea>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">
                    <i class="fas fa-save"></i> Mentés
                </button>
                <button type="button" class="btn btn-secondary"
                        onclick="resetCatForm()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editCat(cat) {
    document.getElementById('catId').value   = cat.id;
    document.getElementById('catName').value = cat.name;
    document.getElementById('catDesc').value = cat.description || '';
    document.getElementById('catFormTitle').innerHTML =
        '<i class="fas fa-edit"></i> Kategória szerkesztése';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetCatForm() {
    document.getElementById('catId').value   = '0';
    document.getElementById('catName').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('catFormTitle').innerHTML =
        '<i class="fas fa-folder-plus"></i> Új kategória';
}
</script>

<?php require_once 'partials/footer.php'; ?>