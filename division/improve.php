<?php
$base_path = '../';
require_once __DIR__ . '/../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verify login and role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'division') {
    header("Location: ../dashboard.php");
    exit;
}

$division_id = $_SESSION['division_id'];
$finding_id = intval($_GET['id'] ?? 0);

// Real-time division edit permission check
$akses_perbaikan = 1;
if ($division_id > 0) {
    $stmt_perm = $pdo->prepare("SELECT akses_perbaikan FROM divisions WHERE id = :id");
    $stmt_perm->execute(['id' => $division_id]);
    $akses_perbaikan = intval($stmt_perm->fetchColumn() ?? 1);
}

if ($akses_perbaikan === 0) {
    header("Location: dashboard.php");
    exit;
}

if ($finding_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Fetch finding details to verify it belongs to this division (and area if restricted)
$area_id = $_SESSION['area_id'] ?? null;
$area_name = $_SESSION['area_name'] ?? null;

$query_sql = "
    SELECT f.*, f.area AS area_name 
    FROM findings f
    WHERE f.id = :id AND f.division_id = :div_id
";
$params = ['id' => $finding_id, 'div_id' => $division_id];

if ($area_id) {
    $query_sql .= " AND f.area = :area_name";
    $params['area_name'] = $area_name;
}

$query_sql .= " LIMIT 1";

$stmt = $pdo->prepare($query_sql);
$stmt->execute($params);
$finding = $stmt->fetch();

if (!$finding) {
    // Finding not found or doesn't belong to division/area
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? 'On Progress');
    $improvement_description = trim($_POST['improvement_description'] ?? '');
    
    // Check if status is Done, then improvement description and photo are required
    if ($status === 'Done' && empty($improvement_description)) {
        $error = 'Harap isi deskripsi tindakan perbaikan jika status sudah selesai (Done).';
    } else {
        try {
            $photo_uploaded = false;
            $new_photo_filename = $finding['improvement_photo'];

            // Handle file upload
            if (isset($_FILES['improvement_photo']) && $_FILES['improvement_photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['improvement_photo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($ext, $allowed_exts)) {
                    $new_photo_filename = 'after_' . time() . '_' . uniqid() . '.' . $ext;
                    $target_path = __DIR__ . '/../uploads/' . $new_photo_filename;

                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Delete old improvement photo if it exists
                        if ($finding['improvement_photo'] && file_exists(__DIR__ . '/../uploads/' . $finding['improvement_photo'])) {
                            unlink(__DIR__ . '/../uploads/' . $finding['improvement_photo']);
                        }
                        $photo_uploaded = true;
                    } else {
                        throw new Exception('Gagal menyimpan file foto perbaikan ke server.');
                    }
                } else {
                    throw new Exception('Ekstensi file foto tidak valid. Hanya JPG, JPEG, PNG, dan WEBP yang diizinkan.');
                }
            }

            // Verify if status is Done, we must have an improvement photo
            if ($status === 'Done' && empty($new_photo_filename)) {
                $error = 'Foto hasil perbaikan wajib diunggah untuk mengubah status menjadi selesai (Done).';
            } else {
                // Update database
                $stmt = $pdo->prepare("
                    UPDATE findings 
                    SET status = :status, 
                        improvement_description = :improvement_description, 
                        improvement_photo = :improvement_photo 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'status' => $status,
                    'improvement_description' => $improvement_description,
                    'improvement_photo' => $new_photo_filename,
                    'id' => $finding_id
                ]);

                // Send notification to Admin
                $div_name = $_SESSION['division_name'] ?? 'Staf Divisi';
                $area_name = $finding['area_name'] ?? 'terkait';
                create_notification(
                    $pdo,
                    $finding_id,
                    $division_id,
                    "Laporan Perbaikan",
                    "Divisi " . $div_name . " melaporkan perbaikan di area " . $area_name . " dengan status \"" . $status . "\".",
                    "admin"
                );

                // Redirect on success
                $_SESSION['action_success'] = 'Laporan perbaikan berhasil disimpan.';
                header("Location: dashboard.php");
                exit;
            }

        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Laporkan Perbaikan 5R</h1>
        <p class="page-subtitle">Unggah bukti perbaikan dan laporkan status tindakan yang telah dilakukan</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="layout-grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Left Column: Finding Reference Details -->
    <div class="card-section">
        <div class="card-section-header">
            <h3 class="card-section-title">Detail Temuan Audit</h3>
        </div>
        <div style="padding: 1.5rem;">
            <div style="margin-bottom: 1.25rem;">
                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary);">Area Audit</span>
                <p style="font-size: 1.15rem; font-weight: 700; margin-top: 0.25rem;"><?= htmlspecialchars($finding['area_name']) ?></p>
            </div>
            
            <div style="margin-bottom: 1.25rem;">
                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary);">Catatan Temuan</span>
                <p style="font-size: 0.95rem; line-height: 1.5; margin-top: 0.25rem; background-color: rgba(15, 23, 42, 0.4); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);"><?= htmlspecialchars($finding['description']) ?></p>
            </div>

            <div class="meta-grid">
                <div>
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary);">PIC</span>
                    <p style="font-size: 0.9rem; font-weight: 600; margin-top: 0.15rem;"><?= htmlspecialchars($finding['pic'] ?: '-') ?></p>
                </div>
                <div>
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary);">Target Selesai</span>
                    <p style="font-size: 0.9rem; font-weight: 600; margin-top: 0.15rem; color: var(--warning);"><?= $finding['due_date'] ? date('d M Y', strtotime($finding['due_date'])) : '-' ?></p>
                </div>
            </div>

            <div>
                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Foto Bukti Temuan (Sebelum)</span>
                <img src="<?= $base_path ?>uploads/<?= htmlspecialchars($finding['finding_photo']) ?>" class="comparison-img" alt="Before Picture" style="width: 100%; max-height: 300px; object-fit: contain; background-color: rgba(15, 23, 42, 0.3); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
            </div>
        </div>
    </div>

    <!-- Right Column: Submission Form -->
    <div class="card-section">
        <div class="card-section-header">
            <h3 class="card-section-title">Form Laporan Perbaikan</h3>
        </div>
        <div style="padding: 1.5rem;">
            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="status" class="form-label">Status Tindakan *</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="On Progress" <?= $finding['status'] === 'On Progress' ? 'selected' : '' ?>>On Progress (Sedang Dikerjakan)</option>
                        <option value="Done" <?= $finding['status'] === 'Done' ? 'selected' : '' ?>>Done (Selesai)</option>
                    </select>
                    <small style="display: block; color: var(--text-secondary); margin-top: 0.25rem; font-size: 0.75rem;">
                        Status "Done" mewajibkan Anda untuk mengunggah foto hasil perbaikan.
                    </small>
                </div>

                <div class="form-group">
                    <label for="improvement_description" class="form-label">Deskripsi Tindakan Perbaikan</label>
                    <textarea name="improvement_description" id="improvement_description" class="form-control" rows="4" placeholder="Tuliskan tindakan konkret yang telah dilakukan untuk menyelesaikan temuan ini..." required><?= htmlspecialchars($finding['improvement_description'] ?? '') ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="improvement_photo" class="form-label">
                        Foto Hasil Perbaikan (Sesudah)
                        <?= $finding['improvement_photo'] ? '<span style="font-weight: 300; font-size: 0.8rem; color: var(--text-secondary);">(Kosongkan jika tidak diganti)</span>' : '*' ?>
                    </label>
                    <input type="file" name="improvement_photo" id="improvement_photo" class="form-control" accept="image/*" <?= $finding['improvement_photo'] ? '' : '' ?>>
                    
                    <?php if ($finding['improvement_photo']): ?>
                        <div style="margin-top: 0.75rem;">
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">Foto perbaikan saat ini:</span><br>
                            <img src="<?= $base_path ?>uploads/<?= htmlspecialchars($finding['improvement_photo']) ?>" class="img-thumbnail" style="width: 100px; height: 100px; margin-top: 0.25rem;" alt="Current Improvement">
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan Laporan</button>
                    <a href="dashboard.php" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center;">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
