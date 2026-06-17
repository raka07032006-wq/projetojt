<?php
$base_path = '../';
require_once __DIR__ . '/../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verify admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

$success = '';
$error = '';
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $division_id = intval($_POST['division_id'] ?? 0);
            $area_id = intval($_POST['area_id'] ?? 0);
            $area_id_val = $area_id > 0 ? $area_id : null;
            
            if (!empty($username) && !empty($password) && $division_id > 0) {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $stmt->execute(['username' => $username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Username sudah digunakan. Gunakan username lain.';
                } else {
                    try {
                        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            INSERT INTO users (username, password, role, division_id, area_id) 
                            VALUES (:username, :password, 'division', :division_id, :area_id)
                        ");
                        $stmt->execute([
                            'username' => $username,
                            'password' => $hashed_pass,
                            'division_id' => $division_id,
                            'area_id' => $area_id_val
                        ]);
                        $success = 'Akun divisi berhasil dibuat.';
                    } catch (PDOException $e) {
                        $error = 'Gagal membuat akun divisi: ' . $e->getMessage();
                    }
                }
            } else {
                $error = 'Harap isi semua kolom untuk membuat akun.';
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $division_id = intval($_POST['division_id'] ?? 0);
            $area_id = intval($_POST['area_id'] ?? 0);
            $area_id_val = $area_id > 0 ? $area_id : null;
            
            if ($id > 0 && !empty($username) && $division_id > 0) {
                // Check if username is used by someone else
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id != :id");
                $stmt->execute(['username' => $username, 'id' => $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Username sudah digunakan oleh akun lain.';
                } else {
                    try {
                        if (!empty($password)) {
                            // Also update password
                            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET username = :username, password = :password, division_id = :division_id, area_id = :area_id 
                                WHERE id = :id AND role = 'division'
                            ");
                            $stmt->execute([
                                'username' => $username,
                                'password' => $hashed_pass,
                                'division_id' => $division_id,
                                'area_id' => $area_id_val,
                                'id' => $id
                            ]);
                        } else {
                            // Only update username, division, and area
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET username = :username, division_id = :division_id, area_id = :area_id 
                                WHERE id = :id AND role = 'division'
                            ");
                            $stmt->execute([
                                'username' => $username,
                                'division_id' => $division_id,
                                'area_id' => $area_id_val,
                                'id' => $id
                            ]);
                        }
                        $success = 'Akun berhasil diperbarui.';
                    } catch (PDOException $e) {
                        $error = 'Gagal memperbarui akun: ' . $e->getMessage();
                    }
                }
            } else {
                $error = 'ID, username, atau divisi tidak valid.';
            }
        }
    }
}

// Handle Delete (GET)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'division'");
            $stmt->execute(['id' => $id]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = 'Akun divisi berhasil dihapus.';
            } else {
                $_SESSION['error_message'] = 'Gagal menghapus akun. Akun mungkin tidak ditemukan atau merupakan akun administrator.';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Gagal menghapus akun: ' . $e->getMessage();
        }
    }
    header("Location: users.php");
    exit;
}

// Fetch edit target
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'division' LIMIT 1");
        $stmt->execute(['id' => $edit_id]);
        $edit_user = $stmt->fetch();
    }
}

