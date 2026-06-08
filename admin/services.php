<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message = '';
$message_type = 'success';

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$_GET['delete']]);
        $message = 'Szolgáltatás törölve!';
    }
}

// ── STÁTUSZ TOGGLE ──
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    if (csrf_verify()) {
        $pdo->prepare("UPDATE services SET active = 1 - active WHERE id = ?")->execute([$_GET['toggle']]);
        $message = 'Státusz frissítve!';
    }
}

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration'] ?? 30);
    $price    = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $sort     = (int)($_POST['sort_order'] ?? 0);
    $active   = isset($_POST['active']) ? 1 : 0;

    if ($name && $duration > 0 && $price >= 0) {
        if ($id > 0) {
            $pdo->prepare("UPDATE services SET name=?,description=?,duration=?,price=?,category=?,sort_order=?,active=? WHERE id=?")
                ->execute([$name, $desc, $duration, $price, $category, $sort, $active, $id]);
            $message = 'Szolgáltatás frissítve!';
        } else {
            $pdo->prepare("INSERT INTO services (name,description,duration,price,category,sort_order,active) VALUES (?,?,?,?,?,?,?)")
                ->execute([$name, $desc, $duration, $price, $category, $sort, $active]);
            $message = 'Szolgáltatás létrehozva!';
        }
    } else {
        $message = 'Kérjük töltsd ki a kötelező mezőket!';
        $message_type = 'error';
    }
}

