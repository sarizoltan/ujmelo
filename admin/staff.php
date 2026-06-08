<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

// ── DEBUG ──
error_log('=== STAFF.PHP LOADED ===');
error_log('UPLOAD_PATH = ' . UPLOAD_PATH);
error_log('UPLOAD_URL = ' . UPLOAD_URL);
error_log('is_writable = ' . (is_writable(UPLOAD_PATH) ? 'YES' : 'NO'));
error_log('Current user = ' . get_current_user());
error_log('=====================');

$message = '';
$message_type = 'success';
// ... REST ...

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM staff WHERE id = ?")->execute([$_GET['delete']]);
        $message = 'Kozmetikus törölve!';
    }
}

// ── MENTÉS (új / szerkesztés) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_staff'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $sort     = (int)($_POST['sort_order'] ?? 0);
    $active   = isset($_POST['active']) ? 1 : 0;
    $services = $_POST['services'] ?? [];
    $photo    = '';
    $photo_error = '';

    // Fotó feltöltés
    if (!empty($_FILES['photo']['name'])) {
        $file_error = $_FILES['photo']['error'];
        $file_name  = $_FILES['photo']['name'];
        $file_size  = $_FILES['photo']['size'];
        $file_tmp   = $_FILES['photo']['tmp_name'];

        // Hiba ellenőrzés
        if ($file_error !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'A fájl nagyobb, mint a szerver limit (php.ini: upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE  => 'A fájl nagyobb, mint a form limit.',
                UPLOAD_ERR_PARTIAL    => 'A fájl csak részlegesen töltődött fel.',
                UPLOAD_ERR_NO_FILE    => 'Nincs fájl kiválasztva.',
                UPLOAD_ERR_NO_TMP_DIR => 'Hiányzik az ideiglenes mappa.',
                UPLOAD_ERR_CANT_WRITE => 'Nem lehet a fájlt az ideiglenes mappába írni.',
                UPLOAD_ERR_EXTENSION  => 'PHP kiterjesztés megakadályozta a feltöltést.',
            ];
            $photo_error = $upload_errors[$file_error] ?? 'Ismeretlen feltöltési hiba.';
        }
        // Fájlméret ellenőrzés
        else if ($file_size > 2097152) {
            $photo_error = 'A fájl túl nagy! Maximum 2 MB engedélyezett.';
        }
        // Kiterjesztés ellenőrzés
        else {
            $ext      = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed)) {
                $photo_error = 'Érvénytelen fájl típus! Engedélyezett: JPG, PNG, WEBP.';
            }
            // MIME típus ellenőrzés
            else {
                $real_mime = mime_content_type($file_tmp);
                $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($real_mime, $allowed_mimes)) {
                    $photo_error = 'A fájl MIME típusa nem támogatott! (' . $real_mime . ')';
                }
            }
        }

        // Ha nincs hiba, feltöltés
        if (!$photo_error) {
            // Mappa ellenőrzése és létrehozása
            if (!is_dir(UPLOAD_PATH)) {
                if (!mkdir(UPLOAD_PATH, 0755, true)) {
                    $photo_error = 'A feltöltési mappa nem létezik és nem sikerült létrehozni!';
                    error_log('UPLOAD DIR CREATE FAIL: ' . UPLOAD_PATH);
                }
            }

            // Mappa írhatósági ellenőrzés
            if (!is_writable(UPLOAD_PATH)) {
                $photo_error = 'A feltöltési mappa nem írható! Ellenőrizd a jogosultságokat: chmod 755 ' . UPLOAD_PATH;
                error_log('UPLOAD DIR NOT WRITABLE: ' . UPLOAD_PATH);
            }

            // Fájl áthelyezés
            if (!$photo_error) {
                $filename = 'staff_' . time() . '_' . uniqid() . '.' . $ext;
                $full_path = UPLOAD_PATH . $filename;

                if (move_uploaded_file($file_tmp, $full_path)) {
                    $photo = $filename;
                    chmod($full_path, 0644);
                } else {
                    $photo_error = 'Nem sikerült a fájl áthelyezése! (move_uploaded_file() hiba) Ellenőrizd a jogosultságokat.';
                    error_log('MOVE UPLOADED FILE FAIL: ' . $file_tmp . ' -> ' . $full_path);
                }
            }
        }
    }

    // Hibaüzenet ha van
    if ($photo_error) {
        $message = 'Fotó feltöltési hiba: ' . $photo_error;
        $message_type = 'error';
    }

    // Mentés akkor is, ha nincs fotó
    if ($name && (!$photo_error || empty($_FILES['photo']['name']))) {
        if ($id > 0) {
            // Frissítés
            $sql = "UPDATE staff SET name=?, bio=?, email=?, phone=?, sort_order=?, active=?" .
                   ($photo ? ", photo=?" : "") . " WHERE id=?";
            $params = $photo
                ? [$name, $bio, $email, $phone, $sort, $active, $photo, $id]
                : [$name, $bio, $email, $phone, $sort, $active, $id];
            $pdo->prepare($sql)->execute($params);
            $message = ($photo ? 'Kozmetikus frissítve + fotó feltöltve!' : 'Kozmetikus frissítve!');
            if ($photo_error) $message .= ' ⚠️ ' . $photo_error;
        } else {
            // Új
            $pdo->prepare("INSERT INTO staff (name,bio,email,phone,sort_order,active,photo) VALUES (?,?,?,?,?,?,?)")
                ->execute([$name, $bio, $email, $phone, $sort, $active, $photo]);
            $id = $pdo->lastInsertId();

            // Alapértelmezett munkaidő létrehozása (H-P: 09-18, Szo: 09-16, V: zárva)
            $default_hours = [
                [0,'09:00','18:00',0],[1,'09:00','18:00',0],[2,'09:00','18:00',0],
                [3,'09:00','18:00',0],[4,'09:00','18:00',0],[5,'09:00','16:00',0],
                [6,'00:00','00:00',1]
            ];
            $wh_stmt = $pdo->prepare("INSERT INTO working_hours (staff_id,day_of_week,start_time,end_time,is_day_off) VALUES (?,?,?,?,?)");
            foreach ($default_hours as $h) {
                $wh_stmt->execute([$id, $h[0], $h[1], $h[2], $h[3]]);
            }
            $message = ($photo ? 'Kozmetikus létrehozva + fotó feltöltve!' : 'Kozmetikus létrehozva!');
            if ($photo_error) $message .= ' ⚠️ ' . $photo_error;
        }

        // Szolgáltatások frissítése
        $pdo->prepare("DELETE FROM staff_services WHERE staff_id = ?")->execute([$id]);
        if ($services) {
            $ss_stmt = $pdo->prepare("INSERT INTO staff_services (staff_id, service_id) VALUES (?,?)");
            foreach ($services as $sid) {
                $ss_stmt->execute([$id, (int)$sid]);
            }
        }
    }
}

