<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message      = '';
$message_type = 'success';

// ── MENÜPONT TÖRLÉS ──
if (isset($_GET['delete_item']) && is_numeric($_GET['delete_item'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM menu_items WHERE id=?")->execute([$_GET['delete_item']]);
        $message = 'Menüpont törölve!';
    }
}

// ── FEL / LE MOZGATÁS ──
if (isset($_GET['move']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    if (csrf_verify()) {
        $id        = (int)$_GET['id'];
        $direction = $_GET['move'];
        $menu_id   = (int)($_GET['menu'] ?? 0);

        $item = $pdo->prepare("SELECT * FROM menu_items WHERE id=?");
        $item->execute([$id]);
        $item = $item->fetch();

        if ($item && $direction === 'up') {
            $swap = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id=? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1");
            $swap->execute([$item['menu_id'], $item['sort_order']]);
            $swap = $swap->fetch();
        } elseif ($item && $direction === 'down') {
            $swap = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id=? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1");
            $swap->execute([$item['menu_id'], $item['sort_order']]);
            $swap = $swap->fetch();
        }

        if (!empty($swap)) {
            $pdo->prepare("UPDATE menu_items SET sort_order=? WHERE id=?")->execute([$swap['sort_order'], $item['id']]);
            $pdo->prepare("UPDATE menu_items SET sort_order=? WHERE id=?")->execute([$item['sort_order'], $swap['id']]);
        }
    }
}

// ── MENÜPONT HOZZÁADÁS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $menu_id = (int)$_POST['menu_id'];
    $label   = trim($_POST['label']   ?? '');
    $url     = trim($_POST['url']     ?? '');
    $target  = $_POST['target']       ?? '_self';
    $type    = $_POST['item_type']    ?? 'custom';
    $ref_id  = (int)($_POST['ref_id'] ?? 0);

    // URL generálás típus szerint
    if ($type === 'page' && $ref_id) {
        $pg = $pdo->prepare("SELECT slug, title FROM pages WHERE id=?");
        $pg->execute([$ref_id]);
        $pg = $pg->fetch();
        if ($pg) {
            $url   = BASE_URL . '/' . $pg['slug'];
            $label = $label ?: $pg['title'];
        }
    } elseif ($type === 'post' && $ref_id) {
        $pt = $pdo->prepare("SELECT slug, title FROM posts WHERE id=?");
        $pt->execute([$ref_id]);
        $pt = $pt->fetch();
        if ($pt) {
            $url   = BASE_URL . '/blog/' . $pt['slug'];
            $label = $label ?: $pt['title'];
        }
    } elseif ($type === 'booking') {
        $url   = BASE_URL . '/foglalas';
        $label = $label ?: 'Időpontfoglalás';
    } elseif ($type === 'blog') {
        $url   = BASE_URL . '/blog';
        $label = $label ?: 'Blog';
    } elseif ($type === 'home') {
        $url   = BASE_URL . '/';
        $label = $label ?: 'Főoldal';
    }

    if ($label && $url) {
        $max = $pdo->prepare("SELECT MAX(sort_order) FROM menu_items WHERE menu_id=?");
        $max->execute([$menu_id]);
        $sort = (int)$max->fetchColumn() + 1;

        $pdo->prepare("INSERT INTO menu_items (menu_id, label, url, sort_order, target) VALUES (?,?,?,?,?)")
            ->execute([$menu_id, $label, $url, $sort, $target]);
        $message = 'Menüpont hozzáadva!';
    } else {
        $message      = 'A felirat és az URL megadása kötelező!';
        $message_type = 'error';
    }
}

// ── MENÜ LÉTREHOZÁS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_menu'])) {
    if (!csrf_verify()) die('CSRF hiba');
    $name     = trim($_POST['menu_name']     ?? '');
    $location = trim($_POST['menu_location'] ?? '');
    if ($name) {
        $pdo->prepare("INSERT INTO menus (name, location) VALUES (?,?)")->execute([$name, $location]);
        $message = 'Menü létrehozva!';
    }
}

