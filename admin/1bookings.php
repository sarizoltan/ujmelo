<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message = '';
$message_type = 'success';

// ── STÁTUSZ VÁLTOZTATÁS ──
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (in_array($_GET['status'], $allowed)) {
        $pdo->prepare("UPDATE bookings SET status=? WHERE id=?")
            ->execute([$_GET['status'], (int)$_GET['id']]);
        $message = 'Státusz frissítve!';
    }
}

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Foglalás törölve!';
    }
}

// ── ÚJ FOGLALÁS (admin kézzel) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_booking'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $staff_id   = (int)$_POST['staff_id'];
    $service_id = (int)$_POST['service_id'];
    $cust_name  = trim($_POST['customer_name']);
    $cust_email = trim($_POST['customer_email']);
    $cust_phone = trim($_POST['customer_phone']);
    $date       = $_POST['booking_date'];
    $start      = $_POST['start_time'];
    $notes      = trim($_POST['notes'] ?? '');
    $status     = $_POST['status'] ?? 'confirmed';

    // Időtartam lekérése
    $svc = $pdo->prepare("SELECT duration FROM services WHERE id=?");
    $svc->execute([$service_id]);
    $svc = $svc->fetch();
    $duration = $svc ? (int)$svc['duration'] : 30;
    $end = date('H:i', strtotime($start) + $duration * 60);

    if ($staff_id && $service_id && $cust_name && $cust_email && $date && $start) {
        // Ütközés ellenőrzés
        $conflict = $pdo->prepare("SELECT id FROM bookings 
            WHERE staff_id=? AND booking_date=? AND status != 'cancelled'
            AND start_time < ? AND end_time > ?");
        $conflict->execute([$staff_id, $date, $end, $start]);

        if ($conflict->fetch()) {
            $message = 'Ez az időpont már foglalt!';
            $message_type = 'error';
        } else {
            $ref = generate_booking_ref();
            $pdo->prepare("INSERT INTO bookings 
                (booking_ref,staff_id,service_id,customer_name,customer_email,customer_phone,booking_date,start_time,end_time,status,notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$ref,$staff_id,$service_id,$cust_name,$cust_email,$cust_phone,$date,$start,$end,$status,$notes]);
            $message = 'Foglalás létrehozva! Referencia: ' . $ref;
        }
    } else {
        $message = 'Kérjük töltsd ki az összes kötelező mezőt!';
        $message_type = 'error';
    }
}

// ── SZŰRŐK ──
$filter_date   = $_GET['date'] ?? date('Y-m-d');
$filter_staff  = (int)($_GET['staff'] ?? 0);
$filter_status = $_GET['fstatus'] ?? '';
$filter_search = trim($_GET['search'] ?? '');
$view          = $_GET['view'] ?? 'list';

// ── LEKÉRDEZÉS ──
$where = ['1=1'];
$params = [];

if ($filter_staff) {
    $where[] = 'b.staff_id = ?';
    $params[] = $filter_staff;
}
if ($filter_status) {
    $where[] = 'b.status = ?';
    $params[] = $filter_status;
}
if ($filter_search) {
    $where[] = '(b.customer_name LIKE ? OR b.customer_email LIKE ? OR b.booking_ref LIKE ?)';
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
}

// Napi nézetnél dátum szűrő
if ($view === 'day') {
    $where[] = 'b.booking_date = ?';
    $params[] = $filter_date;
}

$sql = "SELECT b.*, s.name as staff_name, sv.name as service_name, sv.duration as service_duration
        FROM bookings b
        JOIN staff s ON b.staff_id = s.id
        JOIN services sv ON b.service_id = sv.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY b.booking_date DESC, b.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Műkörmösök és szolgáltatások a filterekhez és formhoz
$all_staff    = $pdo->query("SELECT * FROM staff WHERE active=1 ORDER BY sort_order")->fetchAll();
$all_services = $pdo->query("SELECT * FROM services WHERE active=1 ORDER BY sort_order")->fetchAll();

// Napi összesítő
// ── STATISZTIKA (összes foglalás) ──
$today_summary = $pdo->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$page_title = 'Foglalások';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-calendar-alt"></i> Foglalások kezelése</h2>
    <button class="btn btn-primary" onclick="openBookingModal()">
        <i class="fas fa-plus"></i> Új foglalás
    </button>
</div>

