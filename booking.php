<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_meta_title = 'Időpontfoglalás – ' . get_setting('site_name');
$page_meta_desc  = 'Foglalj időpontot online kozmetikus szalonunkba. Válaszd ki a kezelést, a kozmetikust és az időpontot.';

// Előre kiválasztott kozmetikus (főoldalról jöhet)
$preselect_staff = (int)($_GET['staff'] ?? 0);

// Aktív kezelések és kozmetikusok
$services = $pdo->query("SELECT * FROM services WHERE active=1 ORDER BY sort_order ASC")->fetchAll();
$staff    = $pdo->query("SELECT * FROM staff WHERE active=1 ORDER BY sort_order ASC")->fetchAll();

// Szolgáltatások kategóriánként csoportosítva
$services_by_cat = [];
foreach ($services as $svc) {
    $services_by_cat[$svc['category']][] = $svc;
}

require_once 'templates/header.php';
?>

<script>window._BASE_URL = '<?= BASE_URL ?>';</script>

<!-- Oldal hero -->
<div class="page-hero">
    <div class="container">
        <h1>Időpontfoglalás</h1>
        <div class="page-breadcrumb">
            <a href="<?= BASE_URL ?>/">Főoldal</a>
            <i class="fas fa-chevron-right"></i>
            <span>Időpontfoglalás</span>
        </div>
    </div>
</div>

