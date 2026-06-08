<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

// Statisztikák
$stats = [];

$stats['bookings_today'] = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE() AND status != 'cancelled'")->fetchColumn();
$stats['bookings_pending'] = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$stats['bookings_total'] = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$stats['staff_active'] = $pdo->query("SELECT COUNT(*) FROM staff WHERE active = 1")->fetchColumn();
$stats['messages_new'] = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
$stats['posts_published'] = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();

// Mai foglalások
$today_bookings = $pdo->query("
    SELECT b.*, s.name as staff_name, sv.name as service_name
    FROM bookings b
    JOIN staff s ON b.staff_id = s.id
    JOIN services sv ON b.service_id = sv.id
    WHERE b.booking_date = CURDATE() AND b.status != 'cancelled'
    ORDER BY b.start_time ASC
    LIMIT 10
")->fetchAll();

// Legutóbbi foglalások
$recent_bookings = $pdo->query("
    SELECT b.*, s.name as staff_name, sv.name as service_name
    FROM bookings b
    JOIN staff s ON b.staff_id = s.id
    JOIN services sv ON b.service_id = sv.id
    ORDER BY b.created_at DESC
    LIMIT 8
")->fetchAll();

$page_title = 'Dashboard';
require_once 'partials/header.php';
?>

<div class="dashboard-stats">
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['bookings_today'] ?></span>
            <span class="stat-label">Mai kezelés</span>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['bookings_pending'] ?></span>
            <span class="stat-label">Függőben lévő</span>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['bookings_total'] ?></span>
            <span class="stat-label">Összes foglalás</span>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['staff_active'] ?></span>
            <span class="stat-label">Aktív műkörmös</span>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="fas fa-envelope"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['messages_new'] ?></span>
            <span class="stat-label">Új üzenet</span>
        </div>
    </div>
    <div class="stat-card teal">
        <div class="stat-icon"><i class="fas fa-blog"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= $stats['posts_published'] ?></span>
            <span class="stat-label">Blog bejegyzés</span>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Mai foglalások -->
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-calendar-day"></i> Mai foglalások</h3>
            <a href="bookings.php" class="btn-sm">Összes</a>
        </div>
        <div class="dash-card-body">
            <?php if ($today_bookings): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Időpont</th>
                        <th>Ügyfél</th>
                        <th>Műkörmös</th>
                        <th>Kezelés</th>
                        <th>Státusz</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($today_bookings as $b): ?>
                    <tr>
                        <td><strong><?= e(format_time($b['start_time'])) ?></strong></td>
                        <td><?= e($b['customer_name']) ?></td>
                        <td><?= e($b['staff_name']) ?></td>
                        <td><?= e($b['service_name']) ?></td>
                        <td><span class="badge badge-<?= $b['status'] ?>"><?= booking_status_label($b['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>Ma nincs foglalás.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Legutóbbi foglalások -->
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-history"></i> Legutóbbi foglalások</h3>
            <a href="bookings.php" class="btn-sm">Összes</a>
        </div>
        <div class="dash-card-body">
            <?php if ($recent_bookings): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ref.</th>
                        <th>Ügyfél</th>
                        <th>Dátum</th>
                        <th>Státusz</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_bookings as $b): ?>
                    <tr>
                        <td><code><?= e($b['booking_ref']) ?></code></td>
                        <td><?= e($b['customer_name']) ?></td>
                        <td><?= e(format_date($b['booking_date'])) ?></td>
                        <td><span class="badge badge-<?= $b['status'] ?>"><?= booking_status_label($b['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Még nincs foglalás.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>