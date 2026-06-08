<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message = '';
$message_type = 'success';

// ── JELSZÓ VÁLTOZTATÁS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!csrf_verify()) die('CSRF hiba');
    $current  = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $admin = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $admin->execute([current_admin()['id']]);
    $admin = $admin->fetch();

    if (!password_verify($current, $admin['password_hash'])) {
        $message = 'A jelenlegi jelszó helytelen!';
        $message_type = 'error';
    } elseif (strlen($new_pass) < 6) {
        $message = 'Az új jelszónak legalább 6 karakternek kell lennie!';
        $message_type = 'error';
    } elseif ($new_pass !== $confirm) {
        $message = 'A két jelszó nem egyezik!';
        $message_type = 'error';
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")
            ->execute([$hash, current_admin()['id']]);
        $message = 'Jelszó sikeresen megváltoztatva!';
    }
}

// ── BEÁLLÍTÁSOK MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $keys = [
        'site_name','site_tagline','site_email','site_phone','site_address',
        'booking_interval','booking_advance_days','booking_open_time','booking_close_time',
        'facebook_url','instagram_url','google_maps_embed',
        'meta_description','footer_text'
    ];

    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            save_setting($key, trim($_POST[$key]));
        }
    }

    // Logo feltöltés
    if (!empty($_FILES['site_logo']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','svg'];
        if (in_array($ext, $allowed) && $_FILES['site_logo']['size'] < 2097152) {
            $filename = 'logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['site_logo']['tmp_name'], UPLOAD_PATH . $filename);
            save_setting('site_logo', $filename);
        }
    }

    // Favicon feltöltés
    if (!empty($_FILES['site_favicon']['name'])) {
        $ext = strtolower(pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['ico','png']) && $_FILES['site_favicon']['size'] < 512000) {
            $filename = 'favicon_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['site_favicon']['tmp_name'], UPLOAD_PATH . $filename);
            save_setting('site_favicon', $filename);
        }
    }

    $message = 'Beállítások elmentve!';
}

// Beállítások betöltése
$settings_keys = [
    'site_name','site_tagline','site_email','site_phone','site_address',
    'site_logo','site_favicon',
    'booking_interval','booking_advance_days','booking_open_time','booking_close_time',
    'facebook_url','instagram_url','google_maps_embed',
    'meta_description','footer_text'
];
$s = [];
foreach ($settings_keys as $key) {
    $s[$key] = get_setting($key);
}

$page_title = 'Beállítások';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-cog"></i> Beállítások</h2>
</div>

<!-- Fül navigáció -->
<div class="settings-tabs">
    <button class="settings-tab active" data-tab="general">
        <i class="fas fa-globe"></i> Általános
    </button>
    <button class="settings-tab" data-tab="booking">
        <i class="fas fa-calendar-alt"></i> Foglalás
    </button>
    <button class="settings-tab" data-tab="social">
        <i class="fas fa-share-alt"></i> Közösségi média
    </button>
    <button class="settings-tab" data-tab="seo">
        <i class="fas fa-search"></i> SEO
    </button>
    <button class="settings-tab" data-tab="security">
        <i class="fas fa-shield-alt"></i> Biztonság
    </button>
</div>

