

<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? null;
$division_name = $_SESSION['division_name'] ?? null;

// Real-time division edit permission check
$akses_perbaikan = 1;
if ($is_logged_in && $user_role === 'division') {
    $div_id = $_SESSION['division_id'] ?? 0;
    if ($div_id > 0) {
        $stmt_perm = $pdo->prepare("SELECT akses_perbaikan FROM divisions WHERE id = :id");
        $stmt_perm->execute(['id' => $div_id]);
        $akses_perbaikan = intval($stmt_perm->fetchColumn() ?? 1);
    }
}

// Determine base path if not set by the page
if (!isset($base_path)) {
    $base_path = '';
}

// Get current request details to highlight active sidebar item
$request_uri = $_SERVER['PHP_SELF'];
$current_page = basename($request_uri);
$is_admin_path = strpos($request_uri, '/admin/') !== false;
$is_division_path = strpos($request_uri, '/division/') !== false;
$is_profile_page = ($current_page === 'profile.php' && !$is_admin_path && !$is_division_path);
$is_notifications_page = ($current_page === 'notifications.php' && !$is_admin_path && !$is_division_path);

// Get unread notification count depending on role (and area if restricted)
$unread_count = 0;
if ($is_logged_in) {
    if ($user_role === 'admin') {
        $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_role = 'admin' AND is_read = 0");
        $unread_stmt->execute();
    } else {
        if (!empty($_SESSION['area_name'])) {
            $unread_stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM notifications n
                JOIN findings f ON n.finding_id = f.id
                WHERE n.recipient_role = 'division' AND n.division_id = :div_id AND f.area = :area_name AND n.is_read = 0
            ");
            $unread_stmt->execute([
                'div_id' => $_SESSION['division_id'] ?? 0,
                'area_name' => $_SESSION['area_name']
            ]);
        } else {
            $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_role = 'division' AND division_id = :div_id AND is_read = 0");
            $unread_stmt->execute(['div_id' => $_SESSION['division_id'] ?? 0]);
        }
    }
    $unread_count = (int)$unread_stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Monitoring Audit 5R</title>
    <!-- CSS stylesheet -->
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css">
    <!-- Google Material Symbols Rounded -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <!-- Main JS file -->
    <script src="<?= $base_path ?>assets/js/main.js" defer></script>
</head>
<body>
    <script>
        if (localStorage.getItem('sidebar-collapsed') === 'true' && window.innerWidth > 1024) {
            document.body.classList.add('collapsed');
        }
    </script>
    <div class="app-container">
        
        <?php if ($is_logged_in): ?>
            <!-- Sidebar Backdrop for Mobile view -->
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

            <!-- Sidebar Navigation -->
            <aside class="sidebar">
                <div class="sidebar-brand">
                    <a href="<?= $base_path ?>dashboard.php" class="logo-link">
                        <div class="logo-icon">5R</div>
                        <span class="logo-text">Audit 5R</span>
                    </a>
                </div>
                
                <!-- Menu Links -->
                <nav class="sidebar-menu">
                    <?php if ($user_role === 'admin'): ?>
                        <a href="<?= $base_path ?>admin/dashboard.php" class="sidebar-link <?= ($is_admin_path && $current_page === 'dashboard.php') ? 'active' : '' ?>">
                            <span class="material-symbols-rounded">dashboard</span>
                            <span class="link-text">Dashboard</span>
                        </a>
                        <a href="<?= $base_path ?>admin/findings.php" class="sidebar-link <?= ($is_admin_path && $current_page === 'findings.php') ? 'active' : '' ?>">
                            <span class="material-symbols-rounded">assignment</span>
                            <span class="link-text">Temuan Audit</span>
                        </a>
                        <a href="<?= $base_path ?>admin/divisions.php" class="sidebar-link <?= ($is_admin_path && $current_page === 'divisions.php') ? 'active' : '' ?>">
                            <span class="material-symbols-rounded">domain</span>
                            <span class="link-text">Divisi</span>
                        </a>
                        <a href="<?= $base_path ?>admin/areas.php" class="sidebar-link <?= ($is_admin_path && $current_page === 'areas.php') ? 'active' : '' ?>">
                            <span class="material-symbols-rounded">analytics</span>
                            <span class="link-text">Kelola Area & Nilai</span>
                        </a>
                        <a href="<?= $base_path ?>admin/users.php" class="sidebar-link <?= ($is_admin_path && $current_page === 'users.php') ? 'active' : '' ?>">
                            <span class="material-symbols-rounded">manage_accounts</span>
                            <span class="link-text">Kelola User Akses</span>
                        </a>
                    <?php elseif ($user_role === 'division'): ?>
                        <a href="<?= $base_path ?>division/dashboard.php" class="sidebar-link <?= ($is_division_path && ($current_page === 'dashboard.php' || $current_page === 'improve.php')) ? 'active' : '' ?>">
                            <span class="material-symbols-rounded">dashboard</span>
                            <span class="link-text">Dashboard Divisi</span>
                        </a>
                        <?php if (empty($_SESSION['area_id'])): ?>
                            <a href="<?= $base_path ?>division/areas.php" class="sidebar-link <?= ($is_division_path && $current_page === 'areas.php') ? 'active' : '' ?>">
                                <span class="material-symbols-rounded">group</span>
                                <span class="link-text">Monitoring Area</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <a href="<?= $base_path ?>notifications.php" class="sidebar-link <?= $is_notifications_page ? 'active' : '' ?>">
                        <span class="material-symbols-rounded">notifications</span>
                        <span class="link-text">Notifikasi</span>
                        <?php if ($unread_count > 0): ?>
                            <span class="sidebar-badge" id="sidebarNotifBadge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="<?= $base_path ?>profile.php" class="sidebar-link <?= $is_profile_page ? 'active' : '' ?>">
                        <span class="material-symbols-rounded">account_circle</span>
                        <span class="link-text">Info Akun</span>
                    </a>
                </nav>
                
                <!-- Sidebar Footer -->
                <div class="sidebar-footer">
                    <!-- User Profile Info Link -->
                    <a href="<?= $base_path ?>profile.php" class="sidebar-user-link">
                        <div class="sidebar-user">
                            <div class="sidebar-user-name" title="<?= htmlspecialchars($username) ?>"><?= htmlspecialchars($username) ?></div>
                            <span class="sidebar-user-role">
                                <?php if ($user_role === 'admin'): ?>
                                    Administrator
                                <?php elseif ($division_name): ?>
                                    Staf <?= htmlspecialchars($division_name) ?>
                                    <?php if ($akses_perbaikan === 0): ?>
                                        <span class="read-only-badge" style="color: var(--danger); font-weight: 700; display: inline-flex; align-items: center; gap: 0.15rem; font-size: 0.7rem; margin-top: 0.1rem;"><span class="material-symbols-rounded" style="font-size: 0.8rem;">lock</span>Baca Saja</span>
                                    <?php endif; ?>
                                    <?php if (!empty($_SESSION['area_name'])): ?>
                                        <br><span style="font-size: 0.7rem; color: var(--accent); font-weight: 700;">Area: <?= htmlspecialchars($_SESSION['area_name']) ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </a>
                    
                    <!-- Sidebar Toggle Button -->
                    <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle Sidebar">
                        <span class="material-symbols-rounded">left_panel_close</span>
                        <span class="link-text">Sembunyikan Menu</span>
                    </button>
                    
                    <!-- Logout Button -->
                    <a href="<?= $base_path ?>logout.php" class="sidebar-logout">
                        <span class="material-symbols-rounded">logout</span>
                        <span class="link-text">Keluar</span>
                    </a>
                </div>
            </aside>
        <?php endif; ?>
        
        <!-- Main Content Area wrapper -->
        <main class="main-content">
            
            <?php if ($is_logged_in): ?>
                <div class="main-topbar">
                    <div class="topbar-left">
                        <!-- Topbar left spacer -->
                    </div>
                    <div class="topbar-right">
                        <!-- Notification Bell -->
                        <div class="notification-wrapper">
                            <button class="notification-trigger" id="notificationTrigger" aria-label="Notifikasi">
                                <span class="material-symbols-rounded">notifications</span>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?= $unread_count ?></span>
                                <?php endif; ?>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="dropdown-header">
                                    <h3>Notifikasi</h3>
                                    <?php if ($unread_count > 0): ?>
                                        <button id="markAllRead" class="mark-all-read-btn">Tandai semua dibaca</button>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown-body" id="notificationContainer">
                                    <?php
                                    // Fetch recent 5 notifications
                                    if ($user_role === 'admin') {
                                        $notif_stmt = $pdo->prepare("
                                            SELECT n.*, d.name AS division_name, f.area AS area_name 
                                            FROM notifications n
                                            LEFT JOIN divisions d ON n.division_id = d.id
                                            LEFT JOIN findings f ON n.finding_id = f.id
                                            WHERE n.recipient_role = 'admin'
                                            ORDER BY n.created_at DESC
                                            LIMIT 5
                                        ");
                                        $notif_stmt->execute();
                                    } else {
                                        if (!empty($_SESSION['area_name'])) {
                                            $notif_stmt = $pdo->prepare("
                                                SELECT n.*, d.name AS division_name, f.area AS area_name 
                                                FROM notifications n
                                                LEFT JOIN divisions d ON n.division_id = d.id
                                                LEFT JOIN findings f ON n.finding_id = f.id
                                                WHERE n.recipient_role = 'division' AND n.division_id = :div_id AND f.area = :area_name
                                                ORDER BY n.created_at DESC
                                                LIMIT 5
                                            ");
                                            $notif_stmt->execute([
                                                'div_id' => $_SESSION['division_id'] ?? 0,
                                                'area_name' => $_SESSION['area_name']
                                            ]);
                                        } else {
                                            $notif_stmt = $pdo->prepare("
                                                SELECT n.*, d.name AS division_name, f.area AS area_name 
                                                FROM notifications n
                                                LEFT JOIN divisions d ON n.division_id = d.id
                                                LEFT JOIN findings f ON n.finding_id = f.id
                                                WHERE n.recipient_role = 'division' AND n.division_id = :div_id
                                                ORDER BY n.created_at DESC
                                                LIMIT 5
                                            ");
                                            $notif_stmt->execute(['div_id' => $_SESSION['division_id'] ?? 0]);
                                        }
                                    }
                                    $notifications = $notif_stmt->fetchAll();
                                    
                                    if (count($notifications) > 0):
                                        foreach ($notifications as $notif):
                                    ?>
                                            <div class="notif-item <?= $notif['is_read'] ? 'read' : 'unread' ?>" data-id="<?= $notif['id'] ?>">
                                                <div class="notif-icon">
                                                    <span class="material-symbols-rounded">
                                                        <?= $notif['title'] === 'Temuan Audit Baru' ? 'assignment' : 'build' ?>
                                                    </span>
                                                </div>
                                                <div class="notif-content">
                                                    <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                                                    <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                                    <span class="notif-time"><?= date('d M Y, H:i', strtotime($notif['created_at'])) ?></span>
                                                </div>
                                            </div>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <div class="notif-empty">Tidak ada notifikasi baru</div>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown-footer">
                                    <a href="<?= $base_path ?>notifications.php" class="view-all-link">Lihat Semua Notifikasi</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
