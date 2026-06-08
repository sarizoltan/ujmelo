<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/shortcodes.php';

// Nyitvatartás
$working_hours = $pdo->query("SELECT * FROM working_hours WHERE staff_id=1 ORDER BY day_of_week ASC")->fetchAll();
$days_hu = ['Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat','Vasárnap'];

// Mai nap kiemeléshez (0=Hétfő, 6=Vasárnap)
$today = (date('N') - 1); // PHP N: 1=Hétfő, 7=Vasárnap → 0-6

// Beállítások
$site_name    = get_setting('site_name', 'Műkörmös Szalon');
$site_address = get_setting('site_address', '');
$site_phone   = get_setting('site_phone', '');
$site_email   = get_setting('site_email', '');
$map_type     = get_setting('map_type', 'none');
$osm_lat      = get_setting('osm_lat', '47.4979');
$osm_lng      = get_setting('osm_lng', '19.0402');
$osm_zoom     = get_setting('osm_zoom', '16');
$osm_height   = get_setting('osm_height', '400');
$osm_label    = get_setting('osm_marker_label', $site_name);
$google_embed = get_setting('google_maps_embed', '');
$contact_title     = get_setting('contact_title', 'Kapcsolat & Nyitvatartás');
$contact_label     = get_setting('contact_label', 'Kapcsolat');
$contact_form_title = get_setting('contact_form_title', 'Kérj időpontot vagy írj nekünk!');
$contact_success   = get_setting('contact_success_msg', 'Üzeneted megkaptuk! Hamarosan felvesszük veled a kapcsolatot.');
$hours_extra  = get_setting('hours_extra', '');
$hours_note   = get_setting('hours_note', '');

// Közösségi média
$social_facebook  = get_setting('social_facebook', '');
$social_instagram = get_setting('social_instagram', '');
$social_tiktok    = get_setting('social_tiktok', '');
$social_youtube   = get_setting('social_youtube', '');
$social_twitter   = get_setting('social_twitter', '');
$social_whatsapp  = get_setting('social_whatsapp', '');

$page_title = 'Kapcsolat – ' . $site_name;
require_once 'templates/header.php';
?>

<!-- ── HERO ── -->
<section class="page-hero">
    <div class="container">
        <div class="page-hero-content reveal">
            <span class="section-label"><?= e($contact_label) ?></span>
            <h1><?= e($contact_title) ?></h1>
            <div class="breadcrumb">
                <a href="<?= BASE_URL ?>/">Főoldal</a>
                <i class="fas fa-chevron-right"></i>
                <span>Kapcsolat</span>
            </div>
        </div>
    </div>
</section>