// Fetch all users with division name and area name (except admin)
$users = $pdo->query("
    SELECT u.*, d.name AS division_name, a.name AS area_name 
    FROM users u
    LEFT JOIN divisions d ON u.division_id = d.id
    LEFT JOIN areas a ON u.area_id = a.id
    ORDER BY u.role ASC, u.id ASC
")->fetchAll();

// Fetch all divisions for select dropdown
$divisions = $pdo->query("SELECT * FROM divisions ORDER BY name ASC")->fetchAll();
$predefined_areas = $pdo->query("SELECT * FROM areas ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Kelola User Akses</h1>
        <p class="page-subtitle">Beri hak akses login bagi setiap divisi untuk perbaikan audit</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="layout-grid">
    <!-- User List -->
    <div class="card-section">
        <div class="card-section-header">
            <h2 class="card-section-title">Daftar Akun Pengguna</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Peran (Role)</th>
                        <th>Akses Divisi</th>
                        <th>Tanggal Dibuat</th>
                        <th style="width: 150px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user_row): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($user_row['username']) ?></td>
                            <td>
                                <span class="badge <?= $user_row['role'] === 'admin' ? 'badge-done' : 'badge-progress' ?>">
                                    <?= $user_row['role'] === 'admin' ? 'Administrator' : 'Staf Divisi' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user_row['role'] === 'admin'): ?>
                                    <span style="color: var(--text-secondary);">Semua Akses</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($user_row['division_name'] ?? 'Tidak Terkait') ?>
                                    <?php if (!empty($user_row['area_name'])): ?>
                                        <br><span style="font-size: 0.75rem; color: var(--accent); font-weight: 600;">Area: <?= htmlspecialchars($user_row['area_name']) ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y H:i', strtotime($user_row['created_at'])) ?></td>
                            <td>
                                <?php if ($user_row['role'] === 'division'): ?>
                                    <div class="action-links" style="justify-content: center;">
                                        <a href="?edit=<?= $user_row['id'] ?>" class="action-link edit">Edit</a>
                                        <a href="?delete=<?= $user_row['id'] ?>" class="action-link delete">Hapus</a>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.8rem;">Utama</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Edit/Add Form -->
    <div class="card-section">
        <div class="card-section-header">
            <h2 class="card-section-title"><?= $edit_user ? 'Edit Akun Divisi' : 'Buat Akun Divisi Baru' ?></h2>
        </div>
        <div style="padding: 1.5rem;">
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="<?= $edit_user ? 'edit' : 'add' ?>">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username_form" class="form-label">Username</label>
                    <input type="text" name="username" id="username_form" class="form-control" placeholder="Contoh: user_hrga" value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password_form" class="form-label">
                        Password <?= $edit_user ? '<span style="font-weight: 300; font-size: 0.8rem; color: var(--text-secondary);">(Kosongkan jika tidak diubah)</span>' : '' ?>
                    </label>
                    <input type="password" name="password" id="password_form" class="form-control" placeholder="Masukkan password" <?= $edit_user ? '' : 'required' ?>>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="division_id" class="form-label">Akses Divisi</label>
                    <select name="division_id" id="division_id" class="form-control" required>
                        <option value="" disabled <?= !$edit_user ? 'selected' : '' ?>>Pilih Divisi</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?= $div['id'] ?>" <?= ($edit_user && $edit_user['division_id'] == $div['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($div['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="area_id" class="form-label">Akses Area</label>
                    <select name="area_id" id="area_id" class="form-control">
                        <option value="0">Pilih Divisi Terlebih Dahulu</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><?= $edit_user ? 'Simpan Akun' : 'Buat Akun' ?></button>
                    <?php if ($edit_user): ?>
                        <a href="users.php" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center;">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const divisionAreas = <?= json_encode($predefined_areas) ?>;
    const editUser = <?= json_encode($edit_user) ?>;
    
    const divisionSelect = document.getElementById('division_id');
    const areaSelect = document.getElementById('area_id');

    function populateAreas(divisionId, selectedAreaId = 0) {
        areaSelect.innerHTML = '';
        
        if (!divisionId) {
            areaSelect.innerHTML = '<option value="0">Pilih Divisi Terlebih Dahulu</option>';
            return;
        }

        // Add "All Areas" option
        const allOpt = document.createElement('option');
        allOpt.value = '0';
        allOpt.textContent = 'Seluruh Area (Akses Penuh Divisi)';
        if (selectedAreaId === 0) {
            allOpt.selected = true;
        }
        areaSelect.appendChild(allOpt);

        // Filter areas for selected division
        const areas = divisionAreas.filter(a => a.division_id == divisionId);
        
        areas.forEach(a => {
            const opt = document.createElement('option');
            opt.value = a.id;
            opt.textContent = a.name;
            if (selectedAreaId && a.id == selectedAreaId) {
                opt.selected = true;
            }
            areaSelect.appendChild(opt);
        });
    }

    divisionSelect.addEventListener('change', () => {
        populateAreas(divisionSelect.value);
    });

    // Initial load
    if (editUser && editUser.division_id) {
        populateAreas(editUser.division_id, editUser.area_id || 0);
    } else if (divisionSelect.value) {
        populateAreas(divisionSelect.value, 0);
    }
});
</script>
