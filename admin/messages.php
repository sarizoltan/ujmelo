<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$message = '';
$message_type = 'success';

// ── TÖRLÉS ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (csrf_verify()) {
        $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([$_GET['delete']]);
        $message = 'Üzenet törölve!';
    }
}

// ── ÖSSZES OLVASOTT ──
if (isset($_GET['mark_all_read'])) {
    if (csrf_verify()) {
        $pdo->query("UPDATE contact_messages SET status='read' WHERE status='new'");
        $message = 'Összes üzenet olvasottnak jelölve!';
    }
}

// ── STÁTUSZ VÁLTOZTATÁS ──
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    if (csrf_verify()) {
        $allowed = ['new','read','replied'];
        if (in_array($_GET['status'], $allowed)) {
            $pdo->prepare("UPDATE contact_messages SET status=? WHERE id=?")
                ->execute([$_GET['status'], (int)$_GET['id']]);
        }
    }
}

// ── ÜZENET MEGNYITÁSAKOR OLVASOTTRA ÁLLÍTÁS ──
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $pdo->prepare("UPDATE contact_messages SET status='read' WHERE id=? AND status='new'")
        ->execute([$_GET['view']]);
}

// ── SZŰRŐK ──
$filter_status = $_GET['fstatus'] ?? '';
$search        = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filter_status) {
    $where[] = 'status = ?';
    $params[] = $filter_status;
}
if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Statisztikák
$stats = $pdo->query("SELECT status, COUNT(*) as cnt FROM contact_messages GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// Megnyitott üzenet
$open_msg = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id=?");
    $stmt->execute([$_GET['view']]);
    $open_msg = $stmt->fetch();
}

$page_title = 'Üzenetek';
require_once 'partials/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-envelope"></i> Kapcsolati üzenetek</h2>
    <div style="display:flex;gap:8px;">
        <?php if (($stats['new'] ?? 0) > 0): ?>
        <a href="messages.php?mark_all_read=1&csrf_token=<?= csrf_token() ?>"
           class="btn btn-secondary"
           data-confirm="Összes üzenetet olvasottnak jelölöd?">
            <i class="fas fa-check-double"></i> Mind olvasott
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Összesítő -->
<div class="msg-stats">
    <a href="messages.php" class="msg-stat-item <?= !$filter_status ? 'active' : '' ?>">
        <i class="fas fa-inbox"></i>
        <span>Összes</span>
        <strong><?= array_sum($stats) ?></strong>
    </a>
    <a href="messages.php?fstatus=new" class="msg-stat-item new <?= $filter_status==='new' ? 'active' : '' ?>">
        <i class="fas fa-envelope"></i>
        <span>Új</span>
        <strong><?= $stats['new'] ?? 0 ?></strong>
    </a>
    <a href="messages.php?fstatus=read" class="msg-stat-item <?= $filter_status==='read' ? 'active' : '' ?>">
        <i class="fas fa-envelope-open"></i>
        <span>Olvasott</span>
        <strong><?= $stats['read'] ?? 0 ?></strong>
    </a>
    <a href="messages.php?fstatus=replied" class="msg-stat-item <?= $filter_status==='replied' ? 'active' : '' ?>">
        <i class="fas fa-reply"></i>
        <span>Megválaszolt</span>
        <strong><?= $stats['replied'] ?? 0 ?></strong>
    </a>
</div>