<!-- ── FŐ TARTALOM ── -->
<section class="section section-darker">
    <div class="container">
        <div class="contact-grid">

            <!-- ── BAL: info + nyitvatartás ── -->
            <div class="reveal">

                <!-- Elérhetőségek -->
                <div class="contact-info-block">
                    <?php if ($site_address): ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h4>Cím</h4>
                            <p><?= e($site_address) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($site_phone): ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <h4>Telefon</h4>
                            <a href="tel:<?= e($site_phone) ?>"><?= e($site_phone) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($site_email): ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h4>Email</h4>
                            <a href="mailto:<?= e($site_email) ?>"><?= e($site_email) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Közösségi média -->
                <?php
                $socials = array_filter([
                    ['url' => $social_facebook,  'icon' => 'fab fa-facebook',  'label' => 'Facebook'],
                    ['url' => $social_instagram, 'icon' => 'fab fa-instagram', 'label' => 'Instagram'],
                    ['url' => $social_tiktok,    'icon' => 'fab fa-tiktok',    'label' => 'TikTok'],
                    ['url' => $social_youtube,   'icon' => 'fab fa-youtube',   'label' => 'YouTube'],
                    ['url' => $social_twitter,   'icon' => 'fab fa-x-twitter', 'label' => 'X'],
                    ['url' => $social_whatsapp,  'icon' => 'fab fa-whatsapp',  'label' => 'WhatsApp'],
                ], fn($s) => !empty($s['url']));
                ?>
                <?php if ($socials): ?>
                <div class="contact-social">
                    <?php foreach ($socials as $s): ?>
                    <a href="<?= e($s['url']) ?>" target="_blank"
                       rel="noopener" class="contact-social-btn" title="<?= e($s['label']) ?>">
                        <i class="<?= $s['icon'] ?>"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Nyitvatartás -->
                <div class="hours-card">
                    <h3>
                        <i class="fas fa-clock"></i> Nyitvatartás
                    </h3>
                    <div class="hours-table">
                        <?php foreach ($working_hours as $wh): ?>
                        <div class="hours-row <?= $wh['day_of_week'] == $today ? 'today' : '' ?>">
                            <span class="hours-day">
                                <?= $days_hu[$wh['day_of_week']] ?>
                                <?php if ($wh['day_of_week'] == $today): ?>
                                    <span class="today-badge">Ma</span>
                                <?php endif; ?>
                            </span>
                            <?php if ($wh['is_day_off']): ?>
                                <span class="hours-closed">Zárva</span>
                            <?php else: ?>
                                <span class="hours-time">
                                    <?= substr($wh['start_time'], 0, 5) ?>
                                    –
                                    <?= substr($wh['end_time'], 0, 5) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($hours_extra): ?>
                    <div class="hours-extra">
                        <i class="fas fa-info-circle"></i> <?= e($hours_extra) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($hours_note): ?>
                    <div class="hours-note">
                        <i class="fas fa-calendar-check"></i> <?= e($hours_note) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── JOBB: Kapcsolati form ── -->
            <div class="contact-form-wrap reveal">
                <h3 class="contact-form-title"><?= e($contact_form_title) ?></h3>

                <div class="form-success" id="contactSuccess">
                    <i class="fas fa-check-circle"></i>
                    <?= e($contact_success) ?>
                </div>
                <p class="form-error" id="contactError"></p>

                <form id="contactForm">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Neved *</label>
                            <input type="text" name="name"
                                   placeholder="Kovács János" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email"
                                   placeholder="email@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Telefonszám</label>
                        <input type="tel" name="phone"
                               placeholder="+36 30 123 4567">
                    </div>
                    <div class="form-group">
                        <label>Üzenet *</label>
                        <textarea name="message" rows="5"
                                  placeholder="Írd ide az üzeneted..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-gold" style="width:100%;">
                        <i class="fas fa-paper-plane"></i> Üzenet küldése
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ── TÉRKÉP ── -->
<?php if ($map_type === 'google' && $google_embed): ?>
<section class="section-map reveal">
    <div class="container">
        <div class="map-wrapper">
            <?= $google_embed ?>
        </div>
    </div>
</section>

<?php elseif ($map_type === 'openstreet' && $osm_lat && $osm_lng): ?>
<section class="section-map reveal">
    <div class="container">
        <div class="map-wrapper">
            <iframe
                src="https://www.openstreetmap.org/export/embed.html?bbox=<?= ($osm_lng-0.01) ?>,<?= ($osm_lat-0.01) ?>,<?= ($osm_lng+0.01) ?>,<?= ($osm_lat+0.01) ?>&layer=mapnik&marker=<?= $osm_lat ?>,<?= $osm_lng ?>"
                width="100%"
                height="<?= (int)$osm_height ?>"
                frameborder="0"
                scrolling="no"
                style="display:block;">
            </iframe>
        </div>
        <p style="text-align:right;font-size:11px;margin-top:6px;">
            <a href="https://www.openstreetmap.org/?mlat=<?= $osm_lat ?>&mlon=<?= $osm_lng ?>#map=<?= $osm_zoom ?>/<?= $osm_lat ?>/<?= $osm_lng ?>"
               target="_blank" style="color:var(--gold);">
                <i class="fas fa-external-link-alt"></i> Nagyobb térkép megtekintése
            </a>
        </p>
    </div>
</section>
<?php endif; ?>

