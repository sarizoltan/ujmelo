<?php

// ══════════════════════════════════════
// Shortcode feldolgozó
// ══════════════════════════════════════
function process_shortcodes(string $content): string {
    // HTML entitások dekódolása (TinyMCE miatt)
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Okos idézőjelek visszaalakítása
  $content = str_replace(
    ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", '&#8220;', '&#8221;', '&#8216;', '&#8217;'],
    ['"',        '"',        "'",         "'",        '"',       '"',       "'",       "'"],
    $content  
);

    // [services]
    $content = preg_replace_callback(
        '/\[services(\s[^\]]+)?\]/',
        'shortcode_services',
        $content
    );

    // [staff]
    $content = preg_replace_callback(
        '/\[staff(\s[^\]]+)?\]/',
        'shortcode_staff',
        $content
    );

    // [booking_button]
    $content = preg_replace_callback(
        '/\[booking_button(\s[^\]]+)?\]/',
        'shortcode_booking_button',
        $content
    );

    // [map]
    $content = preg_replace_callback(
        '/\[map\]/',
        'shortcode_map',
        $content
    );

    // [opening_hours]
    $content = preg_replace_callback(
        '/\[opening_hours\]/',
        'shortcode_opening_hours',
        $content
    );

    return $content;
}

// ══════════════════════════════════════
// Attribútumok parse-olása
// ══════════════════════════════════════
function parse_shortcode_atts(string $str, array $defaults = []): array {
    $atts = $defaults;

    // HTML entitások + okos idézőjelek normalizálása
    $str = html_entity_decode($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $str = str_replace(
    ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"],
    ['"',        '"',        "'",         "'"],
    $str  
);

    preg_match_all('/(\w+)\s*=\s*["\']([^"\']*)["\']/', $str, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $atts[$m[1]] = trim($m[2]);
    }

    return $atts;
}

// ══════════════════════════════════════
// [services limit="6" category="" columns="3"]
// ══════════════════════════════════════
function shortcode_services(array $matches): string {
    global $pdo;

    $atts = parse_shortcode_atts($matches[1] ?? '', [
        'limit'    => '99',
        'category' => '',
        'columns'  => '3',
    ]);

    // Kategória dekódolása (ékezetes karakterek)
    $atts['category'] = html_entity_decode($atts['category'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $where  = ["active = 1"];
    $params = [];

    if ($atts['category'] !== '') {
        $where[]  = "category = ?";
        $params[] = $atts['category'];
    }

    $sql = "SELECT * FROM services WHERE "
         . implode(' AND ', $where)
         . " ORDER BY sort_order ASC LIMIT "
         . (int)$atts['limit'];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll();

    if (!$services) {
        return '<p style="color:var(--text-muted);padding:16px 0;">Nincsenek elérhető szolgáltatások.</p>';
    }

    $cols = max(1, min(4, (int)$atts['columns']));

    ob_start();
    ?>
    <div class="sc-services-grid sc-cols-<?= $cols ?>">
        <?php foreach ($services as $svc): ?>
        <div class="sc-service-card">
            <div class="sc-service-header">
                <h3><?= e($svc['name']) ?></h3>
                <?php if ($svc['category']): ?>
                    <span class="sc-category-badge"><?= e($svc['category']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($svc['description']): ?>
                <p class="sc-service-desc"><?= e($svc['description']) ?></p>
            <?php endif; ?>
            <div class="sc-service-footer">
                <span class="sc-price">
                    <?= number_format((float)$svc['price'], 0, ',', ' ') ?> Ft
                </span>
                <span class="sc-duration">
                    <i class="fas fa-clock"></i> <?= (int)$svc['duration'] ?> perc
                </span>
                <a href="<?= BASE_URL ?>/foglalas" class="sc-book-btn">
                    <i class="fas fa-calendar-check"></i> Foglalj
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ══════════════════════════════════════
// [staff limit="4"]
// ══════════════════════════════════════
function shortcode_staff(array $matches): string {
    global $pdo;

    $atts = parse_shortcode_atts($matches[1] ?? '', [
        'limit' => '99',
    ]);

    $stmt = $pdo->prepare(
        "SELECT * FROM staff WHERE active = 1 ORDER BY sort_order ASC LIMIT " . (int)$atts['limit']
    );
    $stmt->execute();
    $staff = $stmt->fetchAll();

    if (!$staff) {
        return '<p style="color:var(--text-muted);padding:16px 0;">Nincsenek elérhető kozmetikusok.</p>';
    }

    ob_start();
    ?>
    <div class="sc-staff-grid">
        <?php foreach ($staff as $s): ?>
        <div class="sc-staff-card">
            <?php if ($s['photo']): ?>
                <img src="<?= UPLOAD_URL . e($s['photo']) ?>" alt="<?= e($s['name']) ?>">
            <?php else: ?>
                <div class="sc-staff-avatar"><i class="fas fa-user-tie"></i></div>
            <?php endif; ?>
            <h4><?= e($s['name']) ?></h4>
            <?php if ($s['bio']): ?>
                <p><?= e(mb_substr($s['bio'], 0, 100)) ?>...</p>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/foglalas?staff=<?= (int)$s['id'] ?>" class="sc-book-btn">
                <i class="fas fa-calendar-plus"></i> Időpontfoglalás
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ══════════════════════════════════════
// [booking_button text="Foglalj most!" url="..." style="gold"]
// ══════════════════════════════════════
function shortcode_booking_button(array $matches): string {
    $atts = parse_shortcode_atts($matches[1] ?? '', [
        'text'  => 'Időpontfoglalás',
        'url'   => BASE_URL . '/foglalas',
        'style' => 'gold',
    ]);

    $class = $atts['style'] === 'outline' ? 'btn btn-outline' : 'btn btn-gold';

    return '<div style="text-align:center;margin:28px 0;">'
         . '<a href="' . e($atts['url']) . '" class="' . $class . '">'
         . '<i class="fas fa-calendar-check"></i> '
         . e($atts['text'])
         . '</a></div>';
}

// ══════════════════════════════════════
// [map]
// ══════════════════════════════════════
function shortcode_map(array $matches): string {
    $embed = get_setting('google_maps_embed', '');
    if (!$embed) {
        return '<p style="color:var(--text-muted);">Nincs Google Maps kód beállítva.</p>';
    }
    return '<div class="sc-map">' . $embed . '</div>';
}

// ══════════════════════════════════════
// [opening_hours]
// ══════════════════════════════════════
function shortcode_opening_hours(array $matches): string {
    global $pdo;

    $days_hu = ['Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat', 'Vasárnap'];

    $stmt = $pdo->prepare(
        "SELECT * FROM working_hours WHERE staff_id = 1 ORDER BY day_of_week ASC"
    );
    $stmt->execute();
    $hours = $stmt->fetchAll();

    if (!$hours) {
        return '<p style="color:var(--text-muted);">Nincs nyitvatartás beállítva.</p>';
    }

    ob_start();
    ?>
    <div class="sc-hours">
        <?php foreach ($hours as $h): ?>
        <div class="sc-hours-row <?= $h['is_day_off'] ? 'closed' : '' ?>">
            <span class="sc-hours-day"><?= $days_hu[(int)$h['day_of_week']] ?? '' ?></span>
            <?php if ($h['is_day_off']): ?>
                <span class="sc-hours-time closed">Zárva</span>
            <?php else: ?>
                <span class="sc-hours-time">
                    <?= substr($h['start_time'], 0, 5) ?> – <?= substr($h['end_time'], 0, 5) ?>
                </span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}