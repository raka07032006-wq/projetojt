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
    SELECT u.*, d.name AS division_name 
    FROM users u
    LEFT JOIN divisions d ON u.division_id = d.id
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
                <span class="badge <?= $user['role'] === 'admin' ? 'badge-done' : 'badge-progress' ?>">
                    <?= $user['role'] === 'admin' ? 'Administrator' : 'Staf Divisi' ?>
                </span>
            </div>
            
            <?php if ($user['role'] === 'division'): ?>
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.25rem;">Divisi Terkait</span>
                    <span style="font-size: 1rem; font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($user['division_name'] ?? 'Tidak Terkait') ?></span>
                </div>
            <?php endif; ?>
            
            <div>
                <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.25rem;">Terdaftar Sejak</span>
                <span style="font-size: 0.95rem; color: var(--text-primary);"><?= date('d F Y, H:i', strtotime($user['created_at'])) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