// ── MENÜ TÖRLÉS ──
if (isset($_GET['delete_menu']) && is_numeric($_GET['delete_menu'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM menu_items WHERE menu_id=?")->execute([$_GET['delete_menu']]);
        $pdo->prepare("DELETE FROM menus WHERE id=?")->execute([$_GET['delete_menu']]);
        $message = 'Menü törölve!';
    }
}

// ── ADATOK ──
$menus = $pdo->query("SELECT * FROM menus ORDER BY id ASC")->fetchAll();
$active_menu_id = (int)($_GET['menu'] ?? 0);
if (!$active_menu_id && $menus) $active_menu_id = $menus[0]['id'];

$menu_items = [];
if ($active_menu_id) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id=? ORDER BY sort_order ASC");
    $stmt->execute([$active_menu_id]);
    $menu_items = $stmt->fetchAll();
}

$all_pages = $pdo->query("SELECT id, title, slug FROM pages WHERE status='published' ORDER BY title ASC")->fetchAll();
$all_posts = $pdo->query("SELECT id, title, slug FROM posts WHERE status='published' ORDER BY title ASC")->fetchAll();

$active_menu = null;
foreach ($menus as $m) {
    if ($m['id'] == $active_menu_id) { $active_menu = $m; break; }
}

$page_title = 'Menük';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-bars"></i> Menük kezelése</h2>
    <button class="btn btn-primary" data-modal-open="createMenuModal">
        <i class="fas fa-plus"></i> Új menü
    </button>
</div>

