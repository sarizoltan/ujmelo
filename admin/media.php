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
        $stmt = $pdo->prepare("SELECT filepath FROM media WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        $file = $stmt->fetch();
        if ($file) {
            $full_path = UPLOAD_PATH . $file['filepath'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
        $pdo->prepare("DELETE FROM media WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Fájl törölve!';
    }
}

// ── ALT SZÖVEG MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_alt'])) {
    if (!csrf_verify()) die('CSRF hiba');
    $pdo->prepare("UPDATE media SET alt_text=? WHERE id=?")
        ->execute([trim($_POST['alt_text']), (int)$_POST['media_id']]);
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit;
    }
    $message = 'Alt szöveg mentve!';
}

// ── FELTÖLTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $allowed_types = ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $uploaded = 0;
    $errors   = [];

    if (!empty($_FILES['files']['name'][0])) {

        // Mappa létrehozása ha nem létezik
        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        foreach ($_FILES['files']['tmp_name'] as $idx => $tmp) {
            $name  = $_FILES['files']['name'][$idx];
            $size  = $_FILES['files']['size'][$idx];
            $error = $_FILES['files']['error'][$idx];

            if ($error !== UPLOAD_ERR_OK) { 
                $errors[] = "$name: feltöltési hiba."; 
                continue; 
            }
            if ($size > $max_size) { 
                $errors[] = "$name: túl nagy (max 5MB)."; 
                continue; 
            }

            // MIME típus ellenőrzése
            $real_type = mime_content_type($tmp);
            if (!in_array($real_type, $allowed_types)) {
                $errors[] = "$name: nem támogatott formátum ({$real_type}).";
                continue;
            }

            // Fájlnév generálás
            $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $filename = pathinfo($name, PATHINFO_FILENAME);
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
            $newname  = $filename . '_' . time() . '_' . $idx . '.' . $ext;
            $dest     = UPLOAD_PATH . $newname;

            if (move_uploaded_file($tmp, $dest)) {
                // CSAK a fájlnév mentése az adatbázisba, NEM az elérési út!
                $pdo->prepare("INSERT INTO media (filename, filepath, filetype, filesize, alt_text)
                               VALUES (?, ?, ?, ?, ?)")
                    ->execute([$newname, $newname, $real_type, $size, '']);
                $uploaded++;
            } else {
                $errors[] = "$name: nem sikerült a feltöltés.";
            }
        }
    }

    if ($uploaded > 0) $message = "$uploaded fájl sikeresen feltöltve!";
    if ($errors) {
        $message .= ($message ? ' ' : '') . 'Hibák: ' . implode(', ', $errors);
        $message_type = $uploaded > 0 ? 'success' : 'error';
    }
}

// ── LISTA ──
$filter_type = $_GET['type'] ?? '';
$search      = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filter_type === 'image') {
    $where[] = "filetype LIKE 'image/%'";
}
if ($search) {
    $where[] = "(filename LIKE ? OR alt_text LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT * FROM media WHERE " . implode(' AND ', $where) . " ORDER BY uploaded_at DESC");
$stmt->execute($params);
$media_files = $stmt->fetchAll();

// Statisztika
$total_size = $pdo->query("SELECT SUM(filesize) FROM media")->fetchColumn();
$total_count = $pdo->query("SELECT COUNT(*) FROM media")->fetchColumn();

$page_title = 'Média kezelő';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-images"></i> Média kezelő</h2>
    <div style="display:flex;gap:8px;align-items:center;">
        <small style="color:var(--text-muted);">
            <?= $total_count ?> fájl | <?= format_filesize((int)$total_size) ?>
        </small>
        <button class="btn btn-primary" data-modal-open="uploadModal">
            <i class="fas fa-upload"></i> Feltöltés
        </button>
    </div>
</div>

<!-- Szűrők -->
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="media.php" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="search" value="<?= e($search) ?>"
               placeholder="🔍 Keresés fájlnévre, alt szövegre..."
               style="max-width:280px;">
        <select name="type" style="max-width:160px;">
            <option value="">Minden típus</option>
            <option value="image" <?= $filter_type === 'image' ? 'selected' : '' ?>>Képek</option>
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Szűrés</button>
        <a href="media.php" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>

        <div style="margin-left:auto;display:flex;gap:8px;">
            <button type="button" class="view-toggle-btn active" id="gridViewBtn" onclick="setView('grid')">
                <i class="fas fa-th"></i>
            </button>
            <button type="button" class="view-toggle-btn" id="listViewBtn" onclick="setView('list')">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </form>