// ── MUNKAIDŐ MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_hours'])) {
    if (!csrf_verify()) die('CSRF hiba');
    $staff_id = (int)$_POST['staff_id'];
    for ($day = 0; $day <= 6; $day++) {
        $is_off = isset($_POST['day_off'][$day]) ? 1 : 0;
        $start  = $_POST['start_time'][$day] ?? '09:00';
        $end    = $_POST['end_time'][$day] ?? '18:00';
        $pdo->prepare("UPDATE working_hours SET start_time=?, end_time=?, is_day_off=? WHERE staff_id=? AND day_of_week=?")
            ->execute([$start, $end, $is_off, $staff_id, $day]);
    }
    $message = 'Munkaidő mentve!';
}

// ── SZERKESZTÉS betöltése ──
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_staff = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
    $edit_staff->execute([$_GET['edit']]);
    $edit_staff = $edit_staff->fetch();
}

// ── MUNKAIDŐ SZERKESZTÉSE ──
$hours_staff = null;
if (isset($_GET['hours']) && is_numeric($_GET['hours'])) {
    $hours_staff = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
    $hours_staff->execute([$_GET['hours']]);
    $hours_staff = $hours_staff->fetch();

    $working_hours = $pdo->prepare("SELECT * FROM working_hours WHERE staff_id = ? ORDER BY day_of_week");
    $working_hours->execute([$_GET['hours']]);
    $working_hours = $working_hours->fetchAll(PDO::FETCH_ASSOC + [PDO::FETCH_UNIQUE => false]);
    $wh_by_day = [];
    foreach ($working_hours as $wh) {
        $wh_by_day[$wh['day_of_week']] = $wh;
    }
}

