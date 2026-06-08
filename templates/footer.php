<?php
$footer_menu = [];
$fmenu_row = $pdo->query("SELECT id FROM menus WHERE location='footer' LIMIT 1")->fetch();
if ($fmenu_row) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id=? ORDER BY sort_order ASC");
    $stmt->execute([$fmenu_row['id']]);
    $footer_menu = $stmt->fetchAll();
}
$footer_text  = get_setting('footer_text', '© ' . date('Y') . ' ' . $site_name);
$maps_embed   = get_setting('google_maps_embed', '');
?>

<footer class="site-footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-grid">
                <!-- Logó + leírás -->
                <div class="footer-col footer-about">
                    <a href="<?= BASE_URL ?>/" class="footer-logo">
                        <?php if ($site_logo): ?>
                            <img src="<?= UPLOAD_URL . e($site_logo) ?>" alt="<?= e($site_name) ?>">
                        <?php else: ?>
                            <i class="fas fa-cut"></i> <?= e($site_name) ?>
                        <?php endif; ?>
                    </a>
                    <p><?= e(get_setting('site_tagline', '')) ?></p>
                    <div class="footer-social">
                        <?php if ($facebook_url): ?>
                        <a href="<?= e($facebook_url) ?>" target="_blank" rel="noopener">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($instagram_url): ?>
                        <a href="<?= e($instagram_url) ?>" target="_blank" rel="noopener">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Gyors linkek -->
                <div class="footer-col">
                    <h4>Gyors linkek</h4>
                    <ul class="footer-links">
                        <?php foreach ($footer_menu as $item): ?>
                        <li>
                            <a href="<?= e($item['url']) ?>">
                                <i class="fas fa-chevron-right"></i> <?= e($item['label']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Kapcsolat -->
                <div class="footer-col">
                    <h4>Kapcsolat</h4>
                    <ul class="footer-contact">
                        <?php if ($site_address): ?>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= e($site_address) ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if ($site_phone): ?>
                        <li>
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?= e($site_phone) ?>"><?= e($site_phone) ?></a>
                        </li>
                        <?php endif; ?>
                        <?php if ($site_email): ?>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?= e($site_email) ?>"><?= e($site_email) ?></a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Térkép -->
                <?php if ($maps_embed): ?>
                <div class="footer-col footer-map">
                    <h4>Térkép</h4>
                    <div class="footer-map-embed">
                        <?= $maps_embed ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p><?= e($footer_text) ?></p>
            <p>
                <a href="<?= BASE_URL ?>/adatvedelem">Adatvédelem</a>
                <span>·</span>
                <a href="<?= BASE_URL ?>/aszf">ÁSZF</a>
            </p>
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/image-comparison.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>