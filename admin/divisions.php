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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $akses_perbaikan = intval($_POST['akses_perbaikan'] ?? 1);
            if (!empty($name)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO divisions (name, akses_perbaikan) VALUES (:name, :akses_perbaikan)");
                    $stmt->execute(['name' => $name, 'akses_perbaikan' => $akses_perbaikan]);
                    $success = 'Divisi berhasil ditambahkan.';
                } catch (PDOException $e) {
                    $error = 'Gagal menambahkan divisi. Nama divisi mungkin sudah ada.';
                }
            } else {
                $error = 'Nama divisi tidak boleh kosong.';
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $akses_perbaikan = intval($_POST['akses_perbaikan'] ?? 1);
            if ($id > 0 && !empty($name)) {
                try {
                    $stmt = $pdo->prepare("UPDATE divisions SET name = :name, akses_perbaikan = :akses_perbaikan WHERE id = :id");
                    $stmt->execute(['name' => $name, 'akses_perbaikan' => $akses_perbaikan, 'id' => $id]);
                    $success = 'Nama divisi berhasil diperbarui.';
                } catch (PDOException $e) {
                    $error = 'Gagal memperbarui divisi. Nama divisi mungkin sudah ada.';
                }
            } else {
                $error = 'ID atau nama divisi tidak valid.';
            }
        }
    }
}

// Handle Delete (GET)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM divisions WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['success_message'] = 'Divisi berhasil dihapus.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Gagal menghapus divisi. Kemungkinan masih ada user atau temuan yang tertaut pada divisi ini.';
        }
    }
    header("Location: divisions.php");
    exit;
}

// Fetch edit target
$edit_div = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM divisions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $edit_id]);
        $edit_div = $stmt->fetch();
    }
}

// Fetch all divisions
$divisions = $pdo->query("SELECT * FROM divisions ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Kelola Divisi</h1>
        <p class="page-subtitle">Atur daftar divisi/bagian yang melakukan monitoring audit 5R</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="layout-grid">
    <!-- List of Divisions -->
    <div class="card-section">
        <div class="card-section-header">
            <h2 class="card-section-title">Daftar Divisi (Total: <?= count($divisions) ?>)</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Nama Divisi</th>
                        <th>Akses Perbaikan</th>
                        <th style="width: 150px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($divisions)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-secondary);">Belum ada divisi. Silakan tambah divisi baru.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($divisions as $div): ?>
                            <tr>
                                <td><?= $div['id'] ?></td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($div['name']) ?></td>
                                <td>
                                    <span class="badge <?= $div['akses_perbaikan'] ? 'badge-done' : 'badge-danger' ?>">
                                        <?= $div['akses_perbaikan'] ? 'Bisa Edit/Perbaikan' : 'Lihat Saja (Read-Only)' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-links" style="justify-content: center;">
                                        <a href="?edit=<?= $div['id'] ?>" class="action-link edit">Edit</a>
                                        <a href="?delete=<?= $div['id'] ?>" class="action-link delete">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Add / Edit -->
    <div class="card-section">
        <div class="card-section-header">
            <h2 class="card-section-title"><?= $edit_div ? 'Edit Divisi' : 'Tambah Divisi Baru' ?></h2>
        </div>
        <div style="padding: 1.5rem;">
            <form action="divisions.php" method="POST">
                <input type="hidden" name="action" value="<?= $edit_div ? 'edit' : 'add' ?>">
                <?php if ($edit_div): ?>
                    <input type="hidden" name="id" value="<?= $edit_div['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name" class="form-label">Nama Divisi</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: HRGA, IT, QC" value="<?= $edit_div ? htmlspecialchars($edit_div['name']) : '' ?>" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="akses_perbaikan" class="form-label">Akses Tindakan Perbaikan</label>
                    <select name="akses_perbaikan" id="akses_perbaikan" class="form-control" required>
                        <option value="1" <?= (!$edit_div || $edit_div['akses_perbaikan'] == 1) ? 'selected' : '' ?>>Boleh (Bisa Edit/Perbaikan)</option>
                        <option value="0" <?= ($edit_div && $edit_div['akses_perbaikan'] == 0) ? 'selected' : '' ?>>Tidak Boleh (Lihat Saja / Read-Only)</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><?= $edit_div ? 'Simpan Perubahan' : 'Tambah Divisi' ?></button>
                    <?php if ($edit_div): ?>
                        <a href="divisions.php" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center;">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