// ── LISTA ──
$staff_list = $pdo->query("SELECT * FROM staff ORDER BY sort_order ASC, name ASC")->fetchAll();
$all_services = $pdo->query("SELECT * FROM services WHERE active=1 ORDER BY sort_order")->fetchAll();

// Kozmetikusok szolgáltatásai
$staff_services_map = [];
$ss_rows = $pdo->query("SELECT * FROM staff_services")->fetchAll();
foreach ($ss_rows as $row) {
    $staff_services_map[$row['staff_id']][] = $row['service_id'];
}

$days_hu = ['Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat','Vasárnap'];
$page_title = 'Kozmetikusok';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
        <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
        <?= e($message) ?>
    </div>
<?php endif; ?>

<!-- REST UGYANAZ, MINT ELŐTTE... -->

<?php if ($hours_staff): ?>
<!-- ── MUNKAIDŐ SZERKESZTŐ ── -->
<div class="page-header">
    <h2><i class="fas fa-clock"></i> Munkaidő – <?= e($hours_staff['name']) ?></h2>
    <a href="staff.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Vissza</a>
</div>

<div class="card">
    <form method="POST" action="staff.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="save_hours" value="1">
        <input type="hidden" name="staff_id" value="<?= $hours_staff['id'] ?>">

        <table class="admin-table hours-table">
            <thead>
                <tr>
                    <th>Nap</th>
                    <th>Nyitás</th>
                    <th>Zárás</th>
                    <th>Szabadnap</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($day = 0; $day <= 6; $day++): ?>
                <?php $wh = $wh_by_day[$day] ?? ['start_time'=>'09:00','end_time'=>'18:00','is_day_off'=>0]; ?>
                <tr class="<?= $wh['is_day_off'] ? 'day-off-row' : '' ?>" id="row-day-<?= $day ?>">
                    <td><strong><?= $days_hu[$day] ?></strong></td>
                    <td>
                        <input type="time" name="start_time[<?= $day ?>]"
                               value="<?= substr($wh['start_time'],0,5) ?>"
                               class="time-input" <?= $wh['is_day_off'] ? 'disabled' : '' ?>>
                    </td>
                    <td>
                        <input type="time" name="end_time[<?= $day ?>]"
                               value="<?= substr($wh['end_time'],0,5) ?>"
                               class="time-input" <?= $wh['is_day_off'] ? 'disabled' : '' ?>>
                    </td>
                    <td>
                        <label class="toggle-switch">
                            <input type="checkbox" name="day_off[<?= $day ?>]"
                                   <?= $wh['is_day_off'] ? 'checked' : '' ?>
                                   onchange="toggleDay(<?= $day ?>, this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Munkaidő mentése
            </button>
        </div>
    </form>
</div>

<?php else: ?>
<!-- ── LISTA + FORM ── -->
<div class="page-header">
    <h2><i class="fas fa-user-tie"></i> Kozmetikusok kezelése</h2>
    <button class="btn btn-primary" data-modal-open="staffModal"
            onclick="openStaffModal()">
        <i class="fas fa-plus"></i> Új kozmetikus
    </button>
</div>

