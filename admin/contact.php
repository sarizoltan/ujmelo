<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message      = '';
$message_type = 'success';
$active_tab   = $_GET['tab'] ?? 'contact';

// ── MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_contact'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $skip = ['csrf_token', 'save_contact', 'tab'];
    foreach ($_POST as $key => $value) {
        if (in_array($key, $skip)) continue;
        save_setting($key, trim($value));
    }

    $message    = 'Kapcsolat oldal mentve!';
    $active_tab = $_POST['tab'] ?? 'contact';
}

function cs(string $key, string $default = ''): string {
    return get_setting($key, $default);
}

$tabs = [
    'contact' => ['icon' => 'fas fa-address-card', 'label' => 'Kapcsolati adatok'],
    'hours'   => ['icon' => 'fas fa-clock',        'label' => 'Nyitvatartás'],
    'map'     => ['icon' => 'fas fa-map',           'label' => 'Térkép'],
    'social'  => ['icon' => 'fas fa-share-alt',     'label' => 'Közösségi média'],
];

$page_title = 'Kapcsolat oldal';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-check-circle"></i> <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-address-card"></i> Kapcsolat oldal szerkesztő</h2>
    <a href="<?= BASE_URL ?>/#contact" target="_blank" class="btn btn-secondary">
        <i class="fas fa-eye"></i> Előnézet
    </a>
</div>