<form method="POST" action="settings.php" enctype="multipart/form-data" id="settingsForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="save_settings" value="1">

    <!-- ── ÁLTALÁNOS ── -->
    <div class="settings-panel active" id="tab-general">
        <div class="settings-grid">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-info-circle"></i> Az oldal adatai</h3></div>
                <div class="form-group">
                    <label>Oldal neve *</label>
                    <input type="text" name="site_name" value="<?= e($s['site_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Alcím / Szlogen</label>
                    <input type="text" name="site_tagline" value="<?= e($s['site_tagline']) ?>"
                           placeholder="pl. Prémium hajvágás és borotválkozás">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email cím</label>
                        <input type="email" name="site_email" value="<?= e($s['site_email']) ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Telefon</label>
                        <input type="text" name="site_phone" value="<?= e($s['site_phone']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Cím</label>
                    <input type="text" name="site_address" value="<?= e($s['site_address']) ?>"
                           placeholder="1061 Budapest, Andrássy út 1.">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Lábléc szöveg</label>
                    <input type="text" name="footer_text" value="<?= e($s['footer_text']) ?>">
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3><i class="fas fa-image"></i> Logó és Favicon</h3></div>
                <div class="form-group">
                    <label>Logó</label>
                    <?php if ($s['site_logo']): ?>
                    <div style="margin-bottom:10px;">
                        <img src="<?= UPLOAD_URL . e($s['site_logo']) ?>"
                             style="max-height:60px;max-width:200px;object-fit:contain;background:#f5f5f5;padding:8px;border-radius:8px;" alt="Logo">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="site_logo" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                    <small style="color:var(--text-muted);">Max 2MB – JPG, PNG, WEBP, SVG</small>
                </div>
                <div class="form-group">
                    <label>Favicon</label>
                    <?php if ($s['site_favicon']): ?>
                    <div style="margin-bottom:10px;">
                        <img src="<?= UPLOAD_URL . e($s['site_favicon']) ?>"
                             style="width:32px;height:32px;object-fit:contain;" alt="Favicon">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="site_favicon" accept="image/x-icon,image/png">
                    <small style="color:var(--text-muted);">Max 512KB – ICO, PNG (32x32px ajánlott)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ── FOGLALÁS ── -->
    <div class="settings-panel" id="tab-booking">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-calendar-alt"></i> Foglalási rendszer beállításai</h3></div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Időpont intervallum (perc)</label>
                    <select name="booking_interval">
                        <?php foreach ([15,20,30,45,60] as $iv): ?>
                        <option value="<?= $iv ?>" <?= $s['booking_interval'] == $iv ? 'selected':'' ?>>
                            <?= $iv ?> perc
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:var(--text-muted);">Milyen sűrűn generálódnak az időpontok</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-plus"></i> Előre foglalható napok száma</label>
                    <input type="number" name="booking_advance_days"
                           value="<?= e($s['booking_advance_days']) ?>" min="1" max="365">
                    <small style="color:var(--text-muted);">Hány napra előre lehet foglalni</small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-door-open"></i> Alapértelmezett nyitás</label>
                    <input type="time" name="booking_open_time" value="<?= e($s['booking_open_time']) ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-door-closed"></i> Alapértelmezett zárás</label>
                    <input type="time" name="booking_close_time" value="<?= e($s['booking_close_time']) ?>">
                </div>
            </div>

            <div class="settings-info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Megjegyzés:</strong> Az egyéni kozmetikus munkaidőt a
                    <a href="staff.php">Kozmetikusok</a> menüpontban lehet beállítani.
                    Az itt megadott értékek csak alapértelmezettként szolgálnak.
                </div>
            </div>
        </div>
    </div>

    <!-- ── KÖZÖSSÉGI ── -->
    <div class="settings-panel" id="tab-social">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-share-alt"></i> Közösségi média linkek</h3></div>
            <div class="form-group">
                <label><i class="fab fa-facebook" style="color:#1877f2;"></i> Facebook URL</label>
                <input type="url" name="facebook_url" value="<?= e($s['facebook_url']) ?>"
                       placeholder="https://facebook.com/nailsalon">
            </div>
            <div class="form-group">
                <label><i class="fab fa-instagram" style="color:#e4405f;"></i> Instagram URL</label>
                <input type="url" name="instagram_url" value="<?= e($s['instagram_url']) ?>"
                       placeholder="https://instagram.com/nailsalon">
            </div>
            <div class="form-group">
                <label><i class="fab fa-google" style="color:#ea4335;"></i> Google Maps Embed kód</label>
                <textarea name="google_maps_embed" rows="4"
                          placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'><?= e($s['google_maps_embed']) ?></textarea>
                <small style="color:var(--text-muted);">A teljes iframe kódot illeszd be a Google Maps megosztás oldaláról.</small>
            </div>
        </div>
    </div>

    <!-- ── SEO ── -->
    <div class="settings-panel" id="tab-seo">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-search"></i> Globális SEO beállítások</h3></div>
            <div class="form-group">
                <label>Alapértelmezett Meta Description</label>
                <textarea name="meta_description" rows="3"
                          id="globalMetaDesc"
                          placeholder="Az oldal alapértelmezett meta leírása (max 160 karakter)"><?= e($s['meta_description']) ?></textarea>
                <small id="globalMetaCount" style="color:var(--text-muted);">0/160</small>
            </div>

            <div class="settings-info-box">
                <i class="fas fa-lightbulb"></i>
                <div>
                    <strong>Schema.org:</strong> Az oldal automatikusan generálja a
                    <code>HairSalon</code>, <code>Service</code>, <code>BlogPosting</code>
                    és <code>WebPage</code> strukturált adatokat minden oldalon.
                </div>
            </div>
        </div>
    </div>

    <div class="settings-save-bar">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Beállítások mentése
        </button>
    </div>