<div class="card">
    <div style="margin-bottom:16px;">
        <input type="text" id="tableSearch" placeholder="🔍 Keresés..." style="max-width:300px;">
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Fotó</th>
                <th>Név</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Szolgáltatások</th>
                <th>Sorrend</th>
                <th>Státusz</th>
                <th>Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($staff_list): ?>
            <?php foreach ($staff_list as $s): ?>
            <tr>
                <td>
                    <?php if ($s['photo']): ?>
                        <img src="<?= UPLOAD_URL . e($s['photo']) ?>" alt="" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <div style="width:44px;height:44px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#999;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td><strong><?= e($s['name']) ?></strong></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['phone']) ?></td>
                <td>
                    <?php
                    $svc_ids = $staff_services_map[$s['id']] ?? [];
                    echo count($svc_ids) . ' db';
                    ?>
                </td>
                <td><?= $s['sort_order'] ?></td>
                <td>
                    <span class="badge <?= $s['active'] ? 'badge-confirmed' : 'badge-cancelled' ?>">
                        <?= $s['active'] ? 'Aktív' : 'Inaktív' ?>
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="staff.php?hours=<?= $s['id'] ?>" class="action-btn view" title="Munkaidő">
                            <i class="fas fa-clock"></i>
                        </a>
                        <button class="action-btn edit" title="Szerkesztés"
                            onclick="openStaffModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($staff_services_map[$s['id']] ?? []), ENT_QUOTES) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="staff.php?delete=<?= $s['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="action-btn delete" title="Törlés"
                           data-confirm="Biztosan törölni szeretnéd ezt a kozmetikust? Minden foglalása is törlődik!">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:#999;">Még nincs kozmetikus felvéve.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ── MODAL: Kozmetikus szerkesztő ── -->
<div class="modal-overlay" id="staffModal">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-user-tie"></i> Kozmetikus hozzáadása</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="staff.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_staff" value="1">
            <input type="hidden" name="id" id="staffId" value="0">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Teljes név *</label>
                        <input type="text" name="name" id="staffName" required placeholder="pl. Kovács Péter">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" id="staffEmail" placeholder="anna@kozmetika.hu">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Telefon</label>
                        <input type="text" name="phone" id="staffPhone" placeholder="+36 30 123 4567">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-sort"></i> Sorrend</label>
                        <input type="number" name="sort_order" id="staffSort" value="0" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Bemutatkozás</label>
                    <textarea name="bio" id="staffBio" rows="3" placeholder="Rövid bemutatkozás..."></textarea>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Profilfotó (max 2MB, jpg/png/webp)</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                    <div id="currentPhoto" style="margin-top:8px;"></div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-concierge-bell"></i> Nyújtott szolgáltatások</label>
                    <div class="services-checkboxes">
                        <?php foreach ($all_services as $svc): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="services[]"
                                   value="<?= $svc['id'] ?>"
                                   class="svc-checkbox"
                                   data-id="<?= $svc['id'] ?>">
                            <?= e($svc['name']) ?>
                            <small>(<?= $svc['duration'] ?> perc – <?= number_format($svc['price'],0,',',' ') ?> Ft)</small>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" id="staffActive" checked>
                        <strong>Aktív kozmetikus</strong> (látható a foglalási rendszerben)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Mégse</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.services-checkboxes { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border); transition: all .2s; }
.checkbox-label:hover { border-color: var(--accent); background: #fff5f6; }
.checkbox-label input[type=checkbox] { accent-color: var(--accent); width: 16px; height: 16px; flex-shrink: 0; }
.checkbox-label small { color: var(--text-muted); font-size: 11px; }
.hours-table input.time-input { padding: 6px 10px; border: 2px solid var(--border); border-radius: 6px; font-size: 14px; }
.day-off-row td { opacity: .45; }
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; inset: 0; background: #ccc; border-radius: 24px; cursor: pointer; transition: .3s; }
.toggle-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .3s; }
.toggle-switch input:checked + .toggle-slider { background: var(--accent); }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }
</style>

<script>
function openStaffModal(staff = null, services = []) {
    document.getElementById('modalTitle').innerHTML =
        staff ? '<i class="fas fa-edit"></i> Kozmetikus szerkesztése' : '<i class="fas fa-user-tie"></i> Kozmetikus hozzáadása';
    document.getElementById('staffId').value    = staff ? staff.id : 0;
    document.getElementById('staffName').value  = staff ? staff.name : '';
    document.getElementById('staffEmail').value = staff ? staff.email : '';
    document.getElementById('staffPhone').value = staff ? staff.phone : '';
    document.getElementById('staffBio').value   = staff ? staff.bio : '';
    document.getElementById('staffSort').value  = staff ? staff.sort_order : 0;
    document.getElementById('staffActive').checked = staff ? staff.active == 1 : true;

    // Fotó preview
    const photoDiv = document.getElementById('currentPhoto');
    photoDiv.innerHTML = (staff && staff.photo)
        ? `<img src="<?= UPLOAD_URL ?>${staff.photo}" style="height:60px;border-radius:8px;"> <small>Jelenlegi fotó</small>`
        : '';

    // Szolgáltatások checkboxok
    document.querySelectorAll('.svc-checkbox').forEach(cb => {
        cb.checked = services.includes(parseInt(cb.dataset.id));
    });

    document.getElementById('staffModal').classList.add('open');
}

function toggleDay(day, isOff) {
    const row = document.getElementById('row-day-' + day);
    row.classList.toggle('day-off-row', isOff);
    row.querySelectorAll('input.time-input').forEach(i => i.disabled = isOff);
}
</script>

<?php require_once 'partials/footer.php'; ?>