<section class="section section-dark">
    <div class="container">
        <div class="booking-wizard">

            <!-- Lépések -->
            <div class="wizard-steps">
                <div class="wizard-step active" id="step-indicator-1">
                    <div class="wizard-step-num">1</div>
                    <div class="wizard-step-label">Kezelés & Kozmetikus</div>
                </div>
                <div class="wizard-connector" id="connector-1"></div>
                <div class="wizard-step" id="step-indicator-2">
                    <div class="wizard-step-num">2</div>
                    <div class="wizard-step-label">Időpont</div>
                </div>
                <div class="wizard-connector" id="connector-2"></div>
                <div class="wizard-step" id="step-indicator-3">
                    <div class="wizard-step-num">3</div>
                    <div class="wizard-step-label">Adatok</div>
                </div>
            </div>

            <!-- ── 1. LÉPÉS: Szolgáltatás + Kozmetikus ── -->
            <div class="wizard-panel active" id="panel-1">

                <!-- Szolgáltatás választó -->
                <h3 class="wizard-panel-title">
                    <span class="step-num-badge">1</span>
                    Válassz kezelést
                </h3>

                <?php foreach ($services_by_cat as $cat => $cat_services): ?>
                <?php if ($cat): ?>
                <div class="service-category-label"><?= e($cat) ?></div>
                <?php endif; ?>
                <div class="service-select-grid">
                    <?php foreach ($cat_services as $svc): ?>
                    <div class="service-select-card"
                         data-id="<?= $svc['id'] ?>"
                         data-name="<?= e($svc['name']) ?>"
                         data-price="<?= $svc['price'] ?>"
                         data-duration="<?= $svc['duration'] ?>"
                         onclick="selectService(this)">
                        <h4><?= e($svc['name']) ?></h4>
                        <?php if ($svc['description']): ?>
                            <p><?= e(mb_substr($svc['description'],0,80)) ?></p>
                        <?php endif; ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;">
                            <span class="price"><?= number_format($svc['price'],0,',',' ') ?> Ft</span>
                            <span class="duration"><i class="fas fa-clock"></i> <?= $svc['duration'] ?> perc</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- Kozmetikus választó -->
                <h3 class="wizard-panel-title" style="margin-top:36px;">
                    <span class="step-num-badge">2</span>
                    Válassz kozmetikust
                </h3>
                <div class="staff-select-grid">
                    <?php foreach ($staff as $s): ?>
                    <div class="staff-select-card"
                         data-id="<?= $s['id'] ?>"
                         data-name="<?= e($s['name']) ?>"
                         onclick="selectStaff(this)"
                         <?= $preselect_staff === $s['id'] ? 'id="preselect-staff"' : '' ?>>
                        <?php if ($s['photo']): ?>
                            <img src="<?= UPLOAD_URL . e($s['photo']) ?>" alt="<?= e($s['name']) ?>">
                        <?php else: ?>
                            <div class="staff-avatar"><i class="fas fa-user-tie"></i></div>
                        <?php endif; ?>
                        <h4><?= e($s['name']) ?></h4>
                        <div class="staff-services-count" id="staff-svcs-<?= $s['id'] ?>"
                             style="font-size:11px;color:var(--text-muted);margin-top:4px;"></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="wizard-nav">
                    <div></div>
                    <button class="btn btn-gold" onclick="goToStep2()" id="toStep2Btn" disabled>
                        Következő <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ── 2. LÉPÉS: Dátum + Időpont ── -->
            <div class="wizard-panel" id="panel-2">
                <h3 class="wizard-panel-title">
                    <span class="step-num-badge">3</span>
                    Válassz időpontot
                </h3>

                <div class="date-slots-grid">
                    <!-- Naptár -->
                    <div>
                        <label style="color:var(--text);font-size:14px;font-weight:600;display:block;margin-bottom:12px;">
                            <i class="fas fa-calendar" style="color:var(--gold);margin-right:6px;"></i>Dátum kiválasztása
                        </label>
                        <input type="date"
                               id="bookingDate"
                               min="<?= date('Y-m-d') ?>"
                               max="<?= date('Y-m-d', strtotime('+' . get_setting('booking_advance_days','30') . ' days')) ?>"
                               value="<?= date('Y-m-d') ?>"
                               style="width:100%;padding:14px;background:var(--dark-3);border:2px solid var(--border);border-radius:var(--radius);color:var(--white);font-size:16px;outline:none;cursor:pointer;"
                               onchange="loadSlots()">

                        <!-- Kiválasztott összegzés -->
                        <div class="step2-summary" id="step2Summary" style="margin-top:20px;display:none;">
                            <div class="step2-summary-item">
                                <i class="fas fa-concierge-bell"></i>
                                <span id="s2ServiceName">–</span>
                            </div>
                            <div class="step2-summary-item">
                                <i class="fas fa-user-tie"></i>
                                <span id="s2StaffName">–</span>
                            </div>
                            <div class="step2-summary-item">
                                <i class="fas fa-clock"></i>
                                <span id="s2Duration">–</span>
                            </div>
                        </div>
                    </div>

                    <!-- Időpontok -->
                    <div>
                        <label style="color:var(--text);font-size:14px;font-weight:600;display:block;margin-bottom:12px;">
                            <i class="fas fa-clock" style="color:var(--gold);margin-right:6px;"></i>Szabad időpontok
                        </label>
                        <div id="slotsContainer">
                            <div class="slots-loading">
                                <i class="fas fa-spinner fa-spin"></i> Időpontok betöltése...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-nav">
                    <button class="btn btn-outline" onclick="goToStep(1)">
                        <i class="fas fa-arrow-left"></i> Vissza
                    </button>
                    <button class="btn btn-gold" onclick="goToStep3()" id="toStep3Btn" disabled>
                        Következő <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ── 3. LÉPÉS: Adatok ── -->
            <div class="wizard-panel" id="panel-3">
                <div style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start;">

                    <!-- Form -->
                    <div>
                        <h3 class="wizard-panel-title">
                            <span class="step-num-badge">4</span>
                            Személyes adatok
                        </h3>

                        <div id="bookingFormErrors" style="display:none;"
                             class="booking-error-box"></div>

                        <form id="bookingForm">
                            <div class="form-group">
                                <label>Teljes neved *</label>
                                <input type="text" id="custName" name="customer_name"
                                       placeholder="Kovács János" required>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Email cím *</label>
                                    <input type="email" id="custEmail" name="customer_email"
                                           placeholder="email@example.com" required>
                                </div>
                                <div class="form-group">
                                    <label>Telefonszám</label>
                                    <input type="tel" id="custPhone" name="customer_phone"
                                           placeholder="+36 30 123 4567">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Megjegyzés <span style="color:var(--text-muted);font-weight:400;">(opcionális)</span></label>
                                <textarea name="notes" id="custNotes" rows="3"
                                          placeholder="Esetleges kérések, megjegyzések..."></textarea>
                            </div>

                            <!-- GDPR -->
                            <div class="form-group">
                                <label class="gdpr-label">
                                    <input type="checkbox" id="gdprCheck" required>
                                    <span>Elfogadom az <a href="<?= BASE_URL ?>/adatvedelem" target="_blank">adatvédelmi tájékoztatót</a> és hozzájárulok adataim kezeléséhez. *</span>
                                </label>
                            </div>
                        </form>
                    </div>

                    <!-- Összegzés -->
                    <div>
                        <div class="booking-summary" id="bookingSummary">
                            <h3><i class="fas fa-receipt" style="color:var(--gold);margin-right:8px;"></i>Foglalás összegzése</h3>
                            <div class="summary-row">
                                <span class="summary-label">Kezelés</span>
                                <span class="summary-value" id="sumService">–</span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Kozmetikus</span>
                                <span class="summary-value" id="sumStaff">–</span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Dátum</span>
                                <span class="summary-value" id="sumDate">–</span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Időpont</span>
                                <span class="summary-value" id="sumTime">–</span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Időtartam</span>
                                <span class="summary-value" id="sumDuration">–</span>
                            </div>
                            <div class="summary-row total">
                                <span class="summary-label">Ár</span>
                                <span class="summary-value" id="sumPrice">–</span>
                            </div>
                        </div>

                        <button class="btn btn-gold" style="width:100%;font-size:15px;padding:16px;"
                                onclick="submitBooking()" id="submitBtn">
                            <i class="fas fa-calendar-check"></i> Foglalás véglegesítése
                        </button>
                        <p style="font-size:12px;color:var(--text-muted);text-align:center;margin-top:12px;">
                            <i class="fas fa-lock"></i> Adataidat biztonságosan kezeljük
                        </p>
                    </div>
                </div>

                <div class="wizard-nav" style="margin-top:24px;">
                    <button class="btn btn-outline" onclick="goToStep(2)">
                        <i class="fas fa-arrow-left"></i> Vissza
                    </button>
                </div>
            </div>

            <!-- ── SIKER ── -->
            <div class="wizard-panel" id="panel-success">
                <div class="booking-success">
                    <div class="booking-success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2>Kezelés foglalva!</h2>
                    <p>Köszönjük! Foglalásod sikeresen rögzítettük.</p>
                    <p>Hamarosan visszaigazoló emailt küldünk.</p>
                    <div class="booking-ref-box">
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;">Foglalási azonosító</div>
                        <span id="successRef">–</span>
                    </div>
                    <div class="success-details" id="successDetails"></div>
                    <div style="display:flex;gap:12px;justify-content:center;margin-top:28px;flex-wrap:wrap;">
                        <a href="<?= BASE_URL ?>/" class="btn btn-outline">
                            <i class="fas fa-home"></i> Főoldal
                        </a>
                        <a href="<?= BASE_URL ?>/foglalas" class="btn btn-gold">
                            <i class="fas fa-calendar-plus"></i> Új foglalás
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
.wizard-panel-title { font-family:var(--font-serif); font-size:20px; color:var(--white); margin-bottom:20px; display:flex; align-items:center; gap:12px; }
.step-num-badge { width:32px; height:32px; border-radius:50%; background:var(--gold); color:var(--dark); display:inline-flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; flex-shrink:0; }
.service-category-label { font-size:11px; text-transform:uppercase; letter-spacing:2px; color:var(--gold); margin:20px 0 10px; font-weight:600; }
.step2-summary { background:var(--dark-3); border:1px solid var(--border); border-radius:var(--radius); padding:16px; }
.step2-summary-item { display:flex; align-items:center; gap:10px; padding:6px 0; font-size:14px; color:var(--text); }
.step2-summary-item i { color:var(--gold); width:16px; }
.gdpr-label { display:flex; gap:10px; align-items:flex-start; cursor:pointer; font-size:13px; color:var(--text-muted); line-height:1.5; }
.gdpr-label input { margin-top:2px; accent-color:var(--gold); flex-shrink:0; }
.gdpr-label a { color:var(--gold); }
.booking-error-box { background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.3); border-radius:var(--radius); padding:14px 16px; color:#ff6b6b; font-size:14px; margin-bottom:20px; }
.success-details { background:var(--dark-3); border:1px solid var(--border); border-radius:var(--radius); padding:20px; margin:20px auto; max-width:360px; text-align:left; }
.success-details .summary-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid rgba(200,169,110,.1); font-size:14px; }
.success-details .summary-row:last-child { border-bottom:none; }
.form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:640px) { .form-row-2 { grid-template-columns:1fr; } }
@media(max-width:768px) {
    #panel-3 > div { grid-template-columns:1fr !important; }
    .booking-summary { order:-1; }
}
</style>

