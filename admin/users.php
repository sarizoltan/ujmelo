<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

// Csak superadmin férhet hozzá
if (current_admin()['role'] !== 'superadmin') {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$message = '';
$message_type = 'success';

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (csrf_verify()) {
        $del_id = (int)$_GET['delete'];
        if ($del_id === (int)current_admin()['id']) {
            $message = 'Saját magadat nem törölheted!';
            $message_type = 'error';
        } else {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$del_id]);
            $message = 'Felhasználó törölve!';
        }
    }
}

// ── STÁTUSZ TOGGLE ──
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    if (csrf_verify()) {
        $tog_id = (int)$_GET['toggle'];
        if ($tog_id === (int)current_admin()['id']) {
            $message = 'Saját magadat nem kapcsolhatod ki!';
            $message_type = 'error';
        } else {
            $pdo->prepare("UPDATE users SET active = 1 - active WHERE id=?")->execute([$tog_id]);
            $message = 'Státusz frissítve!';
        }
    }
}

// ── ÚJ / SZERKESZTÉS MENTÉS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    if (!csrf_verify()) die('CSRF hiba');

    $id       = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? 'editor';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $allowed_roles = ['superadmin', 'admin', 'editor'];
    if (!in_array($role, $allowed_roles)) $role = 'editor';

    $errors = [];
    if (!$username) $errors[] = 'Felhasználónév kötelező!';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Érvényes email kötelező!';
    if ($id === 0 && !$password) $errors[] = 'Új felhasználónál jelszó kötelező!';
    if ($password && strlen($password) < 6) $errors[] = 'A jelszónak legalább 6 karakter kell!';
    if ($password && $password !== $confirm) $errors[] = 'A két jelszó nem egyezik!';

    if (!$errors) {
        if ($id > 0) {
            // ── FRISSÍTÉS ──
            if ($password) {
                $pdo->prepare("UPDATE users SET username=?,email=?,role=?,password_hash=? WHERE id=?")
                    ->execute([$username, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $pdo->prepare("UPDATE users SET username=?,email=?,role=? WHERE id=?")
                    ->execute([$username, $email, $role, $id]);
            }
            $message = 'Felhasználó frissítve!';
        } else {
            // ── ÚJ ──
            try {
                $pdo->prepare("INSERT INTO users (username,email,role,password_hash) VALUES (?,?,?,?)")
                    ->execute([$username, $email, $role, password_hash($password, PASSWORD_DEFAULT)]);
                $message = 'Felhasználó létrehozva!';
            } catch (PDOException $e) {
                $message = 'Ez a felhasználónév már foglalt!';
                $message_type = 'error';
            }
        }
    } else {
        $message = implode(' ', $errors);
        $message_type = 'error';
    }
}

// ── LISTA ──
$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, username ASC")->fetchAll();

$role_labels = [
    'superadmin' => ['label' => 'Super Admin', 'color' => 'badge-cancelled'],
    'admin'      => ['label' => 'Admin',       'color' => 'badge-confirmed'],
    'editor'     => ['label' => 'Szerkesztő',  'color' => 'badge-draft'],
];

$role_permissions = [
    'superadmin' => ['Teljes hozzáférés', 'Felhasználók kezelése', 'Beállítások', 'Minden modul'],
    'admin'      => ['Foglalások', 'Műkörmösök', 'Szolgáltatások', 'Blog', 'Oldalak', 'Média', 'Üzenetek'],
    'editor'     => ['Blog szerkesztés', 'Oldalak szerkesztés', 'Média feltöltés'],
];

$page_title = 'Felhasználók';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-users"></i> Felhasználók kezelése</h2>
    <button class="btn btn-primary" onclick="openUserModal()">
        <i class="fas fa-user-plus"></i> Új felhasználó
    </button>
</div>

<!-- Szerepkörök magyarázata -->
<div class="role-info-grid">
    <?php foreach ($role_permissions as $role => $perms): ?>
    <div class="role-info-card">
        <div class="role-info-header">
            <span class="badge <?= $role_labels[$role]['color'] ?>">
                <?= $role_labels[$role]['label'] ?>
            </span>
        </div>
        <ul class="role-perm-list">
            <?php foreach ($perms as $perm): ?>
                <li><i class="fas fa-check"></i> <?= $perm ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>
</div>

<!-- Felhasználók táblázat -->
<div class="card">
    <div style="margin-bottom:16px;">
        <input type="text" id="tableSearch" placeholder="🔍 Keresés..." style="max-width:300px;">
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Felhasználónév</th>
                <th>Email</th>
                <th>Szerepkör</th>
                <th>Létrehozva</th>
                <th>Státusz</th>
                <th>Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="user-avatar">
                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <strong><?= e($u['username']) ?></strong>
                            <?php if ($u['id'] == current_admin()['id']): ?>
                                <span style="font-size:11px;color:var(--accent);margin-left:4px;">(te)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><?= e($u['email']) ?></td>
                <td>
                    <span class="badge <?= $role_labels[$u['role']]['color'] ?? 'badge-draft' ?>">
                        <?= $role_labels[$u['role']]['label'] ?? $u['role'] ?>
                    </span>
                </td>
                <td style="font-size:12px;color:var(--text-muted);">
                    <?= date('Y.m.d', strtotime($u['created_at'])) ?>
                </td>
                <td>
                    <?php if ($u['id'] != current_admin()['id']): ?>
                    <a href="users.php?toggle=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>"
                       class="badge <?= ($u['active'] ?? 1) ? 'badge-confirmed' : 'badge-cancelled' ?>"
                       style="cursor:pointer;text-decoration:none;">
                        <?= ($u['active'] ?? 1) ? 'Aktív' : 'Inaktív' ?>
                    </a>
                    <?php else: ?>
                    <span class="badge badge-confirmed">Aktív</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <button class="action-btn edit" title="Szerkesztés"
                            onclick="openUserModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($u['id'] != current_admin()['id']): ?>
                        <a href="users.php?delete=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>"
                           class="action-btn delete" title="Törlés"
                           data-confirm="Biztosan törölni szeretnéd a(z) '<?= e($u['username']) ?>' felhasználót?">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ── MODAL: Felhasználó szerkesztő ── -->
<div class="modal-overlay" id="userModal">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <h3 id="userModalTitle"><i class="fas fa-user-plus"></i> Új felhasználó</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="users.php" id="userForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_user" value="1">
            <input type="hidden" name="id" id="userId" value="0">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Felhasználónév *</label>
                        <input type="text" name="username" id="uUsername"
                               required placeholder="pl. kovacs.peter"
                               autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" name="email" id="uEmail"
                               required placeholder="hello@nailsalon.hu"
                               autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> Szerepkör *</label>
                    <select name="role" id="uRole">
                        <option value="editor">Szerkesztő</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                    <div class="role-desc" id="roleDesc"></div>
                </div>

                <div style="background:#f9fafb;border-radius:8px;padding:16px;margin-bottom:4px;">
                    <p style="font-size:13px;font-weight:600;margin-bottom:12px;color:var(--text);">
                        <i class="fas fa-key" style="color:var(--accent);"></i>
                        Jelszó <span id="passRequired" style="color:var(--red);">*</span>
                        <span id="passOptional" style="color:var(--text-muted);font-weight:400;display:none;">(hagyja üresen a megtartáshoz)</span>
                    </p>
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom:0;">
                            <input type="password" name="password" id="uPassword"
                                   placeholder="Jelszó (min. 6 karakter)"
                                   autocomplete="new-password">
                            <div class="password-strength" id="uPassStrength" style="height:4px;border-radius:2px;margin-top:6px;transition:all .3s;"></div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <input type="password" name="confirm_password" id="uConfirm"
                                   placeholder="Jelszó megerősítése"
                                   autocomplete="new-password">
                            <div id="uPassMatch" style="font-size:11px;margin-top:4px;height:14px;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Mégse</button>
                <button type="submit" class="btn btn-primary" id="userSaveBtn">
                    <i class="fas fa-save"></i> Mentés
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.role-info-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
@media(max-width:768px) { .role-info-grid { grid-template-columns:1fr; } }
.role-info-card { background:var(--white); border-radius:var(--radius); border:1px solid var(--border); padding:16px; box-shadow:var(--shadow); }
.role-info-header { margin-bottom:10px; }
.role-perm-list { list-style:none; display:flex; flex-direction:column; gap:5px; }
.role-perm-list li { font-size:12px; color:var(--text-light); display:flex; align-items:center; gap:6px; }
.role-perm-list li i { color:var(--green); font-size:10px; }
.user-avatar { width:36px; height:36px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; flex-shrink:0; }
.role-desc { margin-top:8px; font-size:12px; color:var(--text-muted); padding:8px 12px; background:#f9fafb; border-radius:6px; min-height:32px; }
</style>

<script>
const roleDescriptions = {
    superadmin: '⚡ Teljes hozzáférés mindenhez, beleértve a felhasználók és beállítások kezelését.',
    admin:      '🔧 Foglalások, műkörmösök, szolgáltatások, blog, oldalak, média és üzenetek kezelése.',
    editor:     '✏️ Csak blog bejegyzések és oldalak szerkesztése, média feltöltés.'
};

function openUserModal(user = null) {
    const isEdit = user !== null;
    document.getElementById('userModalTitle').innerHTML = isEdit
        ? '<i class="fas fa-user-edit"></i> Felhasználó szerkesztése'
        : '<i class="fas fa-user-plus"></i> Új felhasználó';

    document.getElementById('userId').value    = user ? user.id : 0;
    document.getElementById('uUsername').value = user ? user.username : '';
    document.getElementById('uEmail').value    = user ? user.email : '';
    document.getElementById('uRole').value     = user ? user.role : 'editor';
    document.getElementById('uPassword').value = '';
    document.getElementById('uConfirm').value  = '';

    // Jelszó mező kezelés
    document.getElementById('passRequired').style.display  = isEdit ? 'none' : 'inline';
    document.getElementById('passOptional').style.display  = isEdit ? 'inline' : 'none';
    document.getElementById('uPassword').required = !isEdit;

    updateRoleDesc();
    document.getElementById('userModal').classList.add('open');
}

function updateRoleDesc() {
    const role = document.getElementById('uRole').value;
    document.getElementById('roleDesc').textContent = roleDescriptions[role] || '';
}
document.getElementById('uRole')?.addEventListener('change', updateRoleDesc);

// Jelszó erősség
document.getElementById('uPassword')?.addEventListener('input', function() {
    const val = this.value;
    const bar = document.getElementById('uPassStrength');
    let strength = 0;
    if (val.length >= 6)  strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;
    const colors = ['','var(--red)','var(--orange)','var(--orange)','var(--blue)','var(--green)'];
    const widths = ['0%','20%','40%','60%','80%','100%'];
    bar.style.background = val.length ? (colors[strength] || 'var(--green)') : '#eee';
    bar.style.width = val.length ? (widths[strength] || '100%') : '0%';

    checkMatch();
});

// Jelszó egyezés
document.getElementById('uConfirm')?.addEventListener('input', checkMatch);
function checkMatch() {
    const pass    = document.getElementById('uPassword').value;
    const confirm = document.getElementById('uConfirm').value;
    const matchEl = document.getElementById('uPassMatch');
    if (!confirm) { matchEl.textContent = ''; return; }
    if (pass === confirm) {
        matchEl.innerHTML = '<span style="color:var(--green);">✅ A jelszavak egyeznek</span>';
    } else {
        matchEl.innerHTML = '<span style="color:var(--red);">❌ A jelszavak nem egyeznek</span>';
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>