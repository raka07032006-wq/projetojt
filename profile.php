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

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch fresh user data
$stmt = $pdo->prepare("
    SELECT u.*, d.name AS division_name, a.name AS area_name 
    FROM users u
    LEFT JOIN divisions d ON u.division_id = d.id
    LEFT JOIN areas a ON u.area_id = a.id
    WHERE u.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User no longer exists in DB
    header("Location: logout.php");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] !== 'admin') {
        $error = 'Akses ditolak: Hanya Administrator yang diizinkan mengubah password.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Harap isi semua kolom password.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password baru minimal harus 6 karakter.';
        } else {
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $update_stmt->execute([
                        'password' => $hashed_password,
                        'id' => $user_id
                    ]);
                    $success = 'Password berhasil diubah.';
                } catch (PDOException $e) {
                    $error = 'Gagal mengubah password: ' . $e->getMessage();
                }
            } else {
                $error = 'Password sekarang yang Anda masukkan salah.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Info Akun</h1>
        <p class="page-subtitle">Kelola informasi profil dan keamanan akun Anda</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($user['role'] === 'admin'): ?>
    <div class="layout-grid">
        <!-- Left Column: Change Password Form -->
        <div class="card-section">
            <div class="card-section-header">
                <h2 class="card-section-title">Ubah Password Keamanan</h2>
            </div>
            <div style="padding: 1.5rem;">
                <form action="profile.php" method="POST">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Password Sekarang</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Masukkan password saat ini" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Masukkan password baru (minimal 6 karakter)" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Ubah Password</button>
                </form>
            </div>
        </div>

        <!-- Right Column: Profile Detail Cards -->
        <div class="card-section">
            <div class="card-section-header">
                <h2 class="card-section-title">Detail Profil</h2>
            </div>
            <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.25rem;">Username</span>
                    <span style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($user['username']) ?></span>
                </div>
                
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.25rem;">Hak Akses (Role)</span>
                    <span class="badge badge-done">Administrator</span>
                </div>
                
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.25rem;">Terdaftar Sejak</span>
                    <span style="font-size: 0.95rem; color: var(--text-primary);"><?= date('d F Y, H:i', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Custom styling for non-admin profile view -->
    <style>
        .profile-wrapper {
            max-width: 850px;
            margin: 0 auto 3rem auto;
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }
        .profile-header-card {
            margin-bottom: 0;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.8) 100%);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
        .profile-header-banner {
            height: 6px;
            background: linear-gradient(90deg, var(--accent) 0%, #3b82f6 100%);
        }
        .profile-header-content {
            padding: 2.5rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .profile-avatar-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent) 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            font-weight: 800;
            color: #ffffff;
            box-shadow: var(--shadow-accent);
            border: 3px solid rgba(255, 255, 255, 0.1);
        }
        .profile-header-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            letter-spacing: -0.025em;
        }
        .profile-role-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
            border-radius: 50px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 380px), 1fr));
            gap: 1.5rem;
        }
        .profile-info-box {
            margin-bottom: 0;
            background-color: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            box-shadow: var(--shadow-sm);
        }
        .profile-info-box:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .profile-info-content {
            padding: 1.75rem;
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
        }
        .profile-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .profile-box-title {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
            display: block;
            margin-bottom: 0.35rem;
        }
        .profile-box-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            display: block;
            line-height: 1.3;
        }
        .profile-box-desc {
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: block;
            margin-top: 0.35rem;
        }
    </style>

    <div class="profile-wrapper">
        <!-- Header Card -->
        <div class="profile-header-card">
            <div class="profile-header-banner"></div>
            <div class="profile-header-content">
                <div class="profile-avatar-circle">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
                        <h2 class="profile-header-title">
                            <?= htmlspecialchars($user['username']) ?>
                        </h2>
                        <span class="profile-role-badge">
                            Staf Divisi
                        </span>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                        Akses Terbatas &bull; Panel Informasi & Monitoring Audit 5R
                    </p>
                </div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="profile-grid">
            <!-- Box 1: Divisi Terkait -->
            <div class="profile-info-box">
                <div class="profile-info-content">
                    <div class="profile-icon-wrapper" style="background-color: var(--info-bg); color: var(--info);">
                        <span class="material-symbols-rounded" style="font-size: 1.5rem;">domain</span>
                    </div>
                    <div>
                        <span class="profile-box-title">Divisi Terkait</span>
                        <span class="profile-box-value">
                            <?= htmlspecialchars($user['division_name'] ?? 'Tidak Terkait') ?>
                        </span>
                        <span class="profile-box-desc">Divisi aktif dalam pemantauan program 5R</span>
                    </div>
                </div>
            </div>

            <!-- Box 2: Area Kerja Terkait -->
            <div class="profile-info-box">
                <div class="profile-info-content">
                    <div class="profile-icon-wrapper" style="background-color: var(--success-bg); color: var(--success);">
                        <span class="material-symbols-rounded" style="font-size: 1.5rem;">explore</span>
                    </div>
                    <div>
                        <span class="profile-box-title">Area Kerja Terkait</span>
                        <span class="profile-box-value">
                            <?= htmlspecialchars($user['area_name'] ?? 'Seluruh Area Divisi') ?>
                        </span>
                        <span class="profile-box-desc">Cakupan area tanggung jawab audit</span>
                    </div>
                </div>
            </div>

            <!-- Box 3: Terdaftar Sejak -->
            <div class="profile-info-box">
                <div class="profile-info-content">
                    <div class="profile-icon-wrapper" style="background-color: var(--warning-bg); color: var(--warning);">
                        <span class="material-symbols-rounded" style="font-size: 1.5rem;">calendar_today</span>
                    </div>
                    <div>
                        <span class="profile-box-title">Terdaftar Sejak</span>
                        <span class="profile-box-value">
                            <?= date('d F Y, H:i', strtotime($user['created_at'])) ?>
                        </span>
                        <span class="profile-box-desc">Tanggal pembuatan akun pengguna</span>
                    </div>
                </div>
            </div>

            <!-- Box 4: Kebijakan Keamanan -->
            <div class="profile-info-box">
                <div class="profile-info-content">
                    <div class="profile-icon-wrapper" style="background-color: var(--danger-bg); color: var(--danger);">
                        <span class="material-symbols-rounded" style="font-size: 1.5rem;">lock</span>
                    </div>
                    <div>
                        <span class="profile-box-title">Kebijakan Akun</span>
                        <span class="profile-box-value">Dikelola Administrator</span>
                        <span class="profile-box-desc">Pengubahan username/password dikelola admin</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
