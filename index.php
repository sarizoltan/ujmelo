<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/schema.php';

$page_meta_title = get_setting('site_name') . ' – ' . get_setting('site_tagline');
$page_meta_desc  = get_setting('meta_description');
$page_schema     = schema_local_business();

// Aktív szolgáltatások
$services = $pdo->query("SELECT * FROM services WHERE active=1 ORDER BY sort_order ASC LIMIT 6")->fetchAll();

// Aktív kozmetikusok
$staff = $pdo->query("SELECT * FROM staff WHERE active=1 ORDER BY sort_order ASC LIMIT 4")->fetchAll();

// Legutóbbi blog bejegyzések
$posts = $pdo->query("SELECT * FROM posts WHERE status='published' ORDER BY published_at DESC LIMIT 3")->fetchAll();

// Munkaidő
$working_hours = $pdo->query("SELECT * FROM working_hours WHERE staff_id=1 ORDER BY day_of_week ASC")->fetchAll();

$days_hu = ['Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat','Vasárnap'];

require_once 'templates/header.php';
?>

<script>window._BASE_URL = '<?= BASE_URL ?>';</script>

<!-- ══ HERO ══ -->
<section class="hero" id="home">
    <?php $hero_img = get_setting('hero_bg_image'); ?>
    <div class="hero-bg" <?= $hero_img ? 'style="background-image:url(\''.UPLOAD_URL.e($hero_img).'\')"' : '' ?>></div>
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-label"><?= e(get_setting('hero_label','Kozmetikai Szalon')) ?></div>
            <h1 class="hero-title">
                <?= e(get_setting('hero_title_line1','Ragyogó arckezelések')) ?>
                <span><?= e(get_setting('hero_title_line2','természetes szépség minden nap.')) ?></span>
            </h1>
            <p class="hero-subtitle"><?= e(get_setting('hero_subtitle','')) ?></p>
            <div class="hero-buttons">
                <a href="<?= e(get_setting('hero_btn1_url', BASE_URL.'/foglalas')) ?>" class="btn btn-gold btn-lg">
                    <i class="fas fa-calendar-check"></i>
                    <?= e(get_setting('hero_btn1_text','Időpontfoglalás')) ?>
                </a>
                <a href="<?= e(get_setting('hero_btn2_url','#services')) ?>" class="btn btn-outline btn-lg">
                    <i class="fas fa-hand-sparkles"></i>
                    <?= e(get_setting('hero_btn2_text','Kezelések')) ?>
                </a>
            </div>
            <div class="hero-stats">
                <div>
                    <span class="hero-stat-number"><?= e(get_setting('hero_stat1_num','10+')) ?></span>
                    <span class="hero-stat-label"><?= e(get_setting('hero_stat1_label','Év tapasztalat')) ?></span>
                </div>
                <div>
                    <span class="hero-stat-number"><?= count($staff) ?>+</span>
                    <span class="hero-stat-label"><?= e(get_setting('hero_stat2_label','Kozmetikus')) ?></span>
                </div>
                <div>
                    <span class="hero-stat-number"><?= e(get_setting('hero_stat3_num','500+')) ?></span>
                    <span class="hero-stat-label"><?= e(get_setting('hero_stat3_label','Elégedett ügyfél')) ?></span>
                </div>
            </div>
        </div>
    </div>
    <a href="#services" class="hero-scroll">
        <i class="fas fa-chevron-down"></i>
        <span>Görgess</span>
    </a>
</section>