<script>
// ── Foglalás állapot ──
const booking = {
    serviceId:   null,
    serviceName: '',
    servicePrice: 0,
    serviceDuration: 0,
    staffId:   null,
    staffName: '',
    date:      '',
    startTime: '',
    endTime:   ''
};

// ── Előre kiválasztott kozmetikus ──
<?php if ($preselect_staff): ?>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('preselect-staff');
    if (el) selectStaff(el);
});
<?php endif; ?>

// ── Szolgáltatás kiválasztása ──
function selectService(el) {
    document.querySelectorAll('.service-select-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    booking.serviceId       = parseInt(el.dataset.id);
    booking.serviceName     = el.dataset.name;
    booking.servicePrice    = parseFloat(el.dataset.price);
    booking.serviceDuration = parseInt(el.dataset.duration);
    checkStep1();
    updateStaffAvailability();
}

// ── Kozmetikus kiválasztása ──
function selectStaff(el) {
    document.querySelectorAll('.staff-select-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    booking.staffId   = parseInt(el.dataset.id);
    booking.staffName = el.dataset.name;
    checkStep1();
}

// ── Step 1 gomb engedélyezése ──
function checkStep1() {
    document.getElementById('toStep2Btn').disabled = !(booking.serviceId && booking.staffId);
}

// ── Kozmetikusok szűrése kezelés szerint ──
function updateStaffAvailability() {
    if (!booking.serviceId) return;
    fetch(`<?= BASE_URL ?>/api/slots.php?check_staff=1&service_id=${booking.serviceId}`)
        .catch(() => {});
}

// ── Step 1 → 2 ──
function goToStep2() {
    if (!booking.serviceId || !booking.staffId) return;
    // Összegzés frissítése a 2. lépésen
    document.getElementById('s2ServiceName').textContent = booking.serviceName;
    document.getElementById('s2StaffName').textContent   = booking.staffName;
    document.getElementById('s2Duration').textContent    = booking.serviceDuration + ' perc';
    document.getElementById('step2Summary').style.display = 'block';
    goToStep(2);
    loadSlots();
}

// ── Időpontok betöltése ──
async function loadSlots() {
    if (!booking.staffId || !booking.serviceId) return;
    const date = document.getElementById('bookingDate').value;
    booking.date = date;
    booking.startTime = '';
    document.getElementById('toStep3Btn').disabled = true;

    const container = document.getElementById('slotsContainer');
    container.innerHTML = '<div class="slots-loading"><i class="fas fa-spinner fa-spin"></i> Betöltés...</div>';

    try {
        const res   = await fetch(`${window._BASE_URL}/api/slots.php?staff_id=${booking.staffId}&service_id=${booking.serviceId}&date=${date}&duration=${booking.serviceDuration}`);
        const slots = await res.json();

        if (!slots.length) {
            container.innerHTML = '<div class="slots-empty"><i class="fas fa-calendar-times"></i><br>Ezen a napon nincs szabad időpont.<br><small>Próbálj másik napot!</small></div>';
            return;
        }

        container.innerHTML = '<div class="slots-grid">' +
            slots.map(s => `<button class="slot-btn" onclick="selectSlot(this,'${s.start}','${s.end}')">${s.start}</button>`).join('') +
            '</div>';
    } catch {
        container.innerHTML = '<div class="slots-empty">Hiba az időpontok betöltésekor.</div>';
    }
}

// ── Időpont kiválasztása ──
function selectSlot(el, start, end) {
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    booking.startTime = start;
    booking.endTime   = end;
    document.getElementById('toStep3Btn').disabled = false;
}

// ── Step 2 → 3 ──
function goToStep3() {
    if (!booking.startTime) return;
    // Összegzés frissítése
    const dateObj = new Date(booking.date);
    const dateStr = dateObj.toLocaleDateString('hu-HU', {year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sumService').textContent  = booking.serviceName;
    document.getElementById('sumStaff').textContent    = booking.staffName;
    document.getElementById('sumDate').textContent     = dateStr;
    document.getElementById('sumTime').textContent     = booking.startTime + ' – ' + booking.endTime;
    document.getElementById('sumDuration').textContent = booking.serviceDuration + ' perc';
    document.getElementById('sumPrice').textContent    = new Intl.NumberFormat('hu-HU').format(booking.servicePrice) + ' Ft';
    goToStep(3);
}

// ── Lépés váltás ──
function goToStep(num) {
    document.querySelectorAll('.wizard-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + num).classList.add('active');

    // Indikátorok
    for (let i = 1; i <= 3; i++) {
        const ind = document.getElementById('step-indicator-' + i);
        const con = document.getElementById('connector-' + i);
        ind.classList.remove('active','done');
        if (con) con.classList.remove('done');
        if (i < num) { ind.classList.add('done'); if (con) con.classList.add('done'); }
        if (i === num) ind.classList.add('active');
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Foglalás beküldése ──
async function submitBooking() {
    const name  = document.getElementById('custName').value.trim();
    const email = document.getElementById('custEmail').value.trim();
    const phone = document.getElementById('custPhone').value.trim();
    const notes = document.getElementById('custNotes').value.trim();
    const gdpr  = document.getElementById('gdprCheck').checked;

    const errBox = document.getElementById('bookingFormErrors');
    errBox.style.display = 'none';

    if (!name || !email) {
        errBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> Kérjük töltsd ki a kötelező mezőket!';
        errBox.style.display = 'block';
        return;
    }
    if (!gdpr) {
        errBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> Az adatvédelmi tájékoztató elfogadása kötelező!';
        errBox.style.display = 'block';
        return;
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Foglalás...';

    try {
        const res  = await fetch(`${window._BASE_URL}/api/booking.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                staff_id:       booking.staffId,
                service_id:     booking.serviceId,
                booking_date:   booking.date,
                start_time:     booking.startTime,
                customer_name:  name,
                customer_email: email,
                customer_phone: phone,
                notes:          notes
            })
        });
        const json = await res.json();

        if (json.success) {
            // Siker képernyő
            document.getElementById('successRef').textContent = json.booking_ref;
            document.getElementById('successDetails').innerHTML = `
                <div class="summary-row"><span class="summary-label">Kezelés</span><span class="summary-value">${booking.serviceName}</span></div>
                <div class="summary-row"><span class="summary-label">Kozmetikus</span><span class="summary-value">${booking.staffName}</span></div>
                <div class="summary-row"><span class="summary-label">Dátum</span><span class="summary-value">${booking.date}</span></div>
                <div class="summary-row"><span class="summary-label">Időpont</span><span class="summary-value">${json.start_time} – ${json.end_time}</span></div>
            `;
            document.querySelectorAll('.wizard-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-success').classList.add('active');
            document.querySelectorAll('.wizard-step').forEach(s => s.classList.add('done'));
        } else {
            errBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (json.errors?.join('<br>') || 'Hiba történt.');
            errBox.style.display = 'block';
        }
    } catch {
        errBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> Hálózati hiba. Kérjük próbáld újra.';
        errBox.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-calendar-check"></i> Foglalás véglegesítése';
    }
}

// ── Dátum változás ──
document.getElementById('bookingDate')?.addEventListener('change', loadSlots);
</script>

<?php require_once 'templates/footer.php'; ?>