<!-- Tabok -->
<div class="homepage-tabs">
    <?php foreach ($tabs as $tab_key => $tab): ?>
    <a href="contact.php?tab=<?= $tab_key ?>"
       class="homepage-tab <?= $active_tab === $tab_key ? 'active' : '' ?>">
        <i class="<?= $tab['icon'] ?>"></i>
        <span><?= $tab['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<form method="POST" action="contact.php?tab=<?= $active_tab ?>"
      enctype="multipart/form-data" id="contactEditorForm">
    <input type="hidden" name="csrf_token"   value="<?= csrf_token() ?>">
    <input type="hidden" name="save_contact" value="1">
    <input type="hidden" name="tab"          value="<?= $active_tab ?>">

    <!-- ══ KAPCSOLATI ADATOK ══ -->
    <?php if ($active_tab === 'contact'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-address-card"></i> Alap elérhetőségek</h3>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt" style="color:var(--accent);"></i> Cím</label>
                    <input type="text" name="site_address"
                           value="<?= e(cs('site_address')) ?>"
                           placeholder="pl. 1234 Budapest, Példa utca 1.">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone" style="color:var(--accent);"></i> Telefonszám</label>
                        <input type="text" name="site_phone"
                               value="<?= e(cs('site_phone')) ?>"
                               placeholder="pl. +36 30 123 4567">
                        <small style="color:var(--text-muted);">Ezt használja a „Hívj minket" gomb is.</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope" style="color:var(--accent);"></i> Email cím</label>
                        <input type="email" name="site_email"
                               value="<?= e(cs('site_email')) ?>"
                               placeholder="pl. hello@nailsalon.hu">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-heading"></i> Szekció szövegek</h3>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Felső felirat</label>
                        <input type="text" name="contact_label"
                               value="<?= e(cs('contact_label','Elérhetőség')) ?>"
                               placeholder="pl. Elérhetőség">
                    </div>
                    <div class="form-group">
                        <label>Főcím</label>
                        <input type="text" name="contact_title"
                               value="<?= e(cs('contact_title','Nyitvatartás & Kapcsolat')) ?>"
                               placeholder="pl. Nyitvatartás & Kapcsolat">
                    </div>
                </div>
                <div class="form-group">
                    <label>Kapcsolati form fejléc</label>
                    <input type="text" name="contact_form_title"
                           value="<?= e(cs('contact_form_title','Írj nekünk!')) ?>"
                           placeholder="pl. Írj nekünk!">
                </div>
                <div class="form-group">
                    <label>Sikeres küldés üzenet</label>
                    <input type="text" name="contact_success_msg"
                           value="<?= e(cs('contact_success_msg','Üzeneted megkaptuk! Hamarosan felvesszük veled a kapcsolatot.')) ?>">
                </div>
            </div>

        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-eye"></i> Gyors áttekintés</h3></div>
                <div class="contact-quick-preview">
                    <div class="cqp-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= cs('site_address') ?: '<em style="color:#ccc;">Nincs megadva</em>' ?></span>
                    </div>
                    <div class="cqp-row">
                        <i class="fas fa-phone"></i>
                        <span><?= cs('site_phone') ?: '<em style="color:#ccc;">Nincs megadva</em>' ?></span>
                    </div>
                    <div class="cqp-row">
                        <i class="fas fa-envelope"></i>
                        <span><?= cs('site_email') ?: '<em style="color:#ccc;">Nincs megadva</em>' ?></span>
                    </div>
                </div>
            </div>
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </div>
    </div>

    <!-- ══ NYITVATARTÁS ══ -->
    <?php elseif ($active_tab === 'hours'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> Nyitvatartási idők</h3>
                </div>
                <?php
                $days_hu = ['Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat','Vasárnap'];
                $hours   = $pdo->query("SELECT * FROM working_hours WHERE staff_id=1 ORDER BY day_of_week ASC")->fetchAll();
                ?>
                <?php if ($hours): ?>
                <div class="hours-editor">
                    <?php foreach ($hours as $h): ?>
                    <div class="hours-editor-row">
                        <span class="hours-day-label"><?= $days_hu[$h['day_of_week']] ?></span>
                        <label class="toggle-switch">
                            <input type="checkbox"
                                   name="day_open_<?= $h['day_of_week'] ?>"
                                   value="1"
                                   <?= !$h['is_day_off'] ? 'checked' : '' ?>
                                   onchange="toggleDayRow(<?= $h['day_of_week'] ?>, this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                        <div class="hours-time-inputs" id="dayRow_<?= $h['day_of_week'] ?>"
                             style="<?= $h['is_day_off'] ? 'opacity:.4;pointer-events:none;' : '' ?>">
                            <input type="time" name="start_<?= $h['day_of_week'] ?>"
                                   value="<?= substr($h['start_time'],0,5) ?>">
                            <span>–</span>
                            <input type="time" name="end_<?= $h['day_of_week'] ?>"
                                   value="<?= substr($h['end_time'],0,5) ?>">
                        </div>
                        <span class="hours-closed-label" id="closedLabel_<?= $h['day_of_week'] ?>"
                              style="<?= !$h['is_day_off'] ? 'display:none;' : '' ?>">
                            Zárva
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin-top:16px;">
                    <i class="fas fa-info-circle"></i>
                    A kapcsoló kikapcsolása = zárva az a nap.
                </p>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clock"></i>
                    <p>Nincs nyitvatartási adat. Előbb hozz létre legalább egy műkörmöst!</p>
                    <a href="staff.php" class="btn btn-secondary">Műkörmösök kezelése</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Extra nyitvatartás szövegek</h3>
                </div>
                <div class="form-group">
                    <label>Extra info sor <small style="color:var(--text-muted);">(opcionális)</small></label>
                    <input type="text" name="hours_extra"
                           value="<?= e(cs('hours_extra')) ?>"
                           placeholder="pl. Ünnepnapokon zárva tartunk.">
                </div>
                <div class="form-group">
                    <label>Nyitvatartás megjegyzés</label>
                    <textarea name="hours_note" rows="2"
                              placeholder="pl. Online foglalás 0-24 óráig lehetséges."><?= e(cs('hours_note')) ?></textarea>
                </div>
            </div>

        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-eye"></i> Előnézet</h3></div>
                <div class="hours-preview">
                    <?php foreach ($hours as $h): ?>
                    <div class="hours-preview-row <?= $h['is_day_off'] ? 'closed' : '' ?>">
                        <span><?= $days_hu[$h['day_of_week']] ?></span>
                        <span><?= $h['is_day_off'] ? 'Zárva' : substr($h['start_time'],0,5).' – '.substr($h['end_time'],0,5) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </div>
    </div>

    <!-- ══ TÉRKÉP ══ -->
    <?php elseif ($active_tab === 'map'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">

            <div class="card">
                <div class="card-header"><h3><i class="fas fa-map"></i> Térkép típusa</h3></div>

                <div class="map-type-selector">
                    <label class="map-type-option <?= cs('map_type','none') === 'none' ? 'active' : '' ?>">
                        <input type="radio" name="map_type" value="none"
                               <?= cs('map_type','none') === 'none' ? 'checked' : '' ?>
                               onchange="switchMapType('none')">
                        <i class="fas fa-times-circle"></i>
                        <span>Nincs térkép</span>
                    </label>
                    <label class="map-type-option <?= cs('map_type') === 'openstreet' ? 'active' : '' ?>">
                        <input type="radio" name="map_type" value="openstreet"
                               <?= cs('map_type') === 'openstreet' ? 'checked' : '' ?>
                               onchange="switchMapType('openstreet')">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>OpenStreetMap</span>
                        <small>Ingyenes</small>
                    </label>
                    <label class="map-type-option <?= cs('map_type') === 'google' ? 'active' : '' ?>">
                        <input type="radio" name="map_type" value="google"
                               <?= cs('map_type') === 'google' ? 'checked' : '' ?>
                               onchange="switchMapType('google')">
                        <i class="fab fa-google"></i>
                        <span>Google Maps</span>
                        <small>Embed kód</small>
                    </label>
                </div>
            </div>

            <!-- OpenStreetMap -->
            <div id="mapTypeOpenstreet" class="card"
                 style="display:<?= cs('map_type') === 'openstreet' ? 'block' : 'none' ?>;">
                <div class="card-header"><h3><i class="fas fa-map-marked-alt"></i> OpenStreetMap beállítások</h3></div>
                <div class="osm-info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Ingyenes, API kulcs nélkül működik!</strong><br>
                        Keresd meg a helyszínt lent, vagy add meg a koordinátákat kézzel.
                        A koordinátákat megtalálod az
                        <a href="https://www.openstreetmap.org" target="_blank">openstreetmap.org</a> oldalon.
                    </div>
                </div>

                <!-- Koordináta kereső -->
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Cím keresés</label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" id="coordSearch"
                               placeholder="pl. Budapest, Váci út 1."
                               style="flex:1;">
                        <button type="button" class="btn btn-secondary" onclick="searchCoords()">
                            <i class="fas fa-search"></i> Keresés
                        </button>
                    </div>
                </div>
                <div id="coordResult" style="display:none;margin-bottom:16px;">
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;font-size:13px;">
                        <strong>Találat:</strong> <span id="coordAddress"></span><br>
                        Lat: <strong id="coordLat"></strong> | Lng: <strong id="coordLng"></strong>
                        <button type="button" class="btn btn-primary"
                                style="margin-top:8px;width:100%;font-size:12px;"
                                onclick="applyCoords()">
                            <i class="fas fa-check"></i> Koordináták alkalmazása
                        </button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Szélesség (Latitude)</label>
                        <input type="text" name="osm_lat" id="osmLat"
                               value="<?= e(cs('osm_lat','47.4979')) ?>"
                               placeholder="pl. 47.4979">
                    </div>
                    <div class="form-group">
                        <label>Hosszúság (Longitude)</label>
                        <input type="text" name="osm_lng" id="osmLng"
                               value="<?= e(cs('osm_lng','19.0402')) ?>"
                               placeholder="pl. 19.0402">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Zoom szint <small style="color:var(--text-muted);">(ajánlott: 16)</small></label>
                        <input type="number" name="osm_zoom" id="osmZoom"
                               value="<?= e(cs('osm_zoom','16')) ?>"
                               min="1" max="19">
                    </div>
                    <div class="form-group">
                        <label>Térkép magassága (px)</label>
                        <input type="number" name="osm_height"
                               value="<?= e(cs('osm_height','400')) ?>"
                               min="200" max="800" step="50">
                    </div>
                </div>
                <div class="form-group">
                    <label>Marker felirat</label>
                    <input type="text" name="osm_marker_label"
                           value="<?= e(cs('osm_marker_label', cs('site_name','Műkörmös Szalon'))) ?>"
                           placeholder="pl. Műkörmös Szalon">
                </div>
                <button type="button" class="btn btn-secondary" onclick="previewOSM()">
                    <i class="fas fa-eye"></i> Előnézet frissítése
                </button>
                <div id="osmPreview" style="margin-top:14px;border-radius:10px;overflow:hidden;border:1px solid var(--border);display:none;">
                    <iframe id="osmFrame" width="100%" height="300"
                            frameborder="0" scrolling="no" style="display:block;"></iframe>
                </div>
            </div>

            <!-- Google Maps -->
            <div id="mapTypeGoogle" class="card"
                 style="display:<?= cs('map_type') === 'google' ? 'block' : 'none' ?>;">
                <div class="card-header"><h3><i class="fab fa-google"></i> Google Maps beállítások</h3></div>
                <div class="osm-info-box" style="background:#e8f4fd;border-color:#bee3f8;color:#1e40af;">
                    <i class="fab fa-google" style="color:#4285F4;"></i>
                    <div>
                        <strong>Google Maps Embed kód beszúrása:</strong><br>
                        1. Menj a <a href="https://maps.google.com" target="_blank">Google Maps</a> oldalra<br>
                        2. Keresd meg a helyszínt<br>
                        3. Kattints a <strong>Megosztás</strong> → <strong>Térkép beágyazása</strong> gombra<br>
                        4. Másold be az &lt;iframe&gt; kódot
                    </div>
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label>Google Maps iframe kód</label>
                    <textarea name="google_maps_embed" rows="6"
                              placeholder='<iframe src="https://www.google.com/maps/embed?pb=..." width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>'
                              style="font-family:monospace;font-size:12px;"><?= e(cs('google_maps_embed')) ?></textarea>
                    <small style="color:var(--text-muted);">A teljes &lt;iframe&gt; kódot illeszd be.</small>
                </div>
                <?php if (cs('google_maps_embed')): ?>
                <div style="margin-top:12px;border-radius:10px;overflow:hidden;border:1px solid var(--border);">
                    <?= cs('google_maps_embed') ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
        <div class="hp-editor-sidebar">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-map"></i> Jelenlegi térkép</h3></div>
                <?php
                $mt = cs('map_type','none');
                if ($mt === 'openstreet'): ?>
                    <div style="text-align:center;padding:12px;">
                        <i class="fas fa-map-marked-alt" style="font-size:32px;color:green;"></i>
                        <p style="margin-top:8px;font-weight:600;color:green;">OpenStreetMap aktív</p>
                        <small style="color:var(--text-muted);">
                            <?= cs('osm_lat') ?>, <?= cs('osm_lng') ?>
                        </small>
                    </div>
                <?php elseif ($mt === 'google'): ?>
                    <div style="text-align:center;padding:12px;">
                        <i class="fab fa-google" style="font-size:32px;color:#4285F4;"></i>
                        <p style="margin-top:8px;font-weight:600;color:#4285F4;">Google Maps aktív</p>
                    </div>
                <?php else: ?>
                    <div style="text-align:center;padding:12px;color:var(--text-muted);">
                        <i class="fas fa-map" style="font-size:32px;opacity:.3;"></i>
                        <p style="margin-top:8px;">Nincs térkép beállítva</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </div>
    </div>

    <!-- ══ KÖZÖSSÉGI MÉDIA ══ -->
    <?php elseif ($active_tab === 'social'): ?>
    <div class="hp-editor-layout">
        <div class="hp-editor-main">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-share-alt"></i> Közösségi média linkek</h3></div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                    Ezek a linkek a fejlécben, láblécben és a kapcsolat oldalon jelennek meg.
                    Hagyd üresen ha nem szeretnéd megjeleníteni.
                </p>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-facebook" style="color:#1877f2;"></i> Facebook</label>
                        <input type="url" name="social_facebook"
                               value="<?= e(cs('social_facebook')) ?>"
                               placeholder="https://facebook.com/nailsalon">
                    </div>
                    <div class="form-group">
                        <label><i class="fab fa-instagram" style="color:#e4405f;"></i> Instagram</label>
                        <input type="url" name="social_instagram"
                               value="<?= e(cs('social_instagram')) ?>"
                               placeholder="https://instagram.com/nailsalon">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-tiktok"></i> TikTok</label>
                        <input type="url" name="social_tiktok"
                               value="<?= e(cs('social_tiktok')) ?>"
                               placeholder="https://tiktok.com/@nailsalon">
                    </div>
                    <div class="form-group">
                        <label><i class="fab fa-youtube" style="color:#ff0000;"></i> YouTube</label>
                        <input type="url" name="social_youtube"
                               value="<?= e(cs('social_youtube')) ?>"
                               placeholder="https://youtube.com/@nailsalon">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-x-twitter"></i> X (Twitter)</label>
                        <input type="url" name="social_twitter"
                               value="<?= e(cs('social_twitter')) ?>"
                               placeholder="https://x.com/nailsalon">
                    </div>
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp" style="color:#25d366;"></i> WhatsApp</label>
                        <input type="url" name="social_whatsapp"
                               value="<?= e(cs('social_whatsapp')) ?>"
                               placeholder="https://wa.me/36301234567">
                    </div>
                </div>
            </div>

            <!-- Előnézet -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-eye"></i> Ikonok előnézete</h3></div>
                <div class="social-icons-preview">
                    <?php
                    $socials = [
                        'social_facebook'  => ['fab fa-facebook',  '#1877f2'],
                        'social_instagram' => ['fab fa-instagram', '#e4405f'],
                        'social_tiktok'    => ['fab fa-tiktok',    '#000'],
                        'social_youtube'   => ['fab fa-youtube',   '#ff0000'],
                        'social_twitter'   => ['fab fa-x-twitter', '#000'],
                        'social_whatsapp'  => ['fab fa-whatsapp',  '#25d366'],
                    ];
                    foreach ($socials as $key => [$icon, $color]):
                        $url = cs($key);
                    ?>
                    <a href="<?= $url ?: '#' ?>"
                       class="social-preview-btn <?= $url ? 'active' : 'inactive' ?>"
                       style="<?= $url ? "--sc:{$color};" : '' ?>"
                       target="_blank">
                        <i class="<?= $icon ?>"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin-top:10px;">
                    Szürke = nincs megadva URL
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
    <?php endif; ?>

</form>

<!-- ══ NYITVATARTÁS MENTÉS (külön POST) ══ -->
<?php if ($active_tab === 'hours' && !empty($hours)): ?>
<script>
document.getElementById('contactEditorForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const fd = new FormData(this);

    // Nyitvatartás adatok összegyűjtése
    <?php foreach ($hours as $h): ?>
    const isOpen_<?= $h['day_of_week'] ?> = document.querySelector('[name="day_open_<?= $h['day_of_week'] ?>"]')?.checked ?? false;
    const start_<?= $h['day_of_week'] ?>  = document.querySelector('[name="start_<?= $h['day_of_week'] ?>"]')?.value ?? '09:00';
    const end_<?= $h['day_of_week'] ?>    = document.querySelector('[name="end_<?= $h['day_of_week'] ?>"]')?.value ?? '18:00';
    <?php endforeach; ?>

    // AJAX küldés a working_hours frissítéséhez
    fetch('contact_hours_save.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(data => {
        if (data.success) {
            showFlash('Nyitvatartás mentve!');
        }
    });
});
</script>
<?php endif; ?>

<style>
/* Tabok – megegyezik a homepage.php-val */
.homepage-tabs  { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:24px; }
.homepage-tab   { display:flex; align-items:center; gap:8px; padding:10px 18px; border-radius:8px; border:2px solid var(--border); background:var(--white); color:var(--text-light); text-decoration:none; font-size:13px; font-weight:600; transition:all .2s; }
.homepage-tab:hover  { border-color:var(--accent); color:var(--accent); }
.homepage-tab.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.hp-editor-layout { display:grid; grid-template-columns:1fr 280px; gap:24px; align-items:start; }
@media(max-width:900px) { .hp-editor-layout { grid-template-columns:1fr; } }

/* Térkép típus választó */
.map-type-selector   { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:4px; }
.map-type-option     { border:2px solid var(--border); border-radius:8px; padding:14px 10px; text-align:center; cursor:pointer; transition:all .2s; display:flex; flex-direction:column; align-items:center; gap:4px; }
.map-type-option:hover  { border-color:var(--accent); }
.map-type-option.active { border-color:var(--accent); background:#fff5f6; }
.map-type-option input[type=radio] { display:none; }
.map-type-option i    { font-size:22px; color:var(--accent); }
.map-type-option span { font-weight:600; font-size:13px; }
.map-type-option small { font-size:10px; color:var(--text-muted); }

/* OSM info box */
.osm-info-box { display:flex; gap:12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 16px; font-size:13px; color:#166534; margin-bottom:16px; }
.osm-info-box i { font-size:18px; flex-shrink:0; margin-top:2px; }
.osm-info-box a { color:var(--accent); }

/* Gyors áttekintés */
.contact-quick-preview { display:flex; flex-direction:column; gap:12px; }
.cqp-row  { display:flex; gap:10px; align-items:flex-start; font-size:13px; }
.cqp-row i { color:var(--accent); width:16px; flex-shrink:0; margin-top:2px; }
.cqp-row span { color:var(--text); line-height:1.6; }

/* Nyitvatartás szerkesztő */
.hours-editor     { display:flex; flex-direction:column; gap:12px; }
.hours-editor-row { display:flex; align-items:center; gap:14px; padding:10px 0; border-bottom:1px solid var(--border); }
.hours-editor-row:last-child { border-bottom:none; }
.hours-day-label  { width:90px; font-weight:600; font-size:14px; flex-shrink:0; }
.hours-time-inputs { display:flex; align-items:center; gap:8px; }
.hours-time-inputs input[type=time] { padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:14px; }
.hours-closed-label { font-size:13px; color:var(--text-muted); font-style:italic; }
.toggle-switch  { position:relative; display:inline-block; width:44px; height:24px; flex-shrink:0; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider  { position:absolute; cursor:pointer; inset:0; background:#ccc; border-radius:24px; transition:.3s; }
.toggle-slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
.toggle-switch input:checked + .toggle-slider { background:var(--accent); }
.toggle-switch input:checked + .toggle-slider:before { transform:translateX(20px); }

/* Nyitvatartás előnézet */
.hours-preview     { display:flex; flex-direction:column; gap:6px; }
.hours-preview-row { display:flex; justify-content:space-between; font-size:13px; padding:6px 0; border-bottom:1px solid var(--border); }
.hours-preview-row:last-child { border-bottom:none; }
.hours-preview-row.closed span:last-child { color:var(--text-muted); font-style:italic; }

/* Közösségi ikonok előnézet */
.social-icons-preview { display:flex; gap:10px; flex-wrap:wrap; padding:8px 0; }
.social-preview-btn   { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; text-decoration:none; transition:all .2s; }
.social-preview-btn.active   { background:var(--sc, #888); color:#fff; }
.social-preview-btn.inactive { background:#f0f0f0; color:#ccc; }
.social-preview-btn:hover { transform:scale(1.1); }
</style>

<script>
// ── Térkép típus váltás ──
function switchMapType(type) {
    document.getElementById('mapTypeOpenstreet').style.display = type === 'openstreet' ? 'block' : 'none';
    document.getElementById('mapTypeGoogle').style.display     = type === 'google'     ? 'block' : 'none';
    document.querySelectorAll('.map-type-option').forEach(el => {
        el.classList.toggle('active', el.querySelector('input').value === type);
    });
}

// ── OSM előnézet ──
function previewOSM() {
    const lat  = document.getElementById('osmLat')?.value  || '47.4979';
    const lng  = document.getElementById('osmLng')?.value  || '19.0402';
    const url  = `https://www.openstreetmap.org/export/embed.html`
               + `?bbox=${(+lng-.01)},${(+lat-.01)},${(+lng+.01)},${(+lat+.01)}`
               + `&layer=mapnik&marker=${lat},${lng}`;
    document.getElementById('osmFrame').src  = url;
    document.getElementById('osmPreview').style.display = 'block';
}

// ── Koordináta keresés (Nominatim) ──
let foundLat = null, foundLng = null;
async function searchCoords() {
    const q = document.getElementById('coordSearch').value.trim();
    if (!q) return;
    try {
        const res  = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=1`,
                                 { headers:{'Accept-Language':'hu'} });
        const data = await res.json();
        if (data.length) {
            foundLat = parseFloat(data[0].lat).toFixed(6);
            foundLng = parseFloat(data[0].lon).toFixed(6);
            document.getElementById('coordAddress').textContent = data[0].display_name;
            document.getElementById('coordLat').textContent     = foundLat;
            document.getElementById('coordLng').textContent     = foundLng;
            document.getElementById('coordResult').style.display = 'block';
        } else {
            alert('Nem található ilyen cím!');
        }
    } catch { alert('Hálózati hiba.'); }
}

function applyCoords() {
    if (foundLat && foundLng) {
        document.getElementById('osmLat').value = foundLat;
        document.getElementById('osmLng').value = foundLng;
        previewOSM();
    }
}

// ── Napok toggle ──
function toggleDayRow(day, isOpen) {
    const row   = document.getElementById('dayRow_' + day);
    const label = document.getElementById('closedLabel_' + day);
    if (row)   { row.style.opacity = isOpen ? '1' : '0.4'; row.style.pointerEvents = isOpen ? '' : 'none'; }
    if (label) { label.style.display = isOpen ? 'none' : 'inline'; }
}

// ── Auto OSM előnézet ha már be van állítva ──
<?php if (cs('map_type') === 'openstreet' && cs('osm_lat')): ?>
previewOSM();
<?php endif; ?>
</script>

<?php require_once 'partials/footer.php'; ?>