</div>

<!-- Galéria nézet -->
<div class="media-grid" id="mediaGrid">
    <?php if ($media_files): ?>
    <?php foreach ($media_files as $f): ?>
    <?php $is_image = str_starts_with($f['filetype'], 'image/'); ?>
    <div class="media-item">
        <div class="media-thumb" onclick="openMediaDetail(<?= htmlspecialchars(json_encode($f), ENT_QUOTES) ?>)">
            <?php if ($is_image): ?>
                <img src="<?= UPLOAD_URL . e($f['filepath']) ?>" alt="<?= e($f['alt_text']) ?>" loading="lazy">
            <?php else: ?>
                <div class="media-icon"><i class="fas fa-file"></i></div>
            <?php endif; ?>
        </div>
        <div class="media-info">
            <span class="media-name" title="<?= e($f['filename']) ?>"><?= e(mb_substr($f['filename'],0,20)) ?></span>
            <span class="media-size"><?= format_filesize($f['filesize']) ?></span>
        </div>
        <div class="media-actions">
            <button class="action-btn view" onclick="copyUrl('<?= UPLOAD_URL . e($f['filepath']) ?>')" title="URL másolása">
                <i class="fas fa-copy"></i>
            </button>
            <a href="media.php?delete=<?= $f['id'] ?>&csrf_token=<?= csrf_token() ?>"
               class="action-btn delete" title="Törlés"
               data-confirm="Biztosan törölni szeretnéd ezt a fájlt?">
                <i class="fas fa-trash"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div style="grid-column:1/-1;">
        <div class="empty-state">
            <i class="fas fa-images"></i>
            <p>Még nincs feltöltött fájl.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── MODAL: Feltöltés ── -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal" style="max-width:540px;">
        <div class="modal-header">
            <h3><i class="fas fa-upload"></i> Fájlok feltöltése</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="media.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="upload_media" value="1">
            <div class="modal-body">
                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Húzd ide a fájlokat, vagy kattints a kiválasztáshoz</p>
                    <small>Támogatott: JPG, PNG, WEBP, GIF, SVG | Max: 5MB/fájl</small>
                    <input type="file" name="files[]" id="fileInput" multiple
                           accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                           style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                </div>
                <div id="filePreview" style="display:none;margin-top:16px;">
                    <h4 style="font-size:13px;margin-bottom:8px;">Kiválasztott fájlok:</h4>
                    <ul id="fileList" style="list-style:none;display:flex;flex-direction:column;gap:6px;"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Mégse</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Feltöltés</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: Média részlet ── -->
<div class="modal-overlay" id="mediaDetailModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Fájl részletei</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="mediaDetailBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Bezárás</button>
        </div>
    </div>
</div>