<div class="menus-layout">

    <!-- ── Bal: Menü választó ── -->
    <div class="menus-sidebar-panel">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-list"></i> Menük</h3></div>
            <?php if ($menus): ?>
            <ul class="menu-list">
                <?php foreach ($menus as $m): ?>
                <li class="<?= $m['id'] == $active_menu_id ? 'active' : '' ?>">
                    <a href="menus.php?menu=<?= $m['id'] ?>">
                        <strong><?= e($m['name']) ?></strong>
                        <?php if ($m['location']): ?>
                        <span class="menu-location-badge">
                            <?= $m['location'] === 'header' ? '🔝 Fejléc' : '📋 Lábléc' ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="menus.php?delete_menu=<?= $m['id'] ?>&csrf_token=<?= csrf_token() ?>"
                       class="menu-delete-btn"
                       data-confirm="Biztosan törlöd ezt a menüt az összes elemével?">
                        <i class="fas fa-trash"></i>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-bars"></i><p>Még nincs menü.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Jobb: Menü szerkesztő ── -->
    <?php if ($active_menu_id && $active_menu): ?>
    <div class="menus-main-panel">

        <div class="menu-editor-grid">

            <!-- ── Elemek hozzáadása ── -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Elemek hozzáadása</h3>
                    </div>

                    <!-- Tabok -->
                    <div class="add-item-tabs">
                        <button class="add-tab active" onclick="switchTab('pages', this)">
                            <i class="fas fa-file-alt"></i> Oldalak
                        </button>
                        <button class="add-tab" onclick="switchTab('posts', this)">
                            <i class="fas fa-blog"></i> Bejegyzések
                        </button>
                        <button class="add-tab" onclick="switchTab('quick', this)">
                            <i class="fas fa-bolt"></i> Gyors linkek
                        </button>
                        <button class="add-tab" onclick="switchTab('custom', this)">
                            <i class="fas fa-link"></i> Egyéni URL
                        </button>
                    </div>

                    <!-- OLDALAK -->
                    <div class="add-tab-content active" id="tab-pages">
                        <?php if ($all_pages): ?>
                        <div class="item-picker-list">
                            <?php foreach ($all_pages as $pg): ?>
                            <div class="item-picker-row">
                                <div class="item-picker-info">
                                    <i class="fas fa-file-alt"></i>
                                    <span><?= e($pg['title']) ?></span>
                                    <small><?= e($pg['slug']) ?></small>
                                </div>
                                <form method="POST" action="menus.php?menu=<?= $active_menu_id ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="add_item"   value="1">
                                    <input type="hidden" name="menu_id"    value="<?= $active_menu_id ?>">
                                    <input type="hidden" name="item_type"  value="page">
                                    <input type="hidden" name="ref_id"     value="<?= $pg['id'] ?>">
                                    <input type="hidden" name="label"      value="<?= e($pg['title']) ?>">
                                    <input type="hidden" name="url"        value="">
                                    <input type="hidden" name="target"     value="_self">
                                    <button type="submit" class="btn-add-item">
                                        <i class="fas fa-plus"></i> Hozzáad
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-state-sm">
                            <i class="fas fa-file-alt"></i>
                            <p>Nincs publikus oldal.</p>
                            <a href="pages.php" class="btn btn-secondary btn-sm">Oldal létrehozása</a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- BEJEGYZÉSEK -->
                    <div class="add-tab-content" id="tab-posts">
                        <?php if ($all_posts): ?>
                        <div class="item-picker-list">
                            <?php foreach ($all_posts as $pt): ?>
                            <div class="item-picker-row">
                                <div class="item-picker-info">
                                    <i class="fas fa-blog"></i>
                                    <span><?= e($pt['title']) ?></span>
                                    <small>blog/<?= e($pt['slug']) ?></small>
                                </div>
                                <form method="POST" action="menus.php?menu=<?= $active_menu_id ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="add_item"   value="1">
                                    <input type="hidden" name="menu_id"    value="<?= $active_menu_id ?>">
                                    <input type="hidden" name="item_type"  value="post">
                                    <input type="hidden" name="ref_id"     value="<?= $pt['id'] ?>">
                                    <input type="hidden" name="label"      value="<?= e($pt['title']) ?>">
                                    <input type="hidden" name="url"        value="">
                                    <input type="hidden" name="target"     value="_self">
                                    <button type="submit" class="btn-add-item">
                                        <i class="fas fa-plus"></i> Hozzáad
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-state-sm">
                            <i class="fas fa-blog"></i>
                            <p>Nincs publikus bejegyzés.</p>
                            <a href="posts.php" class="btn btn-secondary btn-sm">Bejegyzés létrehozása</a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- GYORS LINKEK -->
                    <div class="add-tab-content" id="tab-quick">
                        <div class="quick-links-grid">
                            <?php
                            $quick_links = [
                                ['type'=>'home',    'icon'=>'fas fa-home',           'label'=>'Főoldal',         'url'=> BASE_URL . '/'],
                                ['type'=>'booking', 'icon'=>'fas fa-calendar-check', 'label'=>'Időpontfoglalás', 'url'=> BASE_URL . '/foglalas'],
                                ['type'=>'blog',    'icon'=>'fas fa-blog',           'label'=>'Blog',            'url'=> BASE_URL . '/blog'],
                            ];
                            foreach ($quick_links as $ql):
                            ?>
                            <div class="quick-link-card">
                                <i class="<?= $ql['icon'] ?>"></i>
                                <strong><?= $ql['label'] ?></strong>
                                <small><?= e($ql['url']) ?></small>
                                <form method="POST" action="menus.php?menu=<?= $active_menu_id ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="add_item"   value="1">
                                    <input type="hidden" name="menu_id"    value="<?= $active_menu_id ?>">
                                    <input type="hidden" name="item_type"  value="<?= $ql['type'] ?>">
                                    <input type="hidden" name="label"      value="<?= e($ql['label']) ?>">
                                    <input type="hidden" name="url"        value="<?= e($ql['url']) ?>">
                                    <input type="hidden" name="ref_id"     value="0">
                                    <input type="hidden" name="target"     value="_self">
                                    <button type="submit" class="btn-add-item" style="width:100%;justify-content:center;margin-top:8px;">
                                        <i class="fas fa-plus"></i> Hozzáad
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- EGYÉNI URL -->
                    <div class="add-tab-content" id="tab-custom">
                        <form method="POST" action="menus.php?menu=<?= $active_menu_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="add_item"  value="1">
                            <input type="hidden" name="menu_id"   value="<?= $active_menu_id ?>">
                            <input type="hidden" name="item_type" value="custom">
                            <input type="hidden" name="ref_id"    value="0">
                            <div class="form-group">
                                <label>Felirat *</label>
                                <input type="text" name="label" placeholder="pl. Kapcsolat" required>
                            </div>
                            <div class="form-group">
                                <label>URL *</label>
                                <input type="text" name="url" placeholder="pl. https://... vagy /kapcsolat" required>
                            </div>
                            <div class="form-group">
                                <label>Megnyitás</label>
                                <select name="target">
                                    <option value="_self">Ugyanabban az ablakban</option>
                                    <option value="_blank">Új ablakban</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;">
                                <i class="fas fa-plus"></i> Hozzáadás
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Menü tartalma ── -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-stream"></i>
                            <?= e($active_menu['name']) ?>
                            <?php if ($active_menu['location']): ?>
                            <span class="menu-location-badge">
                                <?= $active_menu['location'] === 'header' ? '🔝 Fejléc' : '📋 Lábléc' ?>
                            </span>
                            <?php endif; ?>
                        </h3>
                        <span style="font-size:12px;color:var(--text-muted);"><?= count($menu_items) ?> elem</span>
                    </div>

                    <?php if ($menu_items): ?>
                    <ul class="menu-items-list">
                        <?php foreach ($menu_items as $i => $item): ?>
                        <li class="menu-item-row">
                            <!-- Sorrend gombok -->
                            <div class="order-btns">
                                <?php if ($i > 0): ?>
                                <a href="menus.php?menu=<?= $active_menu_id ?>&move=up&id=<?= $item['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   class="order-btn" title="Fel">
                                    <i class="fas fa-chevron-up"></i>
                                </a>
                                <?php else: ?>
                                <span class="order-btn disabled"></span>
                                <?php endif; ?>

                                <?php if ($i < count($menu_items) - 1): ?>
                                <a href="menus.php?menu=<?= $active_menu_id ?>&move=down&id=<?= $item['id'] ?>&csrf_token=<?= csrf_token() ?>"
                                   class="order-btn" title="Le">
                                    <i class="fas fa-chevron-down"></i>
                                </a>
                                <?php else: ?>
                                <span class="order-btn disabled"></span>
                                <?php endif; ?>
                            </div>

                            <!-- Tartalom -->
                            <div class="menu-item-content">
                                <div class="menu-item-num"><?= $i + 1 ?></div>
                                <div>
                                    <strong><?= e($item['label']) ?></strong>
                                    <div class="menu-item-url">
                                        <i class="fas fa-link"></i>
                                        <?= e($item['url']) ?>
                                        <?php if ($item['target'] === '_blank'): ?>
                                            <span class="badge badge-draft" style="font-size:10px;margin-left:4px;">új ablak</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Törlés -->
                            <a href="menus.php?delete_item=<?= $item['id'] ?>&csrf_token=<?= csrf_token() ?>&menu=<?= $active_menu_id ?>"
                               class="action-btn delete"
                               data-confirm="Biztosan törlöd ezt a menüpontot?">
                                <i class="fas fa-trash"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="menu-preview-note">
                        <i class="fas fa-eye"></i>
                        Így jelenik meg a navigációban – fentről lefelé, balról jobbra.
                    </div>

                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-stream"></i>
                        <p>Még nincs menüpont.<br>
                        <small>Adj hozzá elemeket a bal oldali panelből!</small></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-bars"></i>
            <p>Válassz egy menüt a bal oldalon, vagy hozz létre újat!</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── MODAL: Új menü ── -->