<div class="messages-layout">
    <!-- Üzenet lista -->
    <div class="messages-list-panel">
        <div class="card" style="padding:0;overflow:hidden;">
            <!-- Kereső -->
            <div style="padding:14px 16px;border-bottom:1px solid var(--border);">
                <form method="GET" action="messages.php" style="display:flex;gap:8px;">
                    <input type="hidden" name="fstatus" value="<?= e($filter_status) ?>">
                    <input type="text" name="search" value="<?= e($search) ?>"
                           placeholder="🔍 Keresés..." style="flex:1;">
                    <button type="submit" class="btn btn-secondary" style="padding:8px 12px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <?php if ($messages): ?>
            <ul class="message-inbox">
                <?php foreach ($messages as $msg): ?>
                <li class="message-item <?= $msg['status'] === 'new' ? 'unread' : '' ?> <?= (isset($_GET['view']) && $_GET['view'] == $msg['id']) ? 'active' : '' ?>">
                    <a href="messages.php?view=<?= $msg['id'] ?><?= $filter_status ? '&fstatus='.$filter_status : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                        <div class="msg-item-header">
                            <span class="msg-sender">
                                <?php if ($msg['status'] === 'new'): ?>
                                    <span class="unread-dot"></span>
                                <?php endif; ?>
                                <?= e($msg['name']) ?>
                            </span>
                            <span class="msg-date"><?= date('m.d H:i', strtotime($msg['created_at'])) ?></span>
                        </div>
                        <div class="msg-email"><?= e($msg['email']) ?></div>
                        <div class="msg-preview"><?= e(mb_substr($msg['message'], 0, 80)) ?>...</div>
                        <div class="msg-status-badge">
                            <span class="badge badge-<?= $msg['status'] ?>">
                                <?= match($msg['status']) {
                                    'new'     => 'Új',
                                    'read'    => 'Olvasott',
                                    'replied' => 'Megválaszolt',
                                    default   => $msg['status']
                                } ?>
                            </span>
                        </div>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state" style="padding:60px 20px;">
                <i class="fas fa-inbox"></i>
                <p>Nincs üzenet a megadott szűrőkkel.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Üzenet nézet -->
    <div class="message-view-panel">
        <?php if ($open_msg): ?>
        <div class="card">
            <div class="msg-view-header">
                <div>
                    <h3><?= e($open_msg['name']) ?></h3>
                    <a href="mailto:<?= e($open_msg['email']) ?>" style="color:var(--blue);">
                        <i class="fas fa-envelope"></i> <?= e($open_msg['email']) ?>
                    </a>
                    <?php if ($open_msg['phone']): ?>
                    <br>
                    <a href="tel:<?= e($open_msg['phone']) ?>" style="color:var(--green);">
                        <i class="fas fa-phone"></i> <?= e($open_msg['phone']) ?>
                    </a>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;">
                    <div style="color:var(--text-muted);font-size:13px;">
                        <i class="fas fa-clock"></i>
                        <?= date('Y. m. d. H:i', strtotime($open_msg['created_at'])) ?>
                    </div>
                    <span class="badge badge-<?= $open_msg['status'] ?>" style="margin-top:6px;display:inline-block;">
                        <?= match($open_msg['status']) {
                            'new'     => 'Új',
                            'read'    => 'Olvasott',
                            'replied' => 'Megválaszolt',
                            default   => $open_msg['status']
                        } ?>
                    </span>
                </div>
            </div>

            <div class="msg-view-body">
                <?= nl2br(e($open_msg['message'])) ?>
            </div>

            <div class="msg-view-actions">
                <a href="mailto:<?= e($open_msg['email']) ?>?subject=Re: Műkörmös Szalon üzenet"
                   class="btn btn-primary"
                   onclick="markReplied(<?= $open_msg['id'] ?>)">
                    <i class="fas fa-reply"></i> Válasz emailben
                </a>
                <?php if ($open_msg['status'] !== 'replied'): ?>
                <a href="messages.php?id=<?= $open_msg['id'] ?>&status=replied&csrf_token=<?= csrf_token() ?>&view=<?= $open_msg['id'] ?>"
                   class="btn btn-success">
                    <i class="fas fa-check"></i> Megválaszolva
                </a>
                <?php endif; ?>
                <a href="messages.php?delete=<?= $open_msg['id'] ?>&csrf_token=<?= csrf_token() ?>"
                   class="btn btn-danger"
                   data-confirm="Biztosan törlöd ezt az üzenetet?">
                    <i class="fas fa-trash"></i> Törlés
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state" style="padding:80px 20px;">
                <i class="fas fa-envelope-open-text"></i>
                <p>Válassz egy üzenetet a bal oldali listából.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.msg-stats { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.msg-stat-item { display:flex; align-items:center; gap:8px; background:var(--white); border:2px solid var(--border); border-radius:var(--radius); padding:12px 20px; text-decoration:none; color:var(--text); transition:all .2s; }
.msg-stat-item:hover, .msg-stat-item.active { border-color:var(--accent); color:var(--accent); }
.msg-stat-item.new strong { color:var(--red); }
.msg-stat-item i { font-size:16px; }
.msg-stat-item strong { font-size:20px; font-weight:700; margin-left:4px; }
.messages-layout { display:grid; grid-template-columns:340px 1fr; gap:20px; align-items:start; }
@media(max-width:900px) { .messages-layout { grid-template-columns:1fr; } }
.message-inbox { list-style:none; }
.message-item a { display:block; padding:14px 16px; text-decoration:none; color:var(--text); border-bottom:1px solid #f0f0f0; transition:background .2s; }
.message-item a:hover { background:#fafafa; }
.message-item.unread a { background:#fffbf0; }
.message-item.active a { background:#fff5f6; border-left:3px solid var(--accent); }
.msg-item-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2px; }
.msg-sender { font-weight:700; font-size:14px; display:flex; align-items:center; gap:6px; }
.msg-date { font-size:11px; color:var(--text-muted); }
.msg-email { font-size:12px; color:var(--blue); margin-bottom:4px; }
.msg-preview { font-size:12px; color:var(--text-muted); line-height:1.5; }
.msg-status-badge { margin-top:6px; }
.unread-dot { width:8px; height:8px; border-radius:50%; background:var(--accent); display:inline-block; flex-shrink:0; }
.msg-view-header { display:flex; justify-content:space-between; align-items:flex-start; padding-bottom:20px; border-bottom:1px solid var(--border); margin-bottom:20px; }
.msg-view-header h3 { font-size:20px; font-weight:700; color:var(--primary); margin-bottom:6px; }
.msg-view-body { background:#f9fafb; border-radius:var(--radius); padding:20px; font-size:15px; line-height:1.8; color:var(--text); min-height:120px; margin-bottom:20px; white-space:pre-wrap; }
.msg-view-actions { display:flex; gap:10px; flex-wrap:wrap; }
</style>

<script>
function markReplied(id) {
    fetch(`messages.php?id=${id}&status=replied&csrf_token=<?= csrf_token() ?>`);
}
</script>

<?php require_once 'partials/footer.php'; ?>