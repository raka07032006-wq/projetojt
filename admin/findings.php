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

// Helper to create uploads directory
if (!is_dir(__DIR__ . '/../uploads')) {
    mkdir(__DIR__ . '/../uploads', 0777, true);
}

// Handle Add Finding (Multiple support)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $areas = $_POST['area'] ?? [];
    $division_id = intval($_POST['division_id'] ?? 0);
    $pic = trim($_POST['pic'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $due_date_val = !empty($due_date) ? $due_date : null;
    
    $descriptions = $_POST['description'] ?? [];

    if ($division_id > 0 && is_array($descriptions) && !empty($descriptions) && is_array($areas)) {
        $success_count = 0;
        $errors = [];
        
        foreach ($descriptions as $index => $desc) {
            $desc = trim($desc);
            $item_area = trim($areas[$index] ?? '');
            if (empty($desc) || empty($item_area)) {
                continue;
            }
            
            $file_field = "finding_photo_" . $index;
            $uploaded_files = [];
            $has_upload_errors = false;

            if (isset($_FILES[$file_field]) && is_array($_FILES[$file_field]['name'])) {
                $file_count = count($_FILES[$file_field]['name']);
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                $valid_files = [];
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES[$file_field]['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES[$file_field]['name'][$i];
                        $file_tmp = $_FILES[$file_field]['tmp_name'][$i];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed_exts)) {
                            $valid_files[] = [
                                'tmp' => $file_tmp,
                                'ext' => $ext
                            ];
                        } else {
                            $errors[] = 'Ekstensi file item ke-' . ($index+1) . ' tidak diizinkan.';
                            $has_upload_errors = true;
                            break;
                        }
                    } else if ($_FILES[$file_field]['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = 'Gagal mengunggah salah satu foto item ke-' . ($index+1) . '.';
                        $has_upload_errors = true;
                        break;
                    }
                }

                if ($has_upload_errors) {
                    continue;
                }

                if (empty($valid_files)) {
                    $errors[] = 'Minimal satu foto wajib diunggah untuk item temuan ke-' . ($index+1) . '.';
                    continue;
                }

                // Insert finding row
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO findings (area, division_id, description, pic, status, due_date) 
                        VALUES (:area, :division_id, :description, :pic, 'On Progress', :due_date)
                    ");
                    $stmt->execute([
                        'area' => $item_area,
                        'division_id' => $division_id,
                        'description' => $desc,
                        'pic' => $pic,
                        'due_date' => $due_date_val
                    ]);
                    $last_id = $pdo->lastInsertId();
                    
                    // Upload files and insert into finding_images in DB as compressed blobs
                    $stmt_img = $pdo->prepare("
                        INSERT INTO finding_images (finding_id, image_path, image_data, mime_type, type) 
                        VALUES (:finding_id, :image_path, :image_data, :mime_type, 'before')
                    ");
                    
                    foreach ($valid_files as $vf) {
                        $fake_filename = 'before_' . time() . '_' . uniqid() . '.' . $vf['ext'];
                        $mime = 'image/' . ($vf['ext'] === 'jpg' ? 'jpeg' : $vf['ext']);
                        $compressed_data = compress_uploaded_image($vf['tmp'], $mime);
                        
                        $stmt_img->execute([
                            'finding_id' => $last_id,
                            'image_path' => $fake_filename,
                            'image_data' => $compressed_data,
                            'mime_type' => $mime
                        ]);
                    }
                    
                    // Send notification to division
                    create_notification(
                        $pdo,
                        $last_id,
                        $division_id,
                        "Temuan Audit Baru",
                        "Admin menambahkan temuan baru di area " . $item_area . ": \"" . $desc . "\"",
                        "division"
                    );
                    
                    $success_count++;
                } catch (PDOException $e) {
                    $errors[] = 'Gagal menyimpan item ke-' . ($index+1) . ': ' . $e->getMessage();
                }
            } else {
                $errors[] = 'Foto wajib diunggah untuk item temuan ke-' . ($index+1) . '.';
            }
        }
        
        if ($success_count > 0) {
            $success = "$success_count temuan audit baru berhasil didaftarkan.";
            if (!empty($errors)) {
                $error = "Beberapa item gagal: " . implode(", ", $errors);
            }
        } else {
            $error = "Semua item gagal didaftarkan: " . implode(", ", $errors);
        }
    } else {
        $error = 'Harap lengkapi semua kolom wajib dan masukkan minimal satu catatan temuan.';
    }
}