<!-- Napi összesítő -->
<div class="booking-summary-bar">
    <div class="summary-item">
        <span class="summary-dot pending"></span>
        <span>Függőben: <strong><?= $today_summary['pending'] ?? 0 ?></strong></span>
    </div>
    <div class="summary-item">
        <span class="summary-dot confirmed"></span>
        <span>Megerősítve: <strong><?= $today_summary['confirmed'] ?? 0 ?></strong></span>
    </div>
    <div class="summary-item">
        <span class="summary-dot completed"></span>
        <span>Teljesítve: <strong><?= $today_summary['completed'] ?? 0 ?></strong></span>
    </div>
    <div class="summary-item">
        <span class="summary-dot cancelled"></span>
        <span>Lemondva: <strong><?= $today_summary['cancelled'] ?? 0 ?></strong></span>
    </div>
    <span style="margin-left:auto;color:var(--text-muted);font-size:13px;">
        <i class="fas fa-calendar"></i> Mai nap: <?= date('Y. m. d.') ?>
    </span>
</div>

<!-- Nézet váltó + szűrők -->
<div class="card">
    <div class="bookings-toolbar">
        <div class="view-tabs">
            <a href="?view=list<?= $filter_staff ? '&staff='.$filter_staff : '' ?>"
               class="view-tab <?= $view === 'list' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> Lista
            </a>
            <a href="?view=day&date=<?= $filter_date ?><?= $filter_staff ? '&staff='.$filter_staff : '' ?>"
               class="view-tab <?= $view === 'day' ? 'active' : '' ?>">
                <i class="fas fa-calendar-day"></i> Napi nézet
            </a>
        </div>

        <form method="GET" action="bookings.php" class="filter-form">
            <input type="hidden" name="view" value="<?= e($view) ?>">
            <?php if ($view === 'day'): ?>
            <input type="date" name="date" value="<?= e($filter_date) ?>" style="max-width:160px;">
            <?php endif; ?>
            <select name="staff" style="max-width:180px;">
                <option value="">Minden műkörmös</option>
                <?php foreach ($all_staff as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filter_staff == $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="fstatus" style="max-width:160px;">
                <option value="">Minden státusz</option>
                <option value="pending"   <?= $filter_status==='pending'   ? 'selected':'' ?>>Függőben</option>
                <option value="confirmed" <?= $filter_status==='confirmed' ? 'selected':'' ?>>Megerősítve</option>
                <option value="completed" <?= $filter_status==='completed' ? 'selected':'' ?>>Teljesítve</option>
                <option value="cancelled" <?= $filter_status==='cancelled' ? 'selected':'' ?>>Lemondva</option>
            </select>
            <input type="text" name="search" value="<?= e($filter_search) ?>"
                   placeholder="Ügyfél / ref. keresés..." style="max-width:220px;">
            <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Szűrés</button>
            <a href="bookings.php?view=<?= $view ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
        </form>
    </div>

    <?php if ($view === 'day'): ?>
    <!-- ══ NAPI NÉZET ══ -->
    <div class="day-view">
        <div class="day-nav">
            <a href="?view=day&date=<?= date('Y-m-d', strtotime($filter_date . ' -1 day')) ?><?= $filter_staff ? '&staff='.$filter_staff : '' ?>"
               class="btn btn-secondary btn-sm"><i class="fas fa-chevron-left"></i></a>
            <h3><?= date('Y. m. d. (l)', strtotime($filter_date)) ?></h3>
            <a href="?view=day&date=<?= date('Y-m-d', strtotime($filter_date . ' +1 day')) ?><?= $filter_staff ? '&staff='.$filter_staff : '' ?>"
               class="btn btn-secondary btn-sm"><i class="fas fa-chevron-right"></i></a>
            <a href="?view=day&date=<?= date('Y-m-d') ?>" class="btn btn-secondary btn-sm">Ma</a>
        </div>

        <?php if ($all_staff): ?>
        <div class="day-columns" style="--col-count:<?= min(count($all_staff), 4) ?>;">
            <?php
            $staff_to_show = $filter_staff
                ? array_filter($all_staff, fn($s) => $s['id'] == $filter_staff)
                : $all_staff;
            foreach ($staff_to_show as $s):
                $staff_bookings = array_filter($bookings, fn($b) => $b['staff_id'] == $s['id']);
            ?>
            <div class="day-column">
                <div class="day-column-header">
                    <?php if ($s['photo']): ?>
                        <img src="<?= UPLOAD_URL . e($s['photo']) ?>" alt="">
                    <?php else: ?>
                        <div class="staff-avatar-sm"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                    <span><?= e($s['name']) ?></span>
                </div>
                <div class="day-slots">
                    <?php if ($staff_bookings): ?>
                        <?php foreach ($staff_bookings as $b): ?>
                        <div class="day-booking-card status-<?= $b['status'] ?>">
                            <div class="dbc-time">
                                <i class="fas fa-clock"></i>
                                <?= format_time($b['start_time']) ?> – <?= format_time($b['end_time']) ?>
                            </div>
                            <div class="dbc-name"><strong><?= e($b['customer_name']) ?></strong></div>
                            <div class="dbc-service"><?= e($b['service_name']) ?></div>
                            <div class="dbc-ref"><code><?= e($b['booking_ref']) ?></code></div>
                            <div class="dbc-actions">
                                <?php if ($b['status'] === 'pending'): ?>
                                <a href="?id=<?= $b['id'] ?>&status=confirmed&csrf_token=<?= csrf_token() ?>&view=day&date=<?= $filter_date ?>"
                                   class="dbc-btn confirm" title="Megerősítés">✓</a>
                                <?php endif; ?>
                                <?php if (in_array($b['status'], ['pending','confirmed'])): ?>
                                <a href="?id=<?= $b['id'] ?>&status=completed&csrf_token=<?= csrf_token() ?>&view=day&date=<?= $filter_date ?>"
                                   class="dbc-btn complete" title="Teljesítve">★</a>
                                <a href="?id=<?= $b['id'] ?>&status=cancelled&csrf_token=<?= csrf_token() ?>&view=day&date=<?= $filter_date ?>"
                                   class="dbc-btn cancel" title="Lemondás">✕</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="day-empty"><i class="fas fa-calendar-times"></i><br>Nincs foglalás</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ══ LISTA NÉZET ══ -->
    <table class="admin-table" style="margin-top:16px;">
        <thead>
            <tr>
                <th>Ref.</th>
                <th>Dátum / Idő</th>
                <th>Ügyfél</th>
                <th>Műkörmös</th>
                <th>Szolgáltatás</th>
                <th>Telefon</th>
                <th>Státusz</th>
                <th>Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($bookings): ?>
            <?php foreach ($bookings as $b): ?>
            <tr>
                <td><code><?= e($b['booking_ref']) ?></code></td>
                <td>
                    <strong><?= format_date($b['booking_date']) ?></strong><br>
                    <small><?= format_time($b['start_time']) ?> – <?= format_time($b['end_time']) ?></small>
                </td>
                <td>
                    <strong><?= e($b['customer_name']) ?></strong><br>
                    <small><?= e($b['customer_email']) ?></small>
                </td>
                <td><?= e($b['staff_name']) ?></td>
                <td>
                    <?= e($b['service_name']) ?><br>
                    <small style="color:#999;"><?= $b['service_duration'] ?> perc</small>
                </td>
                <td><?= e($b['customer_phone']) ?></td>
                <td>
                    <div class="status-dropdown" data-id="<?= $b['id'] ?>">
                        <span class="badge badge-<?= $b['status'] ?> status-badge" style="cursor:pointer;">
                            <?= booking_status_label($b['status']) ?> <i class="fas fa-chevron-down" style="font-size:9px;"></i>
                        </span>
                        <div class="status-menu">
                            <?php foreach (['pending'=>'Függőben','confirmed'=>'Megerősítve','completed'=>'Teljesítve','cancelled'=>'Lemondva'] as $st => $lbl): ?>
                                <?php if ($st !== $b['status']): ?>
                                <a href="?id=<?= $b['id'] ?>&status=<?= $st ?>&csrf_token=<?= csrf_token() ?>">
                                    <span class="badge badge-<?= $st ?>"><?= $lbl ?></span>
                                </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="table-actions">
                        <button class="action-btn view" title="Részletek"
                            onclick="showBookingDetail(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="?delete=<?= $b['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="action-btn delete" title="Törlés"
                           data-confirm="Biztosan törölni szeretnéd ezt a foglalást?">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>Nincs foglalás a megadott szűrőkkel.</p>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── MODAL: Új foglalás ── -->
<div class="modal-overlay" id="bookingModal">
    <div class="modal" style="max-width:660px;">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-plus"></i> Új foglalás (admin)</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="bookings.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_booking" value="1">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user-tie"></i> Műkörmös *</label>
                        <select name="staff_id" id="bStaff" required onchange="loadServiceSlots()">
                            <option value="">Válassz műkörmöst</option>
                            <?php foreach ($all_staff as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-concierge-bell"></i> Szolgáltatás *</label>
                        <select name="service_id" id="bService" required onchange="loadServiceSlots()">
                            <option value="">Válassz szolgáltatást</option>
                            <?php foreach ($all_services as $s): ?>
                                <option value="<?= $s['id'] ?>" data-duration="<?= $s['duration'] ?>">
                                    <?= e($s['name']) ?> (<?= $s['duration'] ?> perc – <?= number_format($s['price'],0,',',' ') ?> Ft)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Dátum *</label>
                        <input type="date" name="booking_date" id="bDate"
                               min="<?= date('Y-m-d') ?>"
                               value="<?= date('Y-m-d') ?>"
                               onchange="loadServiceSlots()" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Kezdési idő *</label>
                        <select name="start_time" id="bTime" required>
                            <option value="">Először válassz műkörmöst, napot és szolgáltatást</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Ügyfél neve *</label>
                        <input type="text" name="customer_name" required placeholder="Teljes név">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" name="customer_email" required placeholder="email@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Telefon</label>
                        <input type="text" name="customer_phone" placeholder="+36 30 123 4567">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-flag"></i> Státusz</label>
                        <select name="status">
                            <option value="confirmed">Megerősítve</option>
                            <option value="pending">Függőben</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-sticky-note"></i> Megjegyzés</label>
                    <textarea name="notes" rows="2" placeholder="Opcionális megjegyzés..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Mégse</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Foglalás létrehozása</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL: Foglalás részletei ── -->
<div class="modal-overlay" id="detailModal">
    <div class="modal" style="max-width:500px;">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Foglalás részletei</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="detailBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Bezárás</button>
        </div>
    </div>
</div>

<style>
.booking-summary-bar { display:flex; align-items:center; gap:20px; background:var(--white); border-radius:var(--radius); padding:14px 20px; margin-bottom:20px; box-shadow:var(--shadow); flex-wrap:wrap; }
.summary-item { display:flex; align-items:center; gap:8px; font-size:13px; }
.summary-dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
.summary-dot.pending { background:var(--orange); }
.summary-dot.confirmed { background:var(--green); }
.summary-dot.completed { background:var(--blue); }
.summary-dot.cancelled { background:var(--red); }
.bookings-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding-bottom:16px; border-bottom:1px solid var(--border); margin-bottom:16px; }
.view-tabs { display:flex; gap:4px; }
.view-tab { padding:7px 14px; border-radius:8px; border:1px solid var(--border); text-decoration:none; color:var(--text); font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; transition:all .2s; }
.view-tab.active, .view-tab:hover { background:var(--accent); color:#fff; border-color:var(--accent); }
.filter-form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; flex:1; }
.filter-form input, .filter-form select { padding:7px 10px; border:2px solid var(--border); border-radius:8px; font-size:13px; }

/* Napi nézet */
.day-view { overflow-x:auto; }
.day-nav { display:flex; align-items:center; gap:12px; padding:12px 0; margin-bottom:16px; }
.day-nav h3 { font-size:16px; font-weight:700; flex:1; text-align:center; }
.day-columns { display:grid; grid-template-columns:repeat(var(--col-count),1fr); gap:16px; min-width:600px; }
.day-column { background:#f9fafb; border-radius:var(--radius); overflow:hidden; }
.day-column-header { background:var(--primary); color:#fff; padding:12px 16px; display:flex; align-items:center; gap:10px; font-weight:600; font-size:13px; }
.day-column-header img { width:32px; height:32px; border-radius:50%; object-fit:cover; }
.staff-avatar-sm { width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; }
.day-slots { padding:12px; display:flex; flex-direction:column; gap:10px; min-height:200px; }
.day-empty { text-align:center; color:var(--text-muted); padding:30px 10px; font-size:13px; }
.day-booking-card { border-radius:8px; padding:12px; font-size:13px; border-left:4px solid; }
.day-booking-card.status-pending   { background:#fef9ee; border-color:var(--orange); }
.day-booking-card.status-confirmed { background:#f0fdf4; border-color:var(--green); }
.day-booking-card.status-completed { background:#eff6ff; border-color:var(--blue); }
.day-booking-card.status-cancelled { background:#fef2f2; border-color:var(--red); opacity:.7; }
.dbc-time { color:var(--text-muted); font-size:12px; margin-bottom:4px; }
.dbc-name { font-weight:700; color:var(--primary); }
.dbc-service { color:var(--text-light); font-size:12px; margin:2px 0; }
.dbc-ref code { font-size:11px; background:#f0f0f0; padding:1px 5px; border-radius:3px; }
.dbc-actions { display:flex; gap:6px; margin-top:8px; }
.dbc-btn { width:26px; height:26px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; }
.dbc-btn.confirm { background:#d1fae5; color:var(--green); }
.dbc-btn.complete { background:#dbeafe; color:var(--blue); }
.dbc-btn.cancel { background:#fee2e2; color:var(--red); }
.dbc-btn:hover { filter:brightness(.9); }

/* Státusz dropdown */
.status-dropdown { position:relative; display:inline-block; }
.status-menu { display:none; position:absolute; top:100%; left:0; background:#fff; border:1px solid var(--border); border-radius:8px; box-shadow:var(--shadow-lg); z-index:10; min-width:140px; padding:6px; }
.status-dropdown:hover .status-menu { display:block; }
.status-menu a { display:block; padding:4px 6px; text-decoration:none; border-radius:4px; }
.status-menu a:hover { background:#f5f5f5; }

/* Detail modal */
.detail-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f0f0f0; font-size:14px; }
.detail-row:last-child { border-bottom:none; }
.detail-label { color:var(--text-muted); }
.detail-value { font-weight:600; color:var(--primary); }
</style>

<script>
function openBookingModal() {
    document.getElementById('bookingModal').classList.add('open');
}

function showBookingDetail(b) {
    document.getElementById('detailBody').innerHTML = `
        <div class="detail-row"><span class="detail-label">Referencia</span><span class="detail-value"><code>${b.booking_ref}</code></span></div>
        <div class="detail-row"><span class="detail-label">Ügyfél</span><span class="detail-value">${b.customer_name}</span></div>
        <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${b.customer_email}</span></div>
        <div class="detail-row"><span class="detail-label">Telefon</span><span class="detail-value">${b.customer_phone || '–'}</span></div>
        <div class="detail-row"><span class="detail-label">Műkörmös</span><span class="detail-value">${b.staff_name}</span></div>
        <div class="detail-row"><span class="detail-label">Szolgáltatás</span><span class="detail-value">${b.service_name}</span></div>
        <div class="detail-row"><span class="detail-label">Dátum</span><span class="detail-value">${b.booking_date}</span></div>
        <div class="detail-row"><span class="detail-label">Időpont</span><span class="detail-value">${b.start_time.slice(0,5)} – ${b.end_time.slice(0,5)}</span></div>
        <div class="detail-row"><span class="detail-label">Státusz</span><span class="detail-value"><span class="badge badge-${b.status}">${{pending:'Függőben',confirmed:'Megerősítve',completed:'Teljesítve',cancelled:'Lemondva'}[b.status]}</span></span></div>
        ${b.notes ? `<div class="detail-row"><span class="detail-label">Megjegyzés</span><span class="detail-value">${b.notes}</span></div>` : ''}
        <div class="detail-row"><span class="detail-label">Létrehozva</span><span class="detail-value">${b.created_at}</span></div>
    `;
    document.getElementById('detailModal').classList.add('open');
}

// Szabad időpontok betöltése az admin foglalóba
function loadServiceSlots() {
    const staffId   = document.getElementById('bStaff').value;
    const serviceId = document.getElementById('bService').value;
    const date      = document.getElementById('bDate').value;
    const timeSelect = document.getElementById('bTime');

    if (!staffId || !serviceId || !date) {
        timeSelect.innerHTML = '<option value="">Töltsd ki az összes mezőt</option>';
        return;
    }

    const duration = document.getElementById('bService').selectedOptions[0]?.dataset.duration || 30;

    timeSelect.innerHTML = '<option value="">Betöltés...</option>';

    fetch(`<?= BASE_URL ?>/api/slots.php?staff_id=${staffId}&date=${date}&duration=${duration}&service_id=${serviceId}`)
        .then(r => r.json())
        .then(slots => {
            if (!slots.length) {
                timeSelect.innerHTML = '<option value="">Nincs szabad időpont</option>';
                return;
            }
            timeSelect.innerHTML = slots.map(s =>
                `<option value="${s.start}">${s.start} – ${s.end}</option>`
            ).join('');
        })
        .catch(() => {
            timeSelect.innerHTML = '<option value="">Hiba az időpontok betöltésekor</option>';
        });
}
</script>

<?php require_once 'partials/footer.php'; ?>