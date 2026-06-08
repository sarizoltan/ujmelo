<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message = '';
$message_type = 'success';
$active_tab = $_GET['tab'] ?? 'hero';

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_homepage'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $tab = $_POST['tab'] ?? 'hero';

    // Képfeltöltés kezelés
    $image_keys = ['hero_bg_image', 'cta_bg_image'];
    foreach ($image_keys as $img_key) {
        if (!empty($_FILES[$img_key]['name'])) {
            $ext     = strtolower(pathinfo($_FILES[$img_key]['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed) && $_FILES[$img_key]['size'] < 5242880) {
                $filename = $img_key . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES[$img_key]['tmp_name'], UPLOAD_PATH . $filename);
                $_POST[$img_key] = $filename;
            }
        }
    }

    // Összes POST adat mentése settings-be
    $skip = ['csrf_token', 'save_homepage', 'tab'];
    foreach ($_POST as $key => $value) {
        if (in_array($key, $skip)) continue;
        save_setting($key, trim($value));
    }

    $message = 'A főoldal tartalma mentve!';
    $active_tab = $tab;
}

// Összes beállítás betöltése
function hs(string $key, string $default = ''): string {
    return get_setting($key, $default);
}

$tabs = [
    'hero'     => ['icon' => 'fas fa-home',          'label' => 'Hero szekció'],
    'services' => ['icon' => 'fas fa-concierge-bell', 'label' => 'Szolgáltatások'],
    'features' => ['icon' => 'fas fa-star',           'label' => 'Miért mi?'],
    'barbers'  => ['icon' => 'fas fa-user-tie',       'label' => 'Kozmetikusok'],
    'cta'      => ['icon' => 'fas fa-bullhorn',       'label' => 'CTA szekció'],
    'blog'     => ['icon' => 'fas fa-blog',           'label' => 'Blog szekció'],
    'contact'  => ['icon' => 'fas fa-envelope',       'label' => 'Kapcsolat'],
];

$page_title = 'Főoldal szerkesztő';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-check-circle"></i> <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-home"></i> Főoldal szerkesztő</h2>
    <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn-secondary">
        <i class="fas fa-eye"></i> Előnézet
    </a>
</div>

