<?php
$base_path = '';
require_once __DIR__ . '/config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_role = $_SESSION['role'] ?? '';
$division_id = $_SESSION['division_id'] ?? 0;

// Fetch all notifications depending on role
if ($user_role === 'admin') {
    $stmt = $pdo->prepare("
        SELECT n.*, d.name AS division_name, f.area AS area_name 
        FROM notifications n
        LEFT JOIN divisions d ON n.division_id = d.id
        LEFT JOIN findings f ON n.finding_id = f.id
        WHERE n.recipient_role = 'admin'
        ORDER BY n.created_at DESC
    ");
    $stmt->execute();
} else {
    if (!empty($_SESSION['area_name'])) {
        $stmt = $pdo->prepare("
            SELECT n.*, d.name AS division_name, f.area AS area_name 
            FROM notifications n
            LEFT JOIN divisions d ON n.division_id = d.id
            LEFT JOIN findings f ON n.finding_id = f.id
            WHERE n.recipient_role = 'division' AND n.division_id = :div_id AND f.area = :area_name
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([
            'div_id' => $division_id,
            'area_name' => $_SESSION['area_name']
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT n.*, d.name AS division_name, f.area AS area_name 
            FROM notifications n
            LEFT JOIN divisions d ON n.division_id = d.id
            LEFT JOIN findings f ON n.finding_id = f.id
            WHERE n.recipient_role = 'division' AND n.division_id = :div_id
            ORDER BY n.created_at DESC
        ");
        $stmt->execute(['div_id' => $division_id]);
    }
}
$all_notifications = $stmt->fetchAll();

// Mark all as read if action requested
if (isset($_GET['action']) && $_GET['action'] === 'read_all') {
    if ($user_role === 'admin') {
        $update_stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_role = 'admin'");
        $update_stmt->execute();
    } else {
        if (!empty($_SESSION['area_name'])) {
            $update_stmt = $pdo->prepare("
                UPDATE notifications n
                JOIN findings f ON n.finding_id = f.id
                SET n.is_read = 1 
                WHERE n.recipient_role = 'division' AND n.division_id = :div_id AND f.area = :area_name
            ");
            $update_stmt->execute([
                'div_id' => $division_id,
                'area_name' => $_SESSION['area_name']
            ]);
        } else {
            $update_stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_role = 'division' AND division_id = :div_id");
            $update_stmt->execute(['div_id' => $division_id]);
        }
    }
    header("Location: notifications.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Notifikasi Masuk</h1>
        <p class="page-subtitle">Daftar pemberitahuan aktivitas audit 5R dan perbaikan divisi</p>
    </div>
    <?php
    $has_unread = false;
    foreach ($all_notifications as $notif) {
        if (!$notif['is_read']) {
            $has_unread = true;
            break;
        }
    }
    if ($has_unread):
    ?>
        <a href="?action=read_all" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem;">
            <span class="material-symbols-rounded">done_all</span> Tandai Semua Dibaca
        </a>
    <?php endif; ?>
</div>

<div class="notif-list-container">
    <?php if (empty($all_notifications)): ?>
        <div class="card-section" style="padding: 3rem; text-align: center; color: var(--text-secondary);">
            <span class="material-symbols-rounded" style="font-size: 3rem; margin-bottom: 1rem; color: var(--border-color);">notifications_off</span>
            <p>Tidak ada notifikasi yang terdaftar saat ini.</p>
        </div>
    <?php else: ?>
        <?php foreach ($all_notifications as $notif): ?>
            <div class="notif-list-card <?= $notif['is_read'] ? 'read' : 'unread' ?>" data-id="<?= $notif['id'] ?>">
                <div class="notif-list-card-left">
                    <div class="notif-icon" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: <?= $notif['is_read'] ? 'rgba(255, 255, 255, 0.03)' : 'rgba(99, 102, 241, 0.15)' ?>; color: <?= $notif['is_read'] ? 'var(--text-secondary)' : 'var(--accent)' ?>;">
                        <span class="material-symbols-rounded" style="font-size: 1.35rem;">
                            <?= $notif['title'] === 'Temuan Audit Baru' ? 'assignment' : 'build' ?>
                        </span>
                    </div>
                    <div class="notif-list-card-content">
                        <h3 class="notif-list-title" style="color: <?= $notif['is_read'] ? 'var(--text-secondary)' : 'var(--text-primary)' ?>;"><?= htmlspecialchars($notif['title']) ?></h3>
                        <p class="notif-list-message" style="color: var(--text-secondary);"><?= htmlspecialchars($notif['message']) ?></p>
                        <span class="notif-list-time"><?= date('d F Y, H:i', strtotime($notif['created_at'])) ?></span>
                    </div>
                </div>
                <div>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="admin/findings.php?filter_area=<?= urlencode($notif['area_name'] ?? '') ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                            Lihat Temuan
                        </a>
                    <?php else: ?>
                        <a href="division/improve.php?id=<?= $notif['finding_id'] ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                            Tindak Lanjut
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Dynamically mark single as read on page load/scroll if clicked or list cards are clicked
    const listCards = document.querySelectorAll('.notif-list-card.unread');
    listCards.forEach(card => {
        card.addEventListener('mouseover', () => {
            const notifId = card.getAttribute('data-id');
            fetch('notifications_action.php?action=read&id=' + notifId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        card.classList.remove('unread');
                        card.classList.add('read');
                        
                        // Decrement main bell badge if exists
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            let count = parseInt(badge.textContent, 10);
                            count--;
                            if (count > 0) {
                                badge.textContent = count;
                            } else {
                                badge.remove();
                            }
                        }
                    }
                })
                .catch(err => console.error(err));
        }, { once: true });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