<!-- ══ SZOLGÁLTATÁSOK ══ -->
<section class="section section-darker" id="services">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">Kezelések</span>
            <h2 class="section-title">Elegáns <span>Szalonkezelések</span></h2>
            <p class="section-subtitle">Minden kezelésünket prémium anyagokkal és kifinomult technikával végezzük.</p>
            <div class="divider">
                <div class="divider-line"></div>
                <i class="fas fa-hand-sparkles divider-icon"></i>
                <div class="divider-line"></div>
            </div>
        </div>

        <?php
        $service_icons = [
            'Arckezelés'    => 'fas fa-spa',
            'Bőrfiatalítás' => 'fas fa-leaf',
            'Smink'         => 'fas fa-brush',
            'Szemöldök'     => 'fas fa-eye',
            'Prémium'       => 'fas fa-crown',
            'default'       => 'fas fa-spa',
        ];
        ?>

        <div class="services-grid">
            <?php foreach ($services as $i => $svc): ?>
            <?php
            $icon = $service_icons[$svc['category']] ?? $service_icons['default'];
            ?>
            <div class="service-card reveal reveal-delay-<?= min($i + 1, 4) ?>">
                <div class="service-icon">
                    <i class="<?= $icon ?>"></i>
                </div>
                <h3><?= e($svc['name']) ?></h3>
                <p><?= e($svc['description'] ?: 'Prémium kozmetikai kezelés tapasztalt kozmetikusoktól.') ?></p>
                <div class="service-meta">
                    <span class="service-price"><?= number_format($svc['price'], 0, ',', ' ') ?> Ft</span>
                    <span class="service-duration">
                        <i class="fas fa-clock"></i> <?= $svc['duration'] ?> perc
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:48px;" class="reveal">
            <a href="<?= BASE_URL ?>/foglalas" class="btn btn-gold">
                <i class="fas fa-calendar-check"></i> Foglalj most
            </a>
        </div>
    </div>
</section>

<!-- ══ MIÉRT MI ══ -->
<section class="section section-dark">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">Miért minket válassz</span>
            <h2 class="section-title">A <span>különbség</span> amit érezni fogsz</h2>
            <div class="divider">
                <div class="divider-line"></div>
                <i class="fas fa-award divider-icon"></i>
                <div class="divider-line"></div>
            </div>
        </div>
        <div class="features-grid">
            <div class="feature-item reveal reveal-delay-1">
                <div class="feature-icon"><i class="fas fa-hand-sparkles"></i></div>
                <h3>Prémium szakértelem</h3>
                <p>Kozmetikusaink a legújabb technikákkal dolgoznak a tartós és elegáns végeredményért.</p>
            </div>
            <div class="feature-item reveal reveal-delay-2">
                <div class="feature-icon"><i class="fas fa-palette"></i></div>
                <h3>Online foglalás</h3>
                <p>Foglalj pár kattintással kezelést és kozmetikust, amikor neked a legkényelmesebb.</p>
            </div>
            <div class="feature-item reveal reveal-delay-3">
                <div class="feature-icon"><i class="fas fa-gem"></i></div>
                <h3>Luxus alapanyagok</h3>
                <p>Minőségi hatóanyagokkal és professzionális termékekkel gondoskodunk bőröd szépségéről.</p>
            </div>
            <div class="feature-item reveal reveal-delay-4">
                <div class="feature-icon"><i class="fas fa-wand-magic-sparkles"></i></div>
                <h3>Személyre szabott stílus</h3>
                <p>Minden kezelést a bőröd igényeihez és céljaidhoz igazítunk.</p>
            </div>
        </div>
    </div>
</section>


<!-- ══ ELŐTTE–UTÁNA ══ -->
<section class="section section-white" id="before-after">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">Valódi eredmények</span>
            <h2 class="section-title">Előtte – <span>Utána</span></h2>
            <p class="section-subtitle">Nézd meg vendégeink látványos bőrmegújulását professzionális kozmetikai kezeléseinkkel.</p>
        </div>

        <div class="comparison-grid reveal">
            <div class="image-comparison" data-start="50">
                <div class="comparison-before">
                    <img src="<?= BASE_URL ?>/assets/images/hero-bg.jpg" alt="Arcbőr kezelés előtt">
                    <span class="comparison-label before">Előtte</span>
                </div>
                <div class="comparison-after">
                    <img src="<?= BASE_URL ?>/assets/images/cta-bg.jpg" alt="Arcbőr kezelés után">
                    <span class="comparison-label after">Utána</span>
                </div>
                <div class="comparison-handle" role="slider" aria-label="Előtte-utána csúszka" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50" tabindex="0">
                    <span class="comparison-line"></span>
                    <span class="comparison-knob"><i class="fas fa-arrows-left-right"></i></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ KOZMETIKUSOK ══ -->