// ── LISTA ──
$services = $pdo->query("SELECT s.*, 
    (SELECT COUNT(*) FROM staff_services ss WHERE ss.service_id = s.id) as staff_count
    FROM services s ORDER BY s.sort_order ASC, s.name ASC")->fetchAll();

// Kategóriák
$categories = $pdo->query("SELECT DISTINCT category FROM services ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Szolgáltatások';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
        <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
        <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-concierge-bell"></i> Szolgáltatások kezelése</h2>
    <button class="btn btn-primary" onclick="openServiceModal()">
        <i class="fas fa-plus"></i> Új szolgáltatás
    </button>
</div>

<!-- Összesítő kártyák -->
<div class="services-summary">
    <?php
    $cats = $pdo->query("SELECT category, COUNT(*) as cnt, SUM(active) as active_cnt FROM services GROUP BY category ORDER BY category")->fetchAll();
    foreach ($cats as $cat):
    ?>
    <div class="summary-pill">
        <strong><?= e($cat['category']) ?></strong>
        <span><?= $cat['active_cnt'] ?>/<?= $cat['cnt'] ?> aktív</span>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div style="margin-bottom:16px;display:flex;gap:12px;align-items:center;">
        <input type="text" id="tableSearch" placeholder="🔍 Keresés..." style="max-width:280px;">
        <select id="categoryFilter" style="max-width:200px;">
            <option value="">Minden kategória</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <table class="admin-table" id="servicesTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Szolgáltatás neve</th>
                <th>Kategória</th>
                <th>Időtartam</th>
                <th>Ár</th>
                <th>Műkörmösök</th>
                <th>Sorrend</th>
                <th>Státusz</th>
                <th>Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($services): ?>
            <?php foreach ($services as $s): ?>
            <tr data-category="<?= e($s['category']) ?>">
                <td><?= $s['id'] ?></td>
                <td>
                    <strong><?= e($s['name']) ?></strong>
                    <?php if ($s['description']): ?>
                        <br><small style="color:#999;"><?= e(mb_substr($s['description'],0,60)) ?>...</small>
                    <?php endif; ?>
                </td>
                <td><span class="category-tag"><?= e($s['category']) ?></span></td>
                <td><i class="fas fa-clock" style="color:#999;"></i> <?= $s['duration'] ?> perc</td>
                <td><strong><?= number_format($s['price'],0,',',' ') ?> Ft</strong></td>
                <td><?= $s['staff_count'] ?> műkörmös</td>
                <td><?= $s['sort_order'] ?></td>
                <td>
                    <a href="services.php?toggle=<?= $s['id'] ?>&csrf_token=<?= csrf_token() ?>"
                       class="badge <?= $s['active'] ? 'badge-confirmed' : 'badge-cancelled' ?>"
                       style="cursor:pointer;text-decoration:none;"
                       title="Kattints a státusz változtatáshoz">
                        <?= $s['active'] ? 'Aktív' : 'Inaktív' ?>
                    </a>
                </td>
                <td>
                    <div class="table-actions">
                        <button class="action-btn edit" title="Szerkesztés"
                            onclick="openServiceModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="services.php?delete=<?= $s['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="action-btn delete" title="Törlés"
                           data-confirm="Biztosan törlöd ezt a szolgáltatást?">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:#999;">Még nincs szolgáltatás.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ── MODAL ── -->
<div class="modal-overlay" id="serviceModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="svcModalTitle"><i class="fas fa-concierge-bell"></i> Szolgáltatás hozzáadása</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="services.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_service" value="1">
            <input type="hidden" name="id" id="svcId" value="0">
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Szolgáltatás neve *</label>
                    <input type="text" name="name" id="svcName" required placeholder="pl. Hajvágás">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Leírás</label>
                    <textarea name="description" id="svcDesc" rows="3" placeholder="Rövid leírás a szolgáltatásról..."></textarea>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Időtartam (perc) *</label>
                        <input type="number" name="duration" id="svcDuration" value="30" min="5" step="5" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Ár (Ft) *</label>
                        <input type="number" name="price" id="svcPrice" value="0" min="0" step="100" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-sort"></i> Sorrend</label>
                        <input type="number" name="sort_order" id="svcSort" value="0" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-folder"></i> Kategória</label>
                    <input type="text" name="category" id="svcCategory" placeholder="pl. Hajvágás, Szakáll, Prémium"
                           list="categoryList">
                    <datalist id="categoryList">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" id="svcActive" checked>
                        <strong>Aktív szolgáltatás</strong>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Mégse</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Mentés</button>
            </div>
        </form>
    </div>
</div>

<style>
.services-summary { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
.summary-pill { background: var(--white); border: 1px solid var(--border); border-radius: 20px; padding: 6px 16px; font-size: 13px; display: flex; gap: 8px; align-items: center; box-shadow: var(--shadow); }
.summary-pill strong { color: var(--primary); }
.summary-pill span { color: var(--accent); font-weight: 600; }
.category-tag { background: #f3f4f6; color: #4b5563; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
</style>

<script>
function openServiceModal(svc = null) {
    document.getElementById('svcModalTitle').innerHTML = svc
        ? '<i class="fas fa-edit"></i> Szolgáltatás szerkesztése'
        : '<i class="fas fa-concierge-bell"></i> Szolgáltatás hozzáadása';
    document.getElementById('svcId').value       = svc ? svc.id : 0;
    document.getElementById('svcName').value     = svc ? svc.name : '';
    document.getElementById('svcDesc').value     = svc ? svc.description : '';
    document.getElementById('svcDuration').value = svc ? svc.duration : 30;
    document.getElementById('svcPrice').value    = svc ? svc.price : 0;
    document.getElementById('svcCategory').value = svc ? svc.category : '';
    document.getElementById('svcSort').value     = svc ? svc.sort_order : 0;
    document.getElementById('svcActive').checked = svc ? svc.active == 1 : true;
    document.getElementById('serviceModal').classList.add('open');
}

// Kategória szűrő
document.getElementById('categoryFilter').addEventListener('change', function () {
    const val = this.value;
    document.querySelectorAll('#servicesTable tbody tr').forEach(row => {
        row.style.display = (!val || row.dataset.category === val) ? '' : 'none';
    });
});
</script>

<?php require_once 'partials/footer.php'; ?>