<style>
/* ── Page hero ── */
.page-hero { background:var(--dark-2); padding:80px 0 60px; border-bottom:1px solid var(--border); }
.page-hero-content { text-align:center; }
.page-hero-content h1 { font-family:var(--font-serif); font-size:clamp(36px,5vw,56px); color:var(--white); margin:12px 0; }
.breadcrumb { display:inline-flex; align-items:center; gap:8px; font-size:13px; color:var(--text-muted); margin-top:8px; }
.breadcrumb a { color:var(--text-muted); text-decoration:none; transition:color .2s; }
.breadcrumb a:hover { color:var(--gold); }
.breadcrumb i { font-size:10px; }

/* ── Contact grid ── */
.contact-grid { display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:start; }
@media(max-width:900px) { .contact-grid { grid-template-columns:1fr; gap:32px; } }

/* ── Info blokk ── */
.contact-info-block { display:flex; flex-direction:column; gap:20px; margin-bottom:32px; }
.contact-info-item { display:flex; align-items:flex-start; gap:16px; }
.contact-info-icon { width:44px; height:44px; border-radius:50%; background:rgba(200,169,110,.15); border:1px solid rgba(200,169,110,.3); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:16px; flex-shrink:0; }
.contact-info-item h4 { font-size:12px; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); margin-bottom:4px; }
.contact-info-item p, .contact-info-item a { color:var(--white); font-size:15px; text-decoration:none; transition:color .2s; }
.contact-info-item a:hover { color:var(--gold); }

/* ── Social gombok ── */
.contact-social { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:32px; }
.contact-social-btn { width:40px; height:40px; border-radius:50%; background:var(--dark-3); border:1px solid var(--border); color:var(--text-muted); display:flex; align-items:center; justify-content:center; font-size:16px; text-decoration:none; transition:all .2s; }
.contact-social-btn:hover { background:var(--gold); border-color:var(--gold); color:var(--dark); transform:translateY(-2px); }

/* ── Nyitvatartás kártya ── */
.hours-card { background:var(--dark-3); border:1px solid var(--border); border-radius:var(--radius-lg); padding:28px; }
.hours-card h3 { font-family:var(--font-serif); color:var(--white); font-size:20px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
.hours-card h3 i { color:var(--gold); }
.hours-table { display:flex; flex-direction:column; gap:0; }
.hours-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border); }
.hours-row:last-child { border-bottom:none; }
.hours-row.today { background:rgba(200,169,110,.06); border-radius:6px; padding:10px 10px; margin:0 -10px; }
.hours-day { font-size:14px; color:var(--text); display:flex; align-items:center; gap:8px; }
.hours-time { font-size:14px; color:var(--gold); font-weight:600; }
.hours-closed { font-size:14px; color:var(--text-muted); font-style:italic; }
.today-badge { font-size:10px; background:var(--gold); color:var(--dark); padding:2px 7px; border-radius:20px; font-weight:700; font-style:normal; }
.hours-extra { margin-top:16px; padding:10px 14px; background:rgba(200,169,110,.08); border-radius:6px; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; }
.hours-note  { margin-top:8px; padding:10px 14px; background:rgba(255,255,255,.04); border-radius:6px; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; }

/* ── Kapcsolati form ── */
.contact-form-wrap { background:var(--dark-3); border:1px solid var(--border); border-radius:var(--radius-lg); padding:36px; }
.contact-form-title { font-family:var(--font-serif); color:var(--white); font-size:24px; margin-bottom:24px; }
.form-success { display:none; background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.3); border-radius:8px; padding:14px 18px; color:#4ade80; font-size:14px; margin-bottom:20px; }
.form-success.show { display:flex; align-items:center; gap:10px; }
.form-error { color:#f87171; font-size:13px; min-height:20px; }

/* ── Térkép ── */
.section-map { padding:0 0 60px; }
.map-wrapper { border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--border); }
.map-wrapper iframe { width:100%; display:block; border:none; }
</style>

<?php require_once 'templates/footer.php'; ?>