// Handle Edit Finding (Single support)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $area = trim($_POST['area'] ?? '');
    $division_id = intval($_POST['division_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $pic = trim($_POST['pic'] ?? '');
    $status = trim($_POST['status'] ?? 'On Progress');
    $due_date = trim($_POST['due_date'] ?? '');
    
    $due_date_val = !empty($due_date) ? $due_date : null;

    if ($id > 0 && !empty($area) && $division_id > 0 && !empty($description)) {
        try {
            $pdo->beginTransaction();
            
            // 1. Update finding details
            $stmt = $pdo->prepare("
                UPDATE findings 
                SET area = :area, division_id = :division_id, description = :description, 
                    pic = :pic, status = :status, due_date = :due_date
                WHERE id = :id
            ");
            $stmt->execute([
                'area' => $area,
                'division_id' => $division_id,
                'description' => $description,
                'pic' => $pic,
                'status' => $status,
                'due_date' => $due_date_val,
                'id' => $id
            ]);

            // 2. Handle photo deletions
            if (isset($_POST['delete_photo']) && is_array($_POST['delete_photo'])) {
                foreach ($_POST['delete_photo'] as $photo_id) {
                    $photo_id = intval($photo_id);
                    $img = $pdo->query("SELECT image_path FROM finding_images WHERE id = $photo_id AND finding_id = $id")->fetch();
                    if ($img) {
                        $target_path = __DIR__ . '/../uploads/' . $img['image_path'];
                        if (file_exists($target_path)) {
                            @unlink($target_path);
                        }
                        $pdo->exec("DELETE FROM finding_images WHERE id = $photo_id");
                    }
                }
            }

            // 3. Handle additional photo uploads (Before)
            if (isset($_FILES['finding_photo']['name']) && is_array($_FILES['finding_photo']['name'])) {
                $file_count = count($_FILES['finding_photo']['name']);
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                $stmt_img = $pdo->prepare("
                    INSERT INTO finding_images (finding_id, image_path, image_data, mime_type, type) 
                    VALUES (:finding_id, :image_path, :image_data, :mime_type, 'before')
                ");

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['finding_photo']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['finding_photo']['name'][$i];
                        $file_tmp = $_FILES['finding_photo']['tmp_name'][$i];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        if (in_array($ext, $allowed_exts)) {
                            $fake_filename = 'before_' . time() . '_' . uniqid() . '.' . $ext;
                            $mime = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
                            $compressed_data = compress_uploaded_image($file_tmp, $mime);

                            $stmt_img->execute([
                                'finding_id' => $id,
                                'image_path' => $fake_filename,
                                'image_data' => $compressed_data,
                                'mime_type' => $mime
                            ]);
                        }
                    }
                }
            }

            $pdo->commit();
            $success = 'Temuan audit berhasil diperbarui.';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal memperbarui temuan: ' . $e->getMessage();
        }
    } else {
        $error = 'Harap isi seluruh kolom wajib.';
    }
}

// Handle Delete Finding
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        try {
            // Delete all image files from physical disk (if exist)
            $images = $pdo->query("SELECT image_path FROM finding_images WHERE finding_id = $id")->fetchAll();
            foreach ($images as $img) {
                $file_path = __DIR__ . '/../uploads/' . $img['image_path'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
            
            $stmt = $pdo->prepare("DELETE FROM findings WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['success_message'] = 'Temuan audit dan berkas fotonya berhasil dihapus.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Gagal menghapus temuan audit: ' . $e->getMessage();
        }
    }
    
    // Redirect back preserving filters
    $redirect_url = 'findings.php';
    $query_params = [];
    if (!empty($_GET['filter_area'])) {
        $query_params['filter_area'] = $_GET['filter_area'];
    }
    if (isset($_GET['filter_division']) && intval($_GET['filter_division']) > 0) {
        $query_params['filter_division'] = intval($_GET['filter_division']);
    }
    if (!empty($_GET['filter_status'])) {
        $query_params['filter_status'] = $_GET['filter_status'];
    }
    if (!empty($query_params)) {
        $redirect_url .= '?' . http_build_query($query_params);
    }
    header("Location: " . $redirect_url);
    exit;
}

// Filters
$filter_division = intval($_GET['filter_division'] ?? 0);
$filter_area = trim($_GET['filter_area'] ?? '');
$filter_status = trim($_GET['filter_status'] ?? '');

$where_clauses = [];
$params = [];

if ($filter_division > 0) {
    $where_clauses[] = "f.division_id = :filter_division";
    $params['filter_division'] = $filter_division;
}
if (!empty($filter_area)) {
    $where_clauses[] = "f.area = :filter_area";
    $params['filter_area'] = $filter_area;
}
if (!empty($filter_status)) {
    $where_clauses[] = "f.status = :filter_status";
    $params['filter_status'] = $filter_status;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch findings
$query_findings = "
    SELECT f.*, f.area AS area_name, d.name AS division_name 
    FROM findings f
    JOIN divisions d ON f.division_id = d.id
    $where_sql
    ORDER BY f.id DESC
";
$stmt = $pdo->prepare($query_findings);
$stmt->execute($params);
$findings_list = $stmt->fetchAll();

// Batch fetch images to avoid N+1 queries
$finding_ids = array_column($findings_list, 'id');
$finding_images = [];
if (!empty($finding_ids)) {
    $in_clause = implode(',', array_fill(0, count($finding_ids), '?'));
    $stmt_imgs = $pdo->prepare("SELECT id, finding_id, type, image_path FROM finding_images WHERE finding_id IN ($in_clause) ORDER BY id ASC");
    $stmt_imgs->execute($finding_ids);
    foreach ($stmt_imgs->fetchAll() as $img) {
        $finding_images[$img['finding_id']][$img['type']][] = [
            'id' => $img['id'],
            'path' => $img['image_path']
        ];
    }
}

// Distinct values for filters
$areas = $pdo->query("SELECT DISTINCT area AS name FROM findings ORDER BY area ASC")->fetchAll();
$divisions = $pdo->query("SELECT * FROM divisions ORDER BY name ASC")->fetchAll();
$predefined_areas = $pdo->query("SELECT * FROM areas ORDER BY name ASC")->fetchAll();

// Fetch edit target
$edit_finding = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM findings WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $edit_id]);
        $edit_finding = $stmt->fetch();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Temuan Audit 5R</h1>
        <p class="page-subtitle">Daftarkan temuan baru, tentukan divisinya, dan pantau kemajuan perbaikannya</p>
    </div>
    <div>
        <button type="button" id="btn-trigger-add" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
            <span class="material-symbols-rounded">add</span>
            Tambah Temuan
        </button>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
        
        <!-- Filter Card -->
        <div class="card-section" style="margin-bottom: 0;">
            <div class="card-section-header">
                <h3 class="card-section-title">Pencarian & Filter</h3>
            </div>
            <div style="padding: 1.25rem;">
                <form action="findings.php" method="GET" class="filter-form">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_area_sel" class="form-label" style="margin-bottom: 0.25rem;">Area</label>
                        <select name="filter_area" id="filter_area_sel" class="form-control">
                            <option value="">Semua Area</option>
                            <?php foreach ($areas as $a): ?>
                                <option value="<?= htmlspecialchars($a['name']) ?>" <?= $filter_area === $a['name'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_div_sel" class="form-label" style="margin-bottom: 0.25rem;">Divisi</label>
                        <select name="filter_division" id="filter_div_sel" class="form-control">
                            <option value="0">Semua Divisi</option>
                            <?php foreach ($divisions as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $filter_division == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_stat_sel" class="form-label" style="margin-bottom: 0.25rem;">Status</label>
                        <select name="filter_status" id="filter_stat_sel" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="On Progress" <?= $filter_status === 'On Progress' ? 'selected' : '' ?>>On Progress</option>
                            <option value="Done" <?= $filter_status === 'Done' ? 'selected' : '' ?>>Done</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary" style="width: 100%;">Filter</button>
                </form>
            </div>
        </div>

        <!-- Findings Table -->
        <div class="card-section">
            <div class="card-section-header">
                <h3 class="card-section-title">Temuan Audit (Total: <?= count($findings_list) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table-wide">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Area & Divisi</th>
                            <th>Deskripsi Temuan</th>
                            <th style="text-align: center;">Foto Sebelum</th>
                            <th style="text-align: center;">Foto Sesudah</th>
                            <th>Tindakan / PIC</th>
                            <th>Status</th>
                            <th style="width: 120px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($findings_list)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 2rem;">Tidak ada temuan audit yang cocok dengan filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($findings_list as $f): 
                                $badge = 'badge-pending';
                                if ($f['status'] === 'On Progress') $badge = 'badge-progress';
                                if ($f['status'] === 'Done') $badge = 'badge-done';
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div style="font-weight: 700;"><?= htmlspecialchars($f['area_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--accent); font-weight: 600; margin-top: 0.25rem;">Divisi: <?= htmlspecialchars($f['division_name']) ?></div>
                                    </td>
                                    <td>
                                        <div style="max-width: 250px; line-height: 1.4; font-size: 0.85rem;"><?= htmlspecialchars($f['description']) ?></div>
                                        <?php if ($f['due_date']): ?>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">Batas: <?= date('d M Y', strtotime($f['due_date'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 0.25rem; justify-content: center; flex-wrap: wrap; max-width: 130px; margin: 0 auto;">
                                             <?php 
                                             $bef_imgs = $finding_images[$f['id']]['before'] ?? [];
                                             if (!empty($bef_imgs)): 
                                                 foreach ($bef_imgs as $img_info):
                                             ?>
                                                <a href="<?= $base_path ?>view_image.php?id=<?= $img_info['id'] ?>" target="_blank">
                                                    <img src="<?= $base_path ?>view_image.php?id=<?= $img_info['id'] ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover; margin: 2px;" alt="Before">
                                                </a>
                                             <?php 
                                                 endforeach;
                                             else:
                                             ?>
                                                 -
                                             <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 0.25rem; justify-content: center; flex-wrap: wrap; max-width: 130px; margin: 0 auto;">
                                             <?php 
                                             $aft_imgs = $finding_images[$f['id']]['after'] ?? [];
                                             if (!empty($aft_imgs)): 
                                                 foreach ($aft_imgs as $img_info):
                                             ?>
                                                <a href="<?= $base_path ?>view_image.php?id=<?= $img_info['id'] ?>" target="_blank">
                                                    <img src="<?= $base_path ?>view_image.php?id=<?= $img_info['id'] ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover; margin: 2px;" alt="After">
                                                </a>
                                             <?php 
                                                 endforeach;
                                             else:
                                             ?>
                                                 <span style="color: var(--text-secondary); font-size: 0.8rem; font-style: italic;">Belum Ada</span>
                                             <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($f['status'] === 'Done'): ?>
                                            <div style="font-size: 0.85rem; font-weight: 600; color: var(--success);">Perbaikan Selesai</div>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($f['improvement_description']) ?></div>
                                        <?php else: ?>
                                            <div style="font-size: 0.85rem; color: var(--text-secondary);">PIC: <?= htmlspecialchars($f['pic'] ?: '-') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($f['status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-links" style="justify-content: center;">
                                            <a href="?edit=<?= $f['id'] ?><?= ($filter_area ? '&filter_area='.urlencode($filter_area) : '') ?><?= ($filter_division ? '&filter_division='.$filter_division : '') ?><?= ($filter_status ? '&filter_status='.$filter_status : '') ?>" class="action-link edit" title="Edit"><span class="material-symbols-rounded">edit</span></a>
                                            <a href="?delete=<?= $f['id'] ?><?= ($filter_area ? '&filter_area='.urlencode($filter_area) : '') ?><?= ($filter_division ? '&filter_division='.$filter_division : '') ?><?= ($filter_status ? '&filter_status='.$filter_status : '') ?>" class="action-link delete" title="Hapus"><span class="material-symbols-rounded">delete</span></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Finding -->
<div id="form-modal" class="modal">
    <div class="modal-content modal-content-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title-text"><?= $edit_finding ? 'Edit Temuan Audit' : 'Tambah Temuan Audit Baru' ?></h3>
            <span class="modal-close" id="modal-close-btn">&times;</span>
        </div>
        <div class="modal-body" style="max-height: 75vh; overflow-y: auto; padding-right: 0.5rem;">
            <form action="findings.php" method="POST" enctype="multipart/form-data" id="modal-form">
                <input type="hidden" name="action" value="<?= $edit_finding ? 'edit' : 'add' ?>">
                <?php if ($edit_finding): ?>
                    <input type="hidden" name="id" value="<?= $edit_finding['id'] ?>">
                <?php endif; ?>

                <div class="form-row-2">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="division_id" class="form-label">Tanggung Jawab Divisi *</label>
                        <select name="division_id" id="division_id" class="form-control" required>
                            <option value="" disabled <?= !$edit_finding ? 'selected' : '' ?>>Pilih Divisi</option>
                            <?php foreach ($divisions as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($edit_finding && $edit_finding['division_id'] == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="pic" class="form-label">PIC Penanggung Jawab</label>
                        <input type="text" name="pic" id="pic" class="form-control" placeholder="Nama PIC" value="<?= $edit_finding ? htmlspecialchars($edit_finding['pic']) : '' ?>">
                    </div>
                </div>

                <div class="form-row-2" style="margin-top: 1.25rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="due_date" class="form-label">Batas Waktu (Deadline)</label>
                        <input type="date" name="due_date" id="due_date" class="form-control" value="<?= $edit_finding ? htmlspecialchars($edit_finding['due_date']) : '' ?>">
                    </div>

                    <?php if ($edit_finding): ?>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="status" class="form-label">Status Temuan</label>
                            <select name="status" id="status" class="form-control">
                                <option value="Pending" <?= $edit_finding['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="On Progress" <?= $edit_finding['status'] === 'On Progress' ? 'selected' : '' ?>>On Progress</option>
                                <option value="Done" <?= $edit_finding['status'] === 'Done' ? 'selected' : '' ?>>Done</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($edit_finding): ?>
                    <div class="form-row-2" style="margin-top: 1.25rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="area_select" class="form-label">Area Audit *</label>
                            <select id="area_select" class="form-control" required>
                                <option value="" disabled selected>Pilih Divisi Terlebih Dahulu</option>
                            </select>
                        </div>

                        <div class="form-group" id="manual_area_group" style="display: none; margin-bottom: 0;">
                            <label for="area" class="form-label">Nama Area Kustom (Ketik Manual) *</label>
                            <input type="text" name="area" id="area" class="form-control" placeholder="Contoh: Kantor lantai 1, Kantin bawah" value="<?= htmlspecialchars($edit_finding['area']) ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.25rem;">
                        <label for="description" class="form-label">Deskripsi Catatan Temuan *</label>
                        <textarea name="description" id="description" class="form-control" rows="4" placeholder="Tuliskan catatan detail temuan..." required><?= htmlspecialchars($edit_finding['description']) ?></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label">Unggah Foto Temuan Baru (Sebelum Perbaikan)</label>
                        <div class="custom-file-row">
                            <div class="custom-file-col">
                                <label class="custom-file-upload">
                                    <span class="material-symbols-rounded upload-icon">photo_library</span>
                                    <span class="custom-file-label">Galeri / File</span>
                                    <span class="custom-file-subtext">Belum ada file</span>
                                    <input type="file" name="finding_photo[]" id="finding_photo" class="hidden-file-input file-input-group" data-type="gallery" accept="image/*" multiple>
                                </label>
                            </div>
                            <div class="custom-file-col">
                                <label class="custom-file-upload">
                                    <span class="material-symbols-rounded upload-icon">photo_camera</span>
                                    <span class="custom-file-label">Kamera HP</span>
                                    <span class="custom-file-subtext">Belum memotret</span>
                                    <input type="file" name="finding_photo[]" id="finding_photo_camera" class="hidden-file-input file-input-group" data-type="camera" accept="image/*" capture="environment">
                                </label>
                            </div>
                        </div>
                        <small style="display: block; color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.75rem;">
                            Bisa mengunggah beberapa foto sekaligus dari galeri atau memotret langsung dengan kamera HP.
                        </small>
                    </div>

                    <?php
                    // Fetch existing before photos for this finding
                    $stmt_cur_imgs = $pdo->prepare("SELECT * FROM finding_images WHERE finding_id = :finding_id AND type = 'before'");
                    $stmt_cur_imgs->execute(['finding_id' => $edit_finding['id']]);
                    $cur_imgs = $stmt_cur_imgs->fetchAll();
                    ?>
                    <?php if (!empty($cur_imgs)): ?>
                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label class="form-label" style="font-weight: 700; color: var(--text-primary);">Foto Sebelum Perbaikan Saat Ini (Centang untuk menghapus):</label>
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                <?php foreach ($cur_imgs as $c_img): ?>
                                    <div style="position: relative; width: 100px; text-align: center; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.25rem; background-color: rgba(255,255,255,0.02);">
                                        <img src="<?= $base_path ?>view_image.php?id=<?= $c_img['id'] ?>" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover; display: block; margin: 0 auto 0.25rem;" alt="Finding Photo">
                                        <label style="font-size: 0.75rem; color: var(--danger); font-weight: 700; display: inline-flex; align-items: center; gap: 0.15rem; cursor: pointer;">
                                            <input type="checkbox" name="delete_photo[]" value="<?= $c_img['id'] ?>"> Hapus
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Fetch existing after photos for this finding
                    $stmt_cur_after = $pdo->prepare("SELECT * FROM finding_images WHERE finding_id = :finding_id AND type = 'after'");
                    $stmt_cur_after->execute(['finding_id' => $edit_finding['id']]);
                    $cur_after = $stmt_cur_after->fetchAll();
                    ?>
                    <?php if (!empty($cur_after)): ?>
                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label class="form-label" style="font-weight: 700; color: var(--text-primary);">Foto Setelah Perbaikan Saat Ini (Centang untuk menghapus):</label>
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                <?php foreach ($cur_after as $c_img): ?>
                                    <div style="position: relative; width: 100px; text-align: center; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.25rem; background-color: rgba(255,255,255,0.02);">
                                        <img src="<?= $base_path ?>view_image.php?id=<?= $c_img['id'] ?>" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover; display: block; margin: 0 auto 0.25rem;" alt="Improvement Photo">
                                        <label style="font-size: 0.75rem; color: var(--danger); font-weight: 700; display: inline-flex; align-items: center; gap: 0.15rem; cursor: pointer;">
                                            <input type="checkbox" name="delete_photo[]" value="<?= $c_img['id'] ?>"> Hapus
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Add mode: Dynamic Multi-Item list -->
                    <div id="findings-container">
                        <div class="finding-item-card" style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.25rem; background-color: rgba(255, 255, 255, 0.01); position: relative;">
                            <h4 class="item-title-text" style="margin: 0 0 1rem 0; font-size: 0.9rem; font-weight: 700; color: var(--accent); display: flex; justify-content: space-between; align-items: center;">
                                Item Temuan #1
                            </h4>
                            
                            <div class="form-row-2">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label">Area Audit *</label>
                                    <select class="form-control area-select-item" name="area[]" required>
                                        <option value="" disabled selected>Pilih Divisi Terlebih Dahulu</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label">Foto Temuan (Sebelum Perbaikan) *</label>
                                    <div class="custom-file-row">
                                        <div class="custom-file-col">
                                            <label class="custom-file-upload">
                                                <span class="material-symbols-rounded upload-icon">photo_library</span>
                                                <span class="custom-file-label">Galeri / File</span>
                                                <span class="custom-file-subtext">Belum ada file</span>
                                                <input type="file" name="finding_photo_0[]" class="hidden-file-input file-input-group" data-type="gallery" accept="image/*" multiple required>
                                            </label>
                                        </div>
                                        <div class="custom-file-col">
                                            <label class="custom-file-upload">
                                                <span class="material-symbols-rounded upload-icon">photo_camera</span>
                                                <span class="custom-file-label">Kamera HP</span>
                                                <span class="custom-file-subtext">Belum memotret</span>
                                                <input type="file" name="finding_photo_0[]" class="hidden-file-input file-input-group" data-type="camera" accept="image/*" capture="environment">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group manual-area-group-item" style="display: none; margin-top: 1.25rem; margin-bottom: 0;">
                                <label class="form-label">Nama Area Kustom (Ketik Manual) *</label>
                                <input type="text" class="form-control manual-area-input-item" placeholder="Contoh: Kantor lantai 1, Kantin bawah">
                            </div>

                            <div class="form-group" style="margin-top: 1.25rem; margin-bottom: 0;">
                                <label class="form-label">Deskripsi Catatan Temuan *</label>
                                <textarea name="description[]" class="form-control" rows="3" placeholder="Tuliskan catatan detail temuan..." required></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" id="btn-add-item" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.5rem 1rem; width: 100%; margin-bottom: 2rem; display: flex; align-items: center; justify-content: center; gap: 0.4rem; background-color: rgba(255,255,255,0.03); border: 1px dashed var(--border-color); cursor: pointer;">
                        <span class="material-symbols-rounded">add_circle</span>
                        Tambah Temuan Lain
                    </button>
                <?php endif; ?>

                <!-- Action buttons -->
                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><?= $edit_finding ? 'Perbarui Temuan' : 'Daftarkan Temuan' ?></button>
                    <button type="button" id="btn-cancel-form" class="btn btn-secondary" style="min-width: 100px;">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const divisionAreas = <?= json_encode($predefined_areas) ?>;
    const editFinding = <?= json_encode($edit_finding) ?>;
    
    const divisionSelect = document.getElementById('division_id');
    const modal = document.getElementById('form-modal');
    const btnTriggerAdd = document.getElementById('btn-trigger-add');
    const btnCloseModal = document.getElementById('modal-close-btn');
    const btnCancelForm = document.getElementById('btn-cancel-form');

    // Automatically open modal in Edit Mode
    if (editFinding && modal) {
        modal.classList.add('active');
    }

    // Toggle modal show
    if (btnTriggerAdd && modal) {
        btnTriggerAdd.addEventListener('click', () => {
            modal.classList.add('active');
        });
    }

    // Toggle modal close handlers
    function closeModal() {
        if (editFinding) {
            window.location.href = 'findings.php';
        } else if (modal) {
            modal.classList.remove('active');
        }
    }

    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', closeModal);
    }
    if (btnCancelForm) {
        btnCancelForm.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal();
        });
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    function populateAreaSelect(selectElement, divisionId, selectedAreaName = '') {
        selectElement.innerHTML = '';
        
        if (!divisionId) {
            selectElement.innerHTML = '<option value="" disabled selected>Pilih Divisi Terlebih Dahulu</option>';
            return;
        }

        const areas = divisionAreas.filter(a => a.division_id == divisionId);
        
        const placeholderOpt = document.createElement('option');
        placeholderOpt.value = '';
        placeholderOpt.disabled = true;
        placeholderOpt.selected = !selectedAreaName;
        placeholderOpt.textContent = 'Pilih Area';
        selectElement.appendChild(placeholderOpt);

        let areaMatched = false;

        areas.forEach(a => {
            const opt = document.createElement('option');
            opt.value = a.name;
            opt.textContent = a.name;
            if (selectedAreaName && a.name.toLowerCase() === selectedAreaName.toLowerCase()) {
                opt.selected = true;
                areaMatched = true;
            }
            selectElement.appendChild(opt);
        });

        const manualOpt = document.createElement('option');
        manualOpt.value = '__manual__';
        manualOpt.textContent = 'Lainnya (Ketik Manual)';
        if (selectedAreaName && !areaMatched) {
            manualOpt.selected = true;
        }
        selectElement.appendChild(manualOpt);

        // Update input names and visibility for this specific select
        const card = selectElement.closest('.finding-item-card') || selectElement.closest('form');
        const manualGroup = card ? card.querySelector('.manual-area-group-item, #manual_area_group') : null;
        const manualInput = card ? card.querySelector('.manual-area-input-item, #area') : null;

        if (manualGroup && manualInput) {
            if (selectElement.value === '__manual__') {
                manualGroup.style.display = 'block';
                manualInput.setAttribute('required', 'required');
                manualInput.setAttribute('name', editFinding ? 'area' : 'area[]');
                selectElement.removeAttribute('name');
                if (selectedAreaName && selectedAreaName !== '__manual__') {
                    manualInput.value = selectedAreaName;
                }
            } else {
                manualGroup.style.display = 'none';
                manualInput.removeAttribute('required');
                manualInput.removeAttribute('name');
                selectElement.setAttribute('name', editFinding ? 'area' : 'area[]');
            }
        }
    }

    // Handle select element change event
    document.addEventListener('change', (e) => {
        if (e.target && (e.target.classList.contains('area-select-item') || e.target.id === 'area_select')) {
            const selectElement = e.target;
            const card = selectElement.closest('.finding-item-card') || selectElement.closest('form');
            const manualGroup = card ? card.querySelector('.manual-area-group-item, #manual_area_group') : null;
            const manualInput = card ? card.querySelector('.manual-area-input-item, #area') : null;

            if (manualGroup && manualInput) {
                if (selectElement.value === '__manual__') {
                    manualGroup.style.display = 'block';
                    manualInput.setAttribute('required', 'required');
                    manualInput.setAttribute('name', editFinding ? 'area' : 'area[]');
                    selectElement.removeAttribute('name');
                    manualInput.value = '';
                } else {
                    manualGroup.style.display = 'none';
                    manualInput.removeAttribute('required');
                    manualInput.removeAttribute('name');
                    selectElement.setAttribute('name', editFinding ? 'area' : 'area[]');
                }
            }
        }
    });

    divisionSelect.addEventListener('change', () => {
        const divId = divisionSelect.value;
        if (editFinding) {
            const areaSelect = document.getElementById('area_select');
            if (areaSelect) populateAreaSelect(areaSelect, divId);
        } else {
            const selects = document.querySelectorAll('.area-select-item');
            selects.forEach(select => {
                populateAreaSelect(select, divId);
            });
        }
    });

    if (editFinding && editFinding.division_id) {
        const areaSelect = document.getElementById('area_select');
        if (areaSelect) {
            populateAreaSelect(areaSelect, editFinding.division_id, editFinding.area);
        }
    } else if (divisionSelect.value) {
        const selects = document.querySelectorAll('.area-select-item');
        selects.forEach(select => {
            populateAreaSelect(select, divisionSelect.value);
        });
    }

    // Dynamic Multi-Item Findings Add Handler
    const container = document.getElementById('findings-container');
    const btnAdd = document.getElementById('btn-add-item');
    let itemIndex = 1;

    if (btnAdd && container) {
        btnAdd.addEventListener('click', () => {
            itemIndex++;
            const itemHtml = `
                <div class="finding-item-card" style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.25rem; background-color: rgba(255, 255, 255, 0.01); position: relative;">
                    <h4 class="item-title-text" style="margin: 0 0 1rem 0; font-size: 0.9rem; font-weight: 700; color: var(--accent); display: flex; justify-content: space-between; align-items: center;">
                        Item Temuan #${itemIndex}
                        <button type="button" class="btn-remove-item" style="background: none; border: none; color: var(--danger); font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 0.15rem; font-weight: 600;">
                            <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span> Hapus
                        </button>
                    </h4>
                    
                    <div class="form-row-2">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Area Audit *</label>
                            <select class="form-control area-select-item" name="area[]" required>
                                <!-- populated dynamically -->
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Foto Temuan (Sebelum Perbaikan) *</label>
                            <div class="custom-file-row">
                                <div class="custom-file-col">
                                    <label class="custom-file-upload">
                                        <span class="material-symbols-rounded upload-icon">photo_library</span>
                                        <span class="custom-file-label">Galeri / File</span>
                                        <span class="custom-file-subtext">Belum ada file</span>
                                        <input type="file" name="finding_photo_${itemIndex-1}[]" class="hidden-file-input file-input-group" data-type="gallery" accept="image/*" multiple required>
                                    </label>
                                </div>
                                <div class="custom-file-col">
                                    <label class="custom-file-upload">
                                        <span class="material-symbols-rounded upload-icon">photo_camera</span>
                                        <span class="custom-file-label">Kamera HP</span>
                                        <span class="custom-file-subtext">Belum memotret</span>
                                        <input type="file" name="finding_photo_${itemIndex-1}[]" class="hidden-file-input file-input-group" data-type="camera" accept="image/*" capture="environment">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group manual-area-group-item" style="display: none; margin-top: 1.25rem; margin-bottom: 0;">
                        <label class="form-label">Nama Area Kustom (Ketik Manual) *</label>
                        <input type="text" class="form-control manual-area-input-item" placeholder="Contoh: Kantor lantai 1, Kantin bawah">
                    </div>

                    <div class="form-group" style="margin-top: 1.25rem; margin-bottom: 0;">
                        <label class="form-label">Deskripsi Catatan Temuan *</label>
                        <textarea name="description[]" class="form-control" rows="3" placeholder="Tuliskan catatan detail temuan..." required></textarea>
                    </div>
                </div>
            `;
            const div = document.createElement('div');
            div.innerHTML = itemHtml;
            const newCard = div.firstElementChild;
            container.appendChild(newCard);
            
            // Populate dropdown of the new item
            const newSelect = newCard.querySelector('.area-select-item');
            if (newSelect) {
                populateAreaSelect(newSelect, divisionSelect.value);
            }
            
            reindexItems();
        });

        container.addEventListener('click', (e) => {
            const btnRemove = e.target.closest('.btn-remove-item');
            if (btnRemove) {
                const card = btnRemove.closest('.finding-item-card');
                if (card) {
                    card.remove();
                    reindexItems();
                }
            }
        });
    }

    function reindexItems() {
        const cards = container.querySelectorAll('.finding-item-card');
        itemIndex = 0;
        cards.forEach((card, idx) => {
            itemIndex = idx + 1;
            const titleElement = card.querySelector('.item-title-text');
            if (idx === 0) {
                titleElement.innerHTML = `Item Temuan #${itemIndex}`;
            } else {
                titleElement.innerHTML = `
                    Item Temuan #${itemIndex}
                    <button type="button" class="btn-remove-item" style="background: none; border: none; color: var(--danger); font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 0.15rem; font-weight: 600;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span> Hapus
                    </button>
                `;
            }
            // Update name of all file inputs in the card to remain correct index-based name
            const fileInputs = card.querySelectorAll('input[type="file"]');
            fileInputs.forEach(fileInput => {
                fileInput.setAttribute('name', `finding_photo_${idx}[]`);
            });
        });
    }

    // Delegated listener for custom styled file inputs
    document.addEventListener('change', (e) => {
        if (e.target && e.target.classList.contains('file-input-group')) {
            const input = e.target;
            const container = input.closest('.custom-file-upload');
            const subtext = container ? container.querySelector('.custom-file-subtext') : null;
            
            // Update current field style & text
            if (container && subtext) {
                if (input.files && input.files.length > 0) {
                    container.classList.add('has-file');
                    if (input.files.length === 1) {
                        subtext.textContent = input.files[0].name;
                    } else {
                        subtext.textContent = input.files.length + ' file terpilih';
                    }
                } else {
                    container.classList.remove('has-file');
                    subtext.textContent = input.dataset.type === 'gallery' ? 'Belum ada file' : 'Belum memotret';
                }
            }
            
            // Dynamic validation: make required only if both gallery and camera are empty
            const parentRow = input.closest('.custom-file-row');
            if (parentRow) {
                const groupInputs = parentRow.querySelectorAll('.file-input-group');
                let anyFilled = false;
                groupInputs.forEach(inp => {
                    if (inp.files && inp.files.length > 0) {
                        anyFilled = true;
                    }
                });
                
                groupInputs.forEach(inp => {
                    if (anyFilled) {
                        inp.removeAttribute('required');
                    } else {
                        // Apply required only to gallery input if they are adding new findings
                        if (inp.dataset.type === 'gallery' && !editFinding) {
                            inp.setAttribute('required', 'required');
                        }
                    }
                });
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