</form>

<!-- ── BIZTONSÁG (külön form) ── -->
<div class="settings-panel" id="tab-security" style="display:none;">
    <div class="settings-grid">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-key"></i> Jelszó megváltoztatása</h3></div>
            <form method="POST" action="settings.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label>Jelenlegi jelszó</label>
                    <input type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label>Új jelszó</label>
                    <input type="password" name="new_password" required minlength="6"
                           id="newPassInput" autocomplete="new-password">
                    <div class="password-strength" id="passStrength"></div>
                </div>
                <div class="form-group">
                    <label>Új jelszó megerősítése</label>
                    <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-lock"></i> Jelszó megváltoztatása
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h3><i class="fas fa-info-circle"></i> Rendszer információ</h3></div>
            <table class="admin-table">
                <tr><td>PHP verzió</td><td><strong><?= phpversion() ?></strong></td></tr>
                <tr><td>MySQL verzió</td><td><strong><?= $pdo->query('SELECT VERSION()')->fetchColumn() ?></strong></td></tr>
                <tr><td>Max feltöltési méret</td><td><strong><?= ini_get('upload_max_filesize') ?></strong></td></tr>
                <tr><td>Max POST méret</td><td><strong><?= ini_get('post_max_size') ?></strong></td></tr>
                <tr><td>Szerver idő</td><td><strong><?= date('Y-m-d H:i:s') ?></strong></td></tr>
                <tr><td>Upload könyvtár</td>
                    <td>
                        <?php if (is_writable(UPLOAD_PATH)): ?>
                            <span style="color:var(--green);"><i class="fas fa-check"></i> Írható</span>
                        <?php else: ?>
                            <span style="color:var(--red);"><i class="fas fa-times"></i> Nem írható!</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
.settings-tabs { display:flex; gap:4px; margin-bottom:20px; flex-wrap:wrap; }
.settings-tab { padding:9px 18px; border:2px solid var(--border); background:var(--white); border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; color:var(--text-light); transition:all .2s; display:flex; align-items:center; gap:6px; }
.settings-tab:hover { border-color:var(--accent); color:var(--accent); }
.settings-tab.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.settings-panel { display:none; }
.settings-panel.active { display:block; }
.settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
@media(max-width:900px) { .settings-grid { grid-template-columns:1fr; } }
.settings-save-bar { position:sticky; bottom:0; background:var(--white); border-top:1px solid var(--border); padding:16px 0; margin-top:24px; z-index:10; }
.btn-lg { padding:12px 32px; font-size:15px; }
.settings-info-box { display:flex; gap:12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:14px 16px; margin-top:16px; font-size:13px; color:#1e40af; }
.settings-info-box i { font-size:18px; flex-shrink:0; margin-top:2px; }
.settings-info-box a { color:var(--accent); }
.password-strength { height:4px; border-radius:2px; margin-top:6px; transition:all .3s; }
</style>

<script>
// Fül váltás
document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.settings-panel').forEach(p => {
            p.style.display = 'none';
            p.classList.remove('active');
        });
        this.classList.add('active');
        const panel = document.getElementById('tab-' + this.dataset.tab);
        if (panel) {
            panel.style.display = 'block';
            panel.classList.add('active');
        }
    });
});

// Meta description számláló
const globalMeta = document.getElementById('globalMetaDesc');
const globalCount = document.getElementById('globalMetaCount');
if (globalMeta && globalCount) {
    const updateGlobal = () => {
        const len = globalMeta.value.length;
        globalCount.textContent = `${len}/160`;
        globalCount.style.color = len > 160 ? 'var(--red)' : len > 136 ? 'var(--orange)' : 'var(--text-muted)';
    };
    globalMeta.addEventListener('input', updateGlobal);
    updateGlobal();
}

// Jelszó erősség
const newPassInput = document.getElementById('newPassInput');
const passStrength = document.getElementById('passStrength');
if (newPassInput && passStrength) {
    newPassInput.addEventListener('input', function() {
        const val = this.value;
        let strength = 0;
        if (val.length >= 8) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;
        const colors = ['','var(--red)','var(--orange)','var(--blue)','var(--green)'];
        const widths = ['0%','25%','50%','75%','100%'];
        passStrength.style.background = colors[strength] || '#eee';
        passStrength.style.width = val.length ? widths[strength] : '0%';
    });
}
</script>

<?php require_once 'partials/footer.php'; ?>