<!-- Szekció tabok -->
<div class="homepage-tabs">
    <?php foreach ($tabs as $tab_key => $tab): ?>
    <a href="homepage.php?tab=<?= $tab_key ?>"
       class="homepage-tab <?= $active_tab === $tab_key ? 'active' : '' ?>">
        <i class="<?= $tab['icon'] ?>"></i>
        <span><?= $tab['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<form method="POST" action="homepage.php?tab=<?= $active_tab ?>"
      enctype="multipart/form-data" id="homepageForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="save_homepage" value="1">
    <input type="hidden" name="tab" value="<?= $active_tab ?>">

    <!-- ══ HERO ══ -->
    <?php if ($active_tab === 'hero'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-heading"></i> Hero szövegek</h3>
                </div>
                <div class="form-group">
                    <label>Felső kis felirat</label>
                    <input type="text" name="hero_label" value="<?= e(hs('hero_label')) ?>"
                           placeholder="pl. Kozmetikai Szalon">
                    <small style="color:var(--text-muted);">A cím felett megjelenő kis szöveg (nagybetűs)</small>
                </div>
                <div class="form-group">
                    <label>Főcím – 1. sor</label>
                    <input type="text" name="hero_title_line1" value="<?= e(hs('hero_title_line1')) ?>"
                           placeholder="pl. Ragyogó arckezelések">
                    <small style="color:var(--text-muted);">Fehér színű sor</small>
                </div>
                <div class="form-group">
                    <label>Főcím – 2. sor <span style="color:var(--accent);">(kiemelő rózsaszín)</span></label>
                    <input type="text" name="hero_title_line2" value="<?= e(hs('hero_title_line2')) ?>"
                           placeholder="pl. természetes szépség minden nap.">
                    <small style="color:var(--text-muted);">Rózsaszín/kiemelő színű sor</small>
                </div>
                <div class="form-group">
                    <label>Alcím szöveg</label>
                    <textarea name="hero_subtitle" rows="3"><?= e(hs('hero_subtitle')) ?></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-mouse-pointer"></i> Gombok</h3>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>1. gomb szövege</label>
                        <input type="text" name="hero_btn1_text" value="<?= e(hs('hero_btn1_text')) ?>">
                    </div>
                    <div class="form-group">
                        <label>1. gomb URL</label>
                        <input type="text" name="hero_btn1_url" value="<?= e(hs('hero_btn1_url')) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>2. gomb szövege</label>
                        <input type="text" name="hero_btn2_text" value="<?= e(hs('hero_btn2_text')) ?>">
                    </div>
                    <div class="form-group">
                        <label>2. gomb URL</label>
                        <input type="text" name="hero_btn2_url" value="<?= e(hs('hero_btn2_url')) ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Statisztikák</h3>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>1. szám</label>
                        <input type="text" name="hero_stat1_num" value="<?= e(hs('hero_stat1_num')) ?>" placeholder="10+">
                    </div>
                    <div class="form-group">
                        <label>1. felirat</label>
                        <input type="text" name="hero_stat1_label" value="<?= e(hs('hero_stat1_label')) ?>" placeholder="Év tapasztalat">
                    </div>
                    <div class="form-group">
                        <label style="color:var(--text-muted);font-size:12px;">2. szám (auto: kozmetikusok száma)</label>
                        <input type="text" name="hero_stat2_label" value="<?= e(hs('hero_stat2_label')) ?>" placeholder="Kozmetikus">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>3. szám</label>
                        <input type="text" name="hero_stat3_num" value="<?= e(hs('hero_stat3_num')) ?>" placeholder="500+">
                    </div>
                    <div class="form-group">
                        <label>3. felirat</label>
                        <input type="text" name="hero_stat3_label" value="<?= e(hs('hero_stat3_label')) ?>" placeholder="Elégedett ügyfél">
                    </div>
                </div>
            </div>
        </div>

        <div class="hp-editor-sidebar">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-image"></i> Háttérkép</h3></div>
                <?php $hero_img = hs('hero_bg_image'); ?>
                <?php if ($hero_img): ?>
                <div style="margin-bottom:12px;">
                    <img src="<?= UPLOAD_URL . e($hero_img) ?>"
                         style="width:100%;border-radius:8px;object-fit:cover;max-height:140px;" alt="">
                </div>
                <?php else: ?>
                <div class="hp-img-placeholder">
                    <i class="fas fa-image"></i>
                    <span>Nincs kép feltöltve</span>
                    <small>Az alapértelmezett háttér aktív</small>
                </div>
                <?php endif; ?>
                <input type="file" name="hero_bg_image" accept="image/jpeg,image/png,image/webp" style="margin-top:10px;">
                <small style="color:var(--text-muted);display:block;margin-top:6px;">Max 5MB – JPG, PNG, WEBP<br>Ajánlott: 1920×1080px</small>
                <?php if ($hero_img): ?>
                <label class="checkbox-label" style="margin-top:10px;">
                    <input type="checkbox" name="hero_bg_image_remove" value="1"
                           onchange="if(this.checked) document.querySelector('[name=hero_bg_image_val]').value=''">
                    Kép eltávolítása
                </label>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header"><h3><i class="fas fa-save"></i> Mentés</h3></div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Hero szekció mentése
                </button>
                <a href="<?= BASE_URL ?>/#home" target="_blank"
                   class="btn btn-secondary" style="width:100%;margin-top:8px;text-align:center;">
                    <i class="fas fa-eye"></i> Előnézet
                </a>
            </div>
        </div>
    </div>

    <!-- ══ SZOLGÁLTATÁSOK ══ -->
    <?php elseif ($active_tab === 'services'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-concierge-bell"></i> Szolgáltatások szekció</h3></div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="services_show" value="1"
                               <?= hs('services_show','1') === '1' ? 'checked' : '' ?>>
                        <strong>Szekció megjelenítése a főoldalon</strong>
                    </label>
                </div>
                <div class="form-group">
                    <label>Felső felirat</label>
                    <input type="text" name="services_label" value="<?= e(hs('services_label')) ?>">
                </div>
                <div class="form-group">
                    <label>Szekció főcím</label>
                    <input type="text" name="services_title" value="<?= e(hs('services_title')) ?>">
                    <small style="color:var(--text-muted);">Az utolsó szó arany színnel jelenik meg</small>
                </div>
                <div class="form-group">
                    <label>Alcím / leírás</label>
                    <textarea name="services_subtitle" rows="3"><?= e(hs('services_subtitle')) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Megjelenítendő szolgáltatások száma</label>
                    <select name="services_limit">
                        <?php foreach ([3,4,6,8,12] as $n): ?>
                        <option value="<?= $n ?>" <?= hs('services_limit','6') == $n ? 'selected':'' ?>><?= $n ?> db</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Aktív szolgáltatások</h3>
                    <a href="services.php" class="btn-sm"><i class="fas fa-edit"></i> Szerkesztés</a>
                </div>
                <?php
                $all_svcs = $pdo->query("SELECT * FROM services WHERE active=1 ORDER BY sort_order LIMIT 12")->fetchAll();
                ?>
                <table class="admin-table">
                    <thead><tr><th>Név</th><th>Ár</th><th>Időtartam</th><th>Kategória</th></tr></thead>
                    <tbody>
                        <?php foreach ($all_svcs as $sv): ?>
                        <tr>
                            <td><?= e($sv['name']) ?></td>
                            <td><?= number_format($sv['price'],0,',',' ') ?> Ft</td>
                            <td><?= $sv['duration'] ?> perc</td>
                            <td><?= e($sv['category']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="font-size:12px;color:var(--text-muted);margin-top:10px;">
                    <i class="fas fa-info-circle"></i> A listán szereplő szolgáltatásokat a
                    <a href="services.php">Szolgáltatások</a> menüpontban szerkesztheted.
                </p>
            </div>
        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
            <div class="card hp-preview-card">
                <div class="card-header"><h3><i class="fas fa-eye"></i> Előnézet</h3></div>
                <div class="hp-section-preview">
                    <div class="preview-label" id="prev_services_label"><?= e(hs('services_label')) ?></div>
                    <div class="preview-title" id="prev_services_title"><?= e(hs('services_title')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ FEATURES ══ -->
    <?php elseif ($active_tab === 'features'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-star"></i> Miért mi? szekció</h3></div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="features_show" value="1"
                               <?= hs('features_show','1') === '1' ? 'checked' : '' ?>>
                        <strong>Szekció megjelenítése</strong>
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Felső felirat</label>
                        <input type="text" name="features_label" value="<?= e(hs('features_label')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Főcím</label>
                        <input type="text" name="features_title" value="<?= e(hs('features_title')) ?>">
                    </div>
                </div>
            </div>

            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-puzzle-piece"></i> <?= $i ?>. elem</h3>
                    <div class="icon-preview">
                        <i class="<?= e(hs("feature{$i}_icon", 'fas fa-star')) ?>" id="iconPreview<?= $i ?>"></i>
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Font Awesome ikon</label>
                        <input type="text" name="feature<?= $i ?>_icon"
                               value="<?= e(hs("feature{$i}_icon")) ?>"
                               placeholder="fas fa-medal"
                               oninput="updateIcon(<?= $i ?>, this.value)">
                        <small><a href="https://fontawesome.com/icons" target="_blank">Ikonok böngészése →</a></small>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Cím</label>
                        <input type="text" name="feature<?= $i ?>_title"
                               value="<?= e(hs("feature{$i}_title")) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Leírás szövege</label>
                    <textarea name="feature<?= $i ?>_text" rows="2"><?= e(hs("feature{$i}_text")) ?></textarea>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </div>
    </div>

    <!-- ══ BORBÉLYOK ══ -->
    <?php elseif ($active_tab === 'barbers'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-user-tie"></i> Kozmetikusok szekció</h3></div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="barbers_show" value="1"
                               <?= hs('barbers_show','1') === '1' ? 'checked' : '' ?>>
                        <strong>Szekció megjelenítése</strong>
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Felső felirat</label>
                        <input type="text" name="barbers_label" value="<?= e(hs('barbers_label')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Megjelenítendő kozmetikusok</label>
                        <select name="barbers_limit">
                            <?php foreach ([2,3,4,6,8] as $n): ?>
                            <option value="<?= $n ?>" <?= hs('barbers_limit','4') == $n ? 'selected':'' ?>><?= $n ?> fő</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Főcím</label>
                    <input type="text" name="barbers_title" value="<?= e(hs('barbers_title')) ?>">
                </div>
                <div class="form-group">
                    <label>Alcím</label>
                    <textarea name="barbers_subtitle" rows="2"><?= e(hs('barbers_subtitle')) ?></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Aktív kozmetikusok</h3>
                    <a href="staff.php" class="btn-sm"><i class="fas fa-edit"></i> Szerkesztés</a>
                </div>
                <?php $all_staff = $pdo->query("SELECT * FROM staff WHERE active=1 ORDER BY sort_order LIMIT 10")->fetchAll(); ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-top:4px;">
                    <?php foreach ($all_staff as $st): ?>
                    <div style="background:#f9fafb;border-radius:8px;padding:12px;text-align:center;border:1px solid var(--border);">
                        <?php if ($st['photo']): ?>
                            <img src="<?= UPLOAD_URL . e($st['photo']) ?>"
                                 style="width:48px;height:48px;border-radius:50%;object-fit:cover;margin:0 auto 8px;" alt="">
                        <?php else: ?>
                            <div style="width:48px;height:48px;border-radius:50%;background:#e0e0e0;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:20px;color:#999;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <strong style="font-size:13px;display:block;"><?= e($st['name']) ?></strong>
                        <span class="badge badge-confirmed" style="font-size:10px;margin-top:4px;">Aktív</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin-top:12px;">
                    <i class="fas fa-info-circle"></i> A kozmetikusokat a <a href="staff.php">Kozmetikusok</a> menüpontban kezelheted.
                </p>
            </div>
        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </div>
    </div>

    <!-- ══ CTA ══ -->
    <?php elseif ($active_tab === 'cta'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-bullhorn"></i> CTA szekció</h3></div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="cta_show" value="1"
                               <?= hs('cta_show','1') === '1' ? 'checked' : '' ?>>
                        <strong>Szekció megjelenítése</strong>
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Felső felirat</label>
                        <input type="text" name="cta_label" value="<?= e(hs('cta_label')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Főcím</label>
                        <input type="text" name="cta_title" value="<?= e(hs('cta_title')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Szöveg</label>
                    <textarea name="cta_text" rows="3"><?= e(hs('cta_text')) ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>1. gomb szövege</label>
                        <input type="text" name="cta_btn1_text" value="<?= e(hs('cta_btn1_text')) ?>">
                    </div>
                    <div class="form-group">
                        <label>1. gomb URL</label>
                        <input type="text" name="cta_btn1_url" value="<?= e(hs('cta_btn1_url')) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>2. gomb szövege</label>
                        <input type="text" name="cta_btn2_text" value="<?= e(hs('cta_btn2_text')) ?>">
                    </div>
                    <div class="form-group">
                        <label>2. gomb URL <small style="color:var(--text-muted);">(pl. tel:+36...)</small></label>
                        <input type="text" name="cta_btn2_url" value="<?= e(hs('cta_btn2_url')) ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3><i class="fas fa-image"></i> CTA háttérkép</h3></div>
                <?php $cta_img = hs('cta_bg_image'); ?>
                <?php if ($cta_img): ?>
                <img src="<?= UPLOAD_URL . e($cta_img) ?>"
                     style="width:100%;border-radius:8px;object-fit:cover;max-height:120px;margin-bottom:12px;" alt="">
                <?php else: ?>
                <div class="hp-img-placeholder">
                    <i class="fas fa-image"></i><span>Nincs kép feltöltve</span>
                </div>
                <?php endif; ?>
                <input type="file" name="cta_bg_image" accept="image/jpeg,image/png,image/webp" style="margin-top:10px;">
                <small style="color:var(--text-muted);display:block;margin-top:6px;">Max 5MB – JPG, PNG, WEBP</small>
            </div>
        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </div>
    </div>

    <!-- ══ BLOG ══ -->
    <?php elseif ($active_tab === 'blog'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-blog"></i> Blog szekció</h3></div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="blog_show" value="1"
                               <?= hs('blog_show','1') === '1' ? 'checked' : '' ?>>
                        <strong>Szekció megjelenítése</strong>
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Felső felirat</label>
                        <input type="text" name="blog_label" value="<?= e(hs('blog_label')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Főcím</label>
                        <input type="text" name="blog_title" value="<?= e(hs('blog_title')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Megjelenítendő bejegyzések</label>
                    <select name="blog_limit">
                        <?php foreach ([2,3,4,6] as $n): ?>
                        <option value="<?= $n ?>" <?= hs('blog_limit','3') == $n ? 'selected':'' ?>><?= $n ?> db</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-newspaper"></i> Legutóbbi bejegyzések</h3>
                    <a href="posts.php" class="btn-sm"><i class="fas fa-edit"></i> Szerkesztés</a>
                </div>
                <?php
                $recent_posts = $pdo->query("SELECT title, status, published_at FROM posts ORDER BY created_at DESC LIMIT 5")->fetchAll();
                ?>
                <?php if ($recent_posts): ?>
                <table class="admin-table">
                    <thead><tr><th>Cím</th><th>Státusz</th><th>Dátum</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent_posts as $rp): ?>
                        <tr>
                            <td><?= e(mb_substr($rp['title'],0,40)) ?>...</td>
                            <td><span class="badge badge-<?= $rp['status'] ?>"><?= $rp['status']==='published'?'Publikus':'Piszkozat' ?></span></td>
                            <td style="font-size:12px;"><?= $rp['published_at'] ? date('Y.m.d',strtotime($rp['published_at'])) : '–' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state"><i class="fas fa-blog"></i><p>Még nincs bejegyzés.</p></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </div>
    </div>

    <!-- ══ KAPCSOLAT ══ -->
    <?php elseif ($active_tab === 'contact'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-envelope"></i> Kapcsolat szekció</h3></div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="contact_show" value="1"
                               <?= hs('contact_show','1') === '1' ? 'checked' : '' ?>>
                        <strong>Szekció megjelenítése</strong>
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Felső felirat</label>
                        <input type="text" name="contact_label" value="<?= e(hs('contact_label')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Főcím</label>
                        <input type="text" name="contact_title" value="<?= e(hs('contact_title')) ?>">
                    </div>
                </div>
                <div class="settings-info-box" style="margin-top:8px;">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        A kapcsolati adatok (cím, telefon, email) a
                        <a href="settings.php">Beállítások → Általános</a> menüpontban szerkeszthetők.
                        A Google Maps kódot a <a href="settings.php?tab=social">Közösségi média</a> fülön adhatod meg.
                    </div>
                </div>
            </div>
        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

</form>

<style>
.homepage-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:24px; }
.homepage-tab { display:flex; align-items:center; gap:8px; padding:10px 18px; border-radius:8px; border:2px solid var(--border); background:var(--white); color:var(--text-light); text-decoration:none; font-size:13px; font-weight:600; transition:all .2s; }
.homepage-tab:hover { border-color:var(--accent); color:var(--accent); }
.homepage-tab.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.homepage-tab i { font-size:14px; }
.hp-editor-layout { display:grid; grid-template-columns:1fr 280px; gap:24px; align-items:start; }
@media(max-width:900px) { .hp-editor-layout { grid-template-columns:1fr; } }
.hp-img-placeholder { border:2px dashed var(--border); border-radius:8px; padding:24px; text-align:center; color:var(--text-muted); display:flex; flex-direction:column; align-items:center; gap:6px; }
.hp-img-placeholder i { font-size:32px; opacity:.4; }
.hp-img-placeholder small { font-size:11px; }
.hp-section-preview { background:#f9fafb; border-radius:8px; padding:16px; text-align:center; }
.preview-label { font-size:11px; text-transform:uppercase; letter-spacing:2px; color:var(--accent); margin-bottom:6px; }
.preview-title { font-size:16px; font-weight:700; color:var(--primary); }
.icon-preview { width:36px; height:36px; border-radius:50%; background:rgba(233,69,96,.1); display:flex; align-items:center; justify-content:center; color:var(--accent); font-size:16px; }
.settings-info-box { display:flex; gap:12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:14px 16px; font-size:13px; color:#1e40af; }
.settings-info-box a { color:var(--accent); }
</style>

<script>
function updateIcon(num, iconClass) {
    const preview = document.getElementById('iconPreview' + num);
    if (preview) {
        preview.className = iconClass;
    }
}

// Élő előnézet frissítés
document.querySelectorAll('[name="services_label"]').forEach(el => {
    el.addEventListener('input', () => {
        const prev = document.getElementById('prev_services_label');
        if (prev) prev.textContent = el.value;
    });
});
document.querySelectorAll('[name="services_title"]').forEach(el => {
    el.addEventListener('input', () => {
        const prev = document.getElementById('prev_services_title');
        if (prev) prev.textContent = el.value;
    });
});
</script>

<?php require_once 'partials/footer.php'; ?>