<div class="modal-overlay" id="createMenuModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Új menü létrehozása</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="menus.php">
            <input type="hidden" name="csrf_token"  value="<?= csrf_token() ?>">
            <input type="hidden" name="create_menu" value="1">
            <div class="modal-body">
                <div class="form-group">
                    <label>Menü neve *</label>
                    <input type="text" name="menu_name" placeholder="pl. Főmenü" required>
                </div>
                <div class="form-group">
                    <label>Elhelyezés</label>
                    <select name="menu_location">
                        <option value="header">🔝 Header (fejléc)</option>
                        <option value="footer">📋 Footer (lábléc)</option>
                        <option value="">Nincs meghatározva</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Mégse</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Létrehozás</button>
            </div>
        </form>
    </div>
</div>

<style>
.menus-layout { display:grid; grid-template-columns:220px 1fr; gap:24px; align-items:start; }
@media(max-width:768px) { .menus-layout { grid-template-columns:1fr; } }

/* Menü lista bal oldal */
.menu-list { list-style:none; }
.menu-list li { display:flex; align-items:center; border-bottom:1px solid var(--border); }
.menu-list li:last-child { border-bottom:none; }
.menu-list li a:first-child { flex:1; padding:12px 14px; text-decoration:none; color:var(--text); display:flex; flex-direction:column; gap:4px; transition:background .2s; }
.menu-list li a:first-child:hover { background:#f9fafb; }
.menu-list li.active a:first-child { background:#fff5f6; }
.menu-list li.active strong { color:var(--accent); }
.menu-location-badge { font-size:11px; color:var(--text-muted); background:#f0f0f0; padding:2px 7px; border-radius:20px; display:inline-block; margin-top:2px; }
.menu-delete-btn { padding:10px 12px; color:var(--red); text-decoration:none; opacity:.4; transition:opacity .2s; }
.menu-delete-btn:hover { opacity:1; }

/* Szerkesztő grid */
.menu-editor-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; }
@media(max-width:900px) { .menu-editor-grid { grid-template-columns:1fr; } }

/* Tabok */
.add-item-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; padding:4px; background:#f5f5f5; border-radius:8px; }
.add-tab { padding:7px 12px; border:none; background:transparent; border-radius:6px; font-size:12px; font-weight:600; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; gap:5px; transition:all .2s; }
.add-tab.active { background:#fff; color:var(--accent); box-shadow:0 1px 4px rgba(0,0,0,.1); }
.add-tab:hover:not(.active) { color:var(--text); }
.add-tab-content { display:none; }
.add-tab-content.active { display:block; }

/* Item picker */
.item-picker-list { display:flex; flex-direction:column; gap:6px; max-height:360px; overflow-y:auto; }
.item-picker-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:9px 12px; background:#f9fafb; border:1px solid var(--border); border-radius:8px; transition:border-color .2s; }
.item-picker-row:hover { border-color:var(--accent); background:#fff5f6; }
.item-picker-info { display:flex; align-items:center; gap:8px; flex:1; min-width:0; }
.item-picker-info i { color:var(--accent); font-size:13px; flex-shrink:0; }
.item-picker-info span { font-size:13px; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.item-picker-info small { font-size:11px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:none; }
.item-picker-row:hover .item-picker-info small { display:block; }
.btn-add-item { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; background:var(--accent); color:#fff; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap; transition:all .2s; }
.btn-add-item:hover { background:var(--accent-dark, #c0392b); transform:translateY(-1px); }

/* Gyors linkek */
.quick-links-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
@media(max-width:640px) { .quick-links-grid { grid-template-columns:1fr; } }
.quick-link-card { background:#f9fafb; border:1px solid var(--border); border-radius:8px; padding:14px 12px; text-align:center; transition:border-color .2s; }
.quick-link-card:hover { border-color:var(--accent); }
.quick-link-card i { font-size:22px; color:var(--accent); display:block; margin-bottom:6px; }
.quick-link-card strong { display:block; font-size:13px; color:var(--text); margin-bottom:2px; }
.quick-link-card small { display:block; font-size:10px; color:var(--text-muted); margin-bottom:6px; word-break:break-all; }

/* Menü tartalom lista */
.menu-items-list { list-style:none; display:flex; flex-direction:column; gap:6px; }
.menu-item-row { display:flex; align-items:center; gap:10px; background:#f9fafb; border:1px solid var(--border); border-radius:8px; padding:10px 12px; transition:border-color .2s; }
.menu-item-row:hover { border-color:var(--border); background:#fff; }
.order-btns { display:flex; flex-direction:column; gap:2px; }
.order-btn { width:24px; height:24px; border-radius:4px; background:#f0f0f0; color:var(--text-muted); display:flex; align-items:center; justify-content:center; font-size:11px; text-decoration:none; transition:all .2s; }
.order-btn:hover { background:var(--accent); color:#fff; }
.order-btn.disabled { opacity:0; pointer-events:none; }
.menu-item-content { display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
.menu-item-num { width:24px; height:24px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; }
.menu-item-content strong { display:block; font-size:14px; color:var(--text); }
.menu-item-url { font-size:11px; color:var(--text-muted); display:flex; align-items:center; gap:4px; margin-top:2px; }
.menu-item-url i { font-size:9px; }
.menu-preview-note { margin-top:16px; padding:10px 14px; background:#f0f9ff; border-radius:6px; font-size:12px; color:#0ea5e9; display:flex; align-items:center; gap:8px; }
.empty-state-sm { text-align:center; padding:24px 16px; color:var(--text-muted); }
.empty-state-sm i { font-size:32px; opacity:.4; display:block; margin-bottom:8px; }
.empty-state-sm p { font-size:13px; margin-bottom:10px; }
</style>

<script>
function switchTab(tab, btn) {
    // Tabok elrejtése
    document.querySelectorAll('.add-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.add-tab').forEach(b => b.classList.remove('active'));
    // Aktív tab megjelenítése
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php require_once 'partials/footer.php'; ?>