<section class="section section-darker" id="mukormoseink">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">Kozmetikusaink</span>
            <h2 class="section-title">Ismerd meg <span>Kozmetikusainkat</span></h2>
            <p class="section-subtitle">Kreatív, precíz szakemberek, akik minden alkalomra egyedi megjelenést alkotnak.</p>
            <div class="divider">
                <div class="divider-line"></div>
                <i class="fas fa-user-nurse divider-icon"></i>
                <div class="divider-line"></div>
            </div>
        </div>
        <div class="staff-grid">
            <?php foreach ($staff as $i => $s): ?>
            <div class="staff-card reveal reveal-delay-<?= min($i + 1, 4) ?>">
                <div class="staff-photo">
                    <?php if ($s['photo']): ?>
                        <img src="<?= UPLOAD_URL . e($s['photo']) ?>" alt="<?= e($s['name']) ?>">
                    <?php else: ?>
                        <div class="staff-photo-placeholder">
                            <i class="fas fa-hand-sparkles"></i>
                        </div>
                    <?php endif; ?>
                    <div class="staff-overlay"></div>
                </div>
                <div class="staff-info">
                    <h3><?= e($s['name']) ?></h3>
                    <span class="staff-title">Kozmetikus</span>
                    <?php if ($s['bio']): ?>
                        <p class="staff-bio"><?= e(mb_substr($s['bio'], 0, 100)) ?>...</p>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/foglalas?staff=<?= $s['id'] ?>" class="staff-book-btn">
                        <i class="fas fa-calendar-plus"></i> Időpontfoglalás
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══ CTA SZEKCIÓ ══ -->
<section class="cta-section">
    <div class="cta-bg"></div>
    <div class="cta-overlay"></div>
    <div class="container">
        <div class="cta-content reveal">
            <span class="section-label">Ne várj tovább</span>
            <h2>Kényeztesd bőröd <span style="color:var(--accent);">még ma!</span></h2>
            <p>Válassz kezelést és kozmetikust, a többit pedig bízd ránk.</p>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>/foglalas" class="btn btn-gold btn-lg">
                    <i class="fas fa-calendar-check"></i> Időpontfoglalás
                </a>
                <a href="tel:<?= e(get_setting('site_phone')) ?>" class="btn btn-outline btn-lg">
                    <i class="fas fa-phone"></i> Hívj minket
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ══ BLOG ══ -->
<?php if ($posts): ?>
<section class="section section-dark" id="blog">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">Trendek & Tippek</span>
            <h2 class="section-title">Legújabb <span>Bejegyzéseink</span></h2>
            <div class="divider">
                <div class="divider-line"></div>
                <i class="fas fa-blog divider-icon"></i>
                <div class="divider-line"></div>
            </div>
        </div>
        <div class="blog-grid">
            <?php foreach ($posts as $i => $post): ?>
            <article class="blog-card reveal reveal-delay-<?= min($i + 1, 3) ?>">
                <div class="blog-thumb">
                    <?php if ($post['featured_image']): ?>
                        <img src="<?= UPLOAD_URL . e($post['featured_image']) ?>"
                             alt="<?= e($post['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="blog-thumb-placeholder">
                            <i class="fas fa-blog"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="blog-body">
                    <div class="blog-date">
                        <i class="fas fa-calendar"></i>
                        <?= $post['published_at'] ? date('Y. m. d.', strtotime($post['published_at'])) : '' ?>
                    </div>
                    <h3><a href="<?= BASE_URL ?>/blog/<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
                    <?php if ($post['excerpt']): ?>
                        <p class="blog-excerpt"><?= e(mb_substr($post['excerpt'], 0, 120)) ?>...</p>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/blog/<?= e($post['slug']) ?>" class="blog-read-more">
                        Tovább olvasom <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:40px;" class="reveal">
            <a href="<?= BASE_URL ?>/blog" class="btn btn-outline">
                <i class="fas fa-blog"></i> Összes bejegyzés
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══ NYITVATARTÁS + KAPCSOLAT ══ -->
<section class="section section-darker" id="contact">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">Elérhetőség</span>
            <h2 class="section-title">Nyitvatartás & <span>Kapcsolat</span></h2>
            <div class="divider">
                <div class="divider-line"></div>
                <i class="fas fa-map-marker-alt divider-icon"></i>
                <div class="divider-line"></div>
            </div>
        </div>

        <div class="contact-grid">
            <!-- Bal: Kapcsolati info + nyitvatartás -->
            <div class="reveal">
                <!-- Kapcsolati adatok -->
                <div style="margin-bottom:40px;">
                    <?php if (get_setting('site_address')): ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h4>Cím</h4>
                            <p><?= e(get_setting('site_address')) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (get_setting('site_phone')): ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <h4>Telefon</h4>
                            <a href="tel:<?= e(get_setting('site_phone')) ?>"><?= e(get_setting('site_phone')) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (get_setting('site_email')): ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h4>Email</h4>
                            <a href="mailto:<?= e(get_setting('site_email')) ?>"><?= e(get_setting('site_email')) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Nyitvatartás -->
                <div style="background:var(--dark-3);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;">
                    <h3 style="font-family:var(--font-serif);color:var(--white);font-size:20px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-clock" style="color:var(--accent);"></i> Nyitvatartás
                    </h3>
                    <div class="hours-table">
                        <?php foreach ($working_hours as $wh): ?>
                        <div class="hours-row">
                            <span class="hours-day"><?= $days_hu[$wh['day_of_week']] ?></span>
                            <?php if ($wh['is_day_off']): ?>
                                <span class="hours-closed">Zárva</span>
                            <?php else: ?>
                                <span class="hours-time">
                                    <?= substr($wh['start_time'], 0, 5) ?> – <?= substr($wh['end_time'], 0, 5) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Jobb: Kapcsolati form -->
            <div class="contact-form-wrap reveal">
                <h3 style="font-family:var(--font-serif);color:var(--white);font-size:22px;margin-bottom:24px;">
                    Írj nekünk!
                </h3>
                <div class="form-success" id="contactSuccess">
                    <i class="fas fa-check-circle"></i> Üzeneted megkaptuk! Hamarosan felvesszük veled a kapcsolatot.
                </div>
                <p class="form-error" id="contactError" style="margin-bottom:12px;"></p>
                <form id="contactForm">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Neved *</label>
                            <input type="text" name="name" placeholder="Kovács János" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" placeholder="email@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Telefonszám</label>
                        <input type="tel" name="phone" placeholder="+36 30 123 4567">
                    </div>
                    <div class="form-group">
                        <label>Üzenet *</label>
                        <textarea name="message" rows="5" placeholder="Írd ide az üzeneted..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-gold" style="width:100%;">
                        <i class="fas fa-paper-plane"></i> Üzenet küldése
                    </button>
                </form>
            </div>
        </div>

        <!-- Google Maps -->
        <?php $maps = get_setting('google_maps_embed'); ?>
        <?php if ($maps): ?>
        <div style="margin-top:48px;" class="reveal">
            <div style="border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border);">
                <?= $maps ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php
// CSS kiegészítés az index oldalhoz
$extra_js = '<script>window._BASE_URL = "' . BASE_URL . '";</script>';
require_once 'templates/footer.php';
?>