<style>
.media-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:16px; }
.media-grid.list-view { grid-template-columns:1fr; }
.media-grid.list-view .media-item { flex-direction:row; align-items:center; }
.media-grid.list-view .media-thumb { width:60px; height:45px; flex-shrink:0; }
.media-item { background:var(--white); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; transition:box-shadow .2s; }
.media-item:hover { box-shadow:var(--shadow-lg); }
.media-thumb { width:100%; height:120px; overflow:hidden; cursor:pointer; background:#f5f5f5; display:flex; align-items:center; justify-content:center; }
.media-thumb img { width:100%; height:100%; object-fit:cover; transition:transform .3s; }
.media-thumb:hover img { transform:scale(1.05); }
.media-icon { font-size:40px; color:#ccc; }
.media-info { padding:8px 10px; display:flex; flex-direction:column; gap:2px; }
.media-name { font-size:12px; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.media-size { font-size:11px; color:var(--text-muted); }
.media-actions { display:flex; gap:4px; padding:0 8px 8px; }
.upload-zone { border:2px dashed var(--border); border-radius:var(--radius); padding:40px 20px; text-align:center; position:relative; cursor:pointer; transition:border-color .2s; }
.upload-zone:hover, .upload-zone.dragover { border-color:var(--accent); background:#fff5f6; }
.upload-zone i { font-size:48px; color:var(--accent); margin-bottom:12px; display:block; }
.upload-zone p { font-weight:600; margin-bottom:4px; }
.upload-zone small { color:var(--text-muted); }
.view-toggle-btn { padding:7px 10px; border:1px solid var(--border); background:var(--white); border-radius:6px; cursor:pointer; color:var(--text-muted); transition:all .2s; }
.view-toggle-btn.active, .view-toggle-btn:hover { background:var(--accent); color:#fff; border-color:var(--accent); }
</style>

<script>
function setView(type) {
    const grid = document.getElementById('mediaGrid');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    if (type === 'list') {
        grid.classList.add('list-view');
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
    } else {
        grid.classList.remove('list-view');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
    }
    localStorage.setItem('mediaView', type);
}

// Mentett nézet betöltése
const savedView = localStorage.getItem('mediaView');
if (savedView) setView(savedView);

// Fájl előnézet
document.getElementById('fileInput')?.addEventListener('change', function() {
    const preview = document.getElementById('filePreview');
    const list    = document.getElementById('fileList');
    list.innerHTML = '';
    if (this.files.length) {
        preview.style.display = 'block';
        [...this.files].forEach(f => {
            list.innerHTML += `<li style="display:flex;justify-content:space-between;font-size:13px;padding:6px 10px;background:#f9fafb;border-radius:6px;">
                <span><i class="fas fa-file-image" style="color:var(--accent);margin-right:6px;"></i>${f.name}</span>
                <span style="color:var(--text-muted);">${(f.size/1024).toFixed(1)} KB</span>
            </li>`;
        });
    } else {
        preview.style.display = 'none';
    }
});

// Drag & Drop feltöltés
const zone = document.getElementById('uploadZone');
if (zone) {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        document.getElementById('fileInput').files = e.dataTransfer.files;
        document.getElementById('fileInput').dispatchEvent(new Event('change'));
    });
}

function copyUrl(url) {
    navigator.clipboard.writeText(url).then(() => showFlash('URL vágólapra másolva!'));
}

function openMediaDetail(f) {
    const isImage = f.filetype.startsWith('image/');
    const uploadUrl = '<?= UPLOAD_URL ?>';
    document.getElementById('mediaDetailBody').innerHTML = `
        ${isImage ? `<div style="text-align:center;margin-bottom:16px;"><img src="${uploadUrl}${f.filepath}" style="max-width:100%;max-height:300px;border-radius:8px;object-fit:contain;" alt=""></div>` : ''}
        <div class="detail-row"><span class="detail-label">Fájlnév</span><span class="detail-value">${f.filename}</span></div>
        <div class="detail-row"><span class="detail-label">Típus</span><span class="detail-value">${f.filetype}</span></div>
        <div class="detail-row"><span class="detail-label">Méret</span><span class="detail-value">${(f.filesize/1024).toFixed(1)} KB</span></div>
        <div class="detail-row"><span class="detail-label">Feltöltve</span><span class="detail-value">${f.uploaded_at}</span></div>
        <div class="detail-row">
            <span class="detail-label">URL</span>
            <span class="detail-value" style="word-break:break-all;font-size:12px;">
                ${uploadUrl}${f.filepath}
                <button onclick="copyUrl('${uploadUrl}${f.filepath}')" class="btn btn-secondary" style="padding:3px 8px;font-size:11px;margin-left:6px;">
                    <i class="fas fa-copy"></i> Másolás
                </button>
            </span>
        </div>
        <div style="margin-top:16px;">
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px;">Alt szöveg</label>
            <div style="display:flex;gap:8px;">
                <input type="text" id="altInput_${f.id}" value="${f.alt_text || ''}" placeholder="Leíró szöveg a képhez..." style="flex:1;padding:8px 12px;border:2px solid var(--border);border-radius:8px;">
                <button onclick="saveAlt(${f.id})" class="btn btn-primary"><i class="fas fa-save"></i></button>
            </div>
        </div>
    `;
    document.getElementById('mediaDetailModal').classList.add('open');
}

function saveAlt(id) {
    const val = document.getElementById('altInput_' + id)?.value || '';
    const fd  = new FormData();
    fd.append('save_alt', '1');
    fd.append('media_id', id);
    fd.append('alt_text', val);
    fd.append('ajax', '1');
    fd.append('csrf_token', '<?= csrf_token() ?>');
    fetch('media.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(() => showFlash('Alt szöveg mentve!'));
}
</script>

<?php require_once 'partials/footer.php'; ?>