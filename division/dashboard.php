<?php
$base_path = '../';
require_once __DIR__ . '/../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check logged in state and role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'division') {
    header("Location: ../dashboard.php");
    exit;
}

$division_id = $_SESSION['division_id'];
$division_name = $_SESSION['division_name'];
$area_id = $_SESSION['area_id'] ?? null;
$area_name = $_SESSION['area_name'] ?? null;

$report_finding = null;
$view_finding = null;
$after_photos = [];
$view_before_photos = [];

// Real-time division edit permission check
$akses_perbaikan = 1;
if ($division_id > 0) {
    $stmt_perm = $pdo->prepare("SELECT akses_perbaikan FROM divisions WHERE id = :id");
    $stmt_perm->execute(['id' => $division_id]);
    $akses_perbaikan = intval($stmt_perm->fetchColumn() ?? 1);
}

// Handle GET view_finding (Detail Temuan Audit Modal)
if (isset($_GET['view_finding'])) {
    $finding_id = intval($_GET['view_finding']);
    if ($finding_id > 0) {
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
        $view_finding = $stmt->fetch();

        if ($view_finding) {
            $stmt_bef = $pdo->prepare("SELECT * FROM finding_images WHERE finding_id = :finding_id AND type = 'before'");
            $stmt_bef->execute(['finding_id' => $finding_id]);
            $view_before_photos = $stmt_bef->fetchAll();

            $stmt_aft = $pdo->prepare("SELECT * FROM finding_images WHERE finding_id = :finding_id AND type = 'after'");
            $stmt_aft->execute(['finding_id' => $finding_id]);
            $view_after_photos = $stmt_aft->fetchAll();
        }
    }
}

// Handle GET report (Form Laporan Perbaikan Modal)
if (isset($_GET['report'])) {
    $finding_id = intval($_GET['report']);
    if ($finding_id > 0 && $akses_perbaikan !== 0) {
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
        $report_finding = $stmt->fetch();

        if ($report_finding) {
            $stmt_after = $pdo->prepare("SELECT * FROM finding_images WHERE finding_id = :finding_id AND type = 'after'");
            $stmt_after->execute(['finding_id' => $finding_id]);
            $after_photos = $stmt_after->fetchAll();
        }
    }
}

// Handle POST report perbaikan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report_improvement') {
    $finding_id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'On Progress');
    $improvement_description = trim($_POST['improvement_description'] ?? '');
    
    if ($finding_id > 0 && $akses_perbaikan !== 0) {
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

        if ($finding) {
            $stmt_cnt = $pdo->prepare("SELECT COUNT(*) FROM finding_images WHERE finding_id = :finding_id AND type = 'after'");
            $stmt_cnt->execute(['finding_id' => $finding_id]);
            $existing_after_count = intval($stmt_cnt->fetchColumn());

            $deleted_count = isset($_POST['delete_photo']) ? count($_POST['delete_photo']) : 0;
            $uploaded_valid_files = [];
            $has_upload_errors = false;
            $error = '';

            if (isset($_FILES['improvement_photo']['name']) && is_array($_FILES['improvement_photo']['name'])) {
                $file_count = count($_FILES['improvement_photo']['name']);
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['improvement_photo']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['improvement_photo']['name'][$i];
                        $file_tmp = $_FILES['improvement_photo']['tmp_name'][$i];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        if (in_array($ext, $allowed_exts)) {
                            $uploaded_valid_files[] = [
                                'tmp' => $file_tmp,
                                'ext' => $ext
                            ];
                        } else {
                            $error = 'Ekstensi file foto perbaikan tidak valid. Hanya JPG, JPEG, PNG, dan WEBP yang diizinkan.';
                            $has_upload_errors = true;
                            break;
                        }
                    } else if ($_FILES['improvement_photo']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $error = 'Gagal mengunggah salah satu foto perbaikan.';
                        $has_upload_errors = true;
                        break;
                    }
                }
            }

            if (!$has_upload_errors) {
                $total_after_photos = $existing_after_count - $deleted_count + count($uploaded_valid_files);

                if ($status === 'Done' && empty($improvement_description)) {
                    $error = 'Harap isi deskripsi tindakan perbaikan jika status sudah selesai (Done).';
                } else if ($status === 'Done' && $total_after_photos <= 0) {
                    $error = 'Foto hasil perbaikan wajib diunggah untuk mengubah status menjadi selesai (Done).';
                } else {
                    try {
                        $pdo->beginTransaction();

                        $stmt = $pdo->prepare("
                            UPDATE findings 
                            SET status = :status, 
                                improvement_description = :improvement_description
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            'status' => $status,
                            'improvement_description' => $improvement_description,
                            'id' => $finding_id
                        ]);

                        if (isset($_POST['delete_photo']) && is_array($_POST['delete_photo'])) {
                            foreach ($_POST['delete_photo'] as $photo_id) {
                                $photo_id = intval($photo_id);
                                $img = $pdo->query("SELECT image_path FROM finding_images WHERE id = $photo_id AND finding_id = $finding_id AND type = 'after'")->fetch();
                                if ($img) {
                                    $target_path = __DIR__ . '/../uploads/' . $img['image_path'];
                                    if (file_exists($target_path)) {
                                        @unlink($target_path);
                                    }
                                    $pdo->exec("DELETE FROM finding_images WHERE id = $photo_id");
                                }
                            }
                        }

                        if (!empty($uploaded_valid_files)) {
                            $stmt_img = $pdo->prepare("
                                INSERT INTO finding_images (finding_id, image_path, image_data, mime_type, type) 
                                VALUES (:finding_id, :image_path, :image_data, :mime_type, 'after')
                            ");
                            foreach ($uploaded_valid_files as $uf) {
                                $fake_filename = 'after_' . time() . '_' . uniqid() . '.' . $uf['ext'];
                                $mime = 'image/' . ($uf['ext'] === 'jpg' ? 'jpeg' : $uf['ext']);
                                $compressed_data = compress_uploaded_image($uf['tmp'], $mime);
                                
                                $stmt_img->execute([
                                    'finding_id' => $finding_id,
                                    'image_path' => $fake_filename,
                                    'image_data' => $compressed_data,
                                    'mime_type' => $mime
                                ]);
                            }
                        }

                        $pdo->commit();

                        $div_name = $_SESSION['division_name'] ?? 'Staf Divisi';
                        $area_name_finding = $finding['area_name'] ?? 'terkait';
                        create_notification(
                            $pdo,
                            $finding_id,
                            $division_id,
                            "Laporan Perbaikan",
                            "Divisi $div_name melaporkan tindakan perbaikan di area $area_name_finding: \"$improvement_description\"",
                            "admin"
                        );

                        $_SESSION['success_message'] = 'Tindakan perbaikan berhasil dilaporkan.';
                        header("Location: dashboard.php");
                        exit;
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $error = 'Gagal menyimpan laporan perbaikan: ' . $e->getMessage();
                    }
                }
            }

            if (!empty($error)) {
                $_SESSION['error_message'] = $error;
                header("Location: dashboard.php?report=" . $finding_id);
                exit;
            }
        }
    }
}



$area_id = $_SESSION['area_id'] ?? null;
$area_name = $_SESSION['area_name'] ?? null;

$area_filter_sql = "";
$stats_params = ['div_id' => $division_id];
if ($area_id) {
    $area_filter_sql = " AND area = :area_name";
    $stats_params['area_name'] = $area_name;
}

// Fetch stats specific to this division (and area if restricted)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM findings WHERE division_id = :div_id" . $area_filter_sql);
$stmt->execute($stats_params);
$total_f = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM findings WHERE division_id = :div_id AND status = 'Pending'" . $area_filter_sql);
$stmt->execute($stats_params);
$pending_f = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM findings WHERE division_id = :div_id AND status = 'On Progress'" . $area_filter_sql);
$stmt->execute($stats_params);
$progress_f = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM findings WHERE division_id = :div_id AND status = 'Done'" . $area_filter_sql);
$stmt->execute($stats_params);
$done_f = $stmt->fetchColumn();

// Calculate percentage done
$completion_rate = $total_f > 0 ? round(($done_f / $total_f) * 100) : 100;

// Filter selection
$filter_status = trim($_GET['filter_status'] ?? '');

$where_clauses = ["f.division_id = :div_id"];
$params = ['div_id' => $division_id];

if ($area_id) {
    $where_clauses[] = "f.area = :area_name";
    $params['area_name'] = $area_name;
}

if (!empty($filter_status)) {
    $where_clauses[] = "f.status = :filter_status";
    $params['filter_status'] = $filter_status;
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Fetch findings assigned to division
$query_findings = "
    SELECT f.*, f.area AS area_name 
    FROM findings f
    $where_sql
    ORDER BY f.id DESC
";
$stmt = $pdo->prepare($query_findings);
$stmt->execute($params);
$my_findings = $stmt->fetchAll();

// Batch fetch images to avoid N+1 queries
$finding_ids = array_column($my_findings, 'id');
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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Divisi <?= htmlspecialchars($division_name) ?></h1>
        <p class="page-subtitle">
            <?php if ($area_id): ?>
                Kelola tindakan perbaikan untuk area: <strong><?= htmlspecialchars($area_name) ?></strong>
            <?php else: ?>
                Kelola tindakan perbaikan atas temuan-temuan audit 5R di seluruh area divisi Anda
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success" style="margin-bottom: 2rem;">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger" style="margin-bottom: 2rem;">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if ($akses_perbaikan === 0): ?>
    <div class="alert alert-danger" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 2rem;">
        <span class="material-symbols-rounded">lock</span>
        Akses Perbaikan Dinonaktifkan: Anda dalam mode Baca-Saja (Read-Only) oleh Administrator.
    </div>
<?php endif; ?>

<!-- Stats Card Specific to Division -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Total Tugas Temuan</span>
        <span class="stat-val"><?= $total_f ?></span>
    </div>
    <div class="stat-card pending">
        <span class="stat-label">Pending</span>
        <span class="stat-val"><?= $pending_f ?></span>
    </div>
    <div class="stat-card progress">
        <span class="stat-label">Sedang Dikerjakan</span>
        <span class="stat-val"><?= $progress_f ?></span>
    </div>
    <div class="stat-card done">
        <span class="stat-label">Selesai (Done)</span>
        <span class="stat-val"><?= $done_f ?></span>
    </div>
</div>

<!-- Progress Bar -->
<div class="card-section" style="padding: 1.5rem; margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
        <span style="font-weight: 700; font-size: 0.95rem;">Tingkat Penyelesaian Perbaikan Divisi</span>
        <span style="font-weight: 800; color: var(--success); font-size: 0.95rem;"><?= $completion_rate ?>%</span>
    </div>
    <div style="width: 100%; height: 12px; background-color: var(--border-color); border-radius: 9999px; overflow: hidden;">
        <div style="width: <?= $completion_rate ?>%; height: 100%; background: linear-gradient(to right, var(--accent) 0%, var(--success) 100%); transition: width 0.5s ease-in-out;"></div>
    </div>
</div>

<!-- Findings List Card -->
<div class="card-section">
    <div class="card-section-header" style="flex-wrap: wrap; gap: 1rem;">
        <h3 class="card-section-title" style="min-width: 180px;">Tugas Temuan Audit</h3>
        
        <!-- Filter Form -->
        <form action="dashboard.php" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
            <select name="filter_status" class="form-control form-control-sm" style="width: 140px;">
                <option value="">Semua Status</option>
                <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="On Progress" <?= $filter_status === 'On Progress' ? 'selected' : '' ?>>On Progress</option>
                <option value="Done" <?= $filter_status === 'Done' ? 'selected' : '' ?>>Done</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table-wide">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Area Audit</th>
                    <th>Catatan Temuan</th>
                    <th style="text-align: center; width: 130px;">Foto Sebelum</th>
                    <th style="text-align: center; width: 130px;">Foto Sesudah</th>
                    <th>PIC & Target</th>
                    <th>Status</th>
                    <th style="width: 180px; text-align: center;">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($my_findings)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 2.5rem;">Tidak ada temuan audit yang dibebankan kepada divisi Anda.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($my_findings as $f): 
                        $badge = 'badge-pending';
                        if ($f['status'] === 'On Progress') $badge = 'badge-progress';
                        if ($f['status'] === 'Done') $badge = 'badge-done';
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                             <td style="font-weight: 700;"><?= htmlspecialchars($f['area_name']) ?></td>
                             <td style="font-size: 0.875rem; line-height: 1.4; max-width: 250px;">
                                 <?= htmlspecialchars($f['description']) ?>
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
                                 <div style="font-size: 0.85rem;"><span style="color: var(--text-secondary);">PIC:</span> <?= htmlspecialchars($f['pic'] ?: '-') ?></div>
                                 <div style="font-size: 0.85rem; margin-top: 0.25rem;">
                                     <span style="color: var(--text-secondary);">Batas:</span> 
                                     <?= $f['due_date'] ? date('d M Y', strtotime($f['due_date'])) : '-' ?>
                                 </div>
                             </td>
                             <td>
                                 <span class="badge <?= $badge ?>"><?= htmlspecialchars($f['status']) ?></span>
                             </td>
                             <td style="text-align: center;">
                                 <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                                     <!-- Detail/View Info Button -->
                                     <a href="dashboard.php?view_finding=<?= $f['id'] ?>" class="action-link edit" title="Detail Temuan & Perbaikan" style="padding: 0.4rem; border-radius: var(--radius-sm); background-color: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); display: inline-flex; align-items: center; justify-content: center; height: 32px; width: 32px;">
                                         <span class="material-symbols-rounded" style="font-size: 1.25rem; color: var(--accent);">info</span>
                                     </a>
                                     
                                     <!-- Report/Edit Button -->
                                     <?php if ($akses_perbaikan !== 0): ?>
                                         <a href="dashboard.php?report=<?= $f['id'] ?>" class="action-link edit" title="<?= $f['status'] === 'Done' ? 'Ubah Laporan Perbaikan' : 'Laporkan Perbaikan' ?>" style="padding: 0.4rem; border-radius: var(--radius-sm); background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); display: inline-flex; align-items: center; justify-content: center; height: 32px; width: 32px;">
                                             <span class="material-symbols-rounded" style="font-size: 1.25rem; color: var(--success);">rate_review</span>
                                         </a>
                                     <?php endif; ?>
                                 </div>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Detail Temuan Audit -->
<div id="finding-details-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Detail Temuan Audit</h2>
            <span class="modal-close" id="details-close-btn">&times;</span>
        </div>
        <div class="modal-body">
            <?php if ($view_finding): ?>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem;">Area Audit</label>
                    <p style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($view_finding['area_name']) ?></p>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem;">Catatan Temuan</label>
                    <p style="font-size: 0.95rem; line-height: 1.5; color: var(--text-primary); background-color: rgba(15, 23, 42, 0.4); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);"><?= htmlspecialchars($view_finding['description']) ?></p>
                </div>

                <div class="meta-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label class="form-label" style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem;">PIC</label>
                        <p style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($view_finding['pic'] ?: '-') ?></p>
                    </div>
                    <div>
                        <label class="form-label" style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem;">Target Selesai</label>
                        <p style="font-size: 0.9rem; font-weight: 600; color: var(--warning);"><?= $view_finding['due_date'] ? date('d M Y', strtotime($view_finding['due_date'])) : '-' ?></p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; display: block; margin-bottom: 0.5rem;">Foto Bukti Temuan (Sebelum)</label>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if (!empty($view_before_photos)): ?>
                            <?php foreach ($view_before_photos as $bp): ?>
                                <a href="<?= $base_path ?>view_image.php?id=<?= $bp['id'] ?>" target="_blank">
                                    <img src="<?= $base_path ?>view_image.php?id=<?= $bp['id'] ?>" class="comparison-img" style="width: 100px; height: 100px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);" alt="Before Picture">
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color: var(--text-secondary); font-size: 0.85rem; font-style: italic;">Tidak ada foto bukti</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($view_finding['status'] === 'Done'): ?>
                    <div style="margin-top: 1.25rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label class="form-label" style="color: var(--success); text-transform: uppercase; font-size: 0.75rem; font-weight: 700;">Tindakan Perbaikan (Selesai)</label>
                            <p style="font-size: 0.95rem; line-height: 1.5; color: var(--text-primary); background-color: rgba(16, 185, 129, 0.04); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid rgba(16, 185, 129, 0.2);"><?= htmlspecialchars($view_finding['improvement_description']) ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; display: block; margin-bottom: 0.5rem;">Foto Hasil Perbaikan (Sesudah)</label>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <?php if (!empty($view_after_photos)): ?>
                                    <?php foreach ($view_after_photos as $ap): ?>
                                        <a href="<?= $base_path ?>view_image.php?id=<?= $ap['id'] ?>" target="_blank">
                                            <img src="<?= $base_path ?>view_image.php?id=<?= $ap['id'] ?>" class="comparison-img" style="width: 100px; height: 100px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);" alt="After Picture">
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 0.85rem; font-style: italic;">Tidak ada foto hasil</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" id="details-close-btn-bottom" class="btn btn-secondary" style="min-width: 100px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Form Laporan Perbaikan -->
<div id="report-form-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Form Laporan Perbaikan</h2>
            <span class="modal-close" id="report-close-btn">&times;</span>
        </div>
        <div class="modal-body">
            <?php if ($report_finding): ?>
                <div style="margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); display: block;">Area Kerja</span>
                    <strong style="font-size: 1rem; color: var(--text-primary);"><?= htmlspecialchars($report_finding['area_name']) ?></strong>
                </div>

                <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="report_improvement">
                    <input type="hidden" name="id" value="<?= $report_finding['id'] ?>">
                    
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="status" class="form-label" style="font-weight: 600;">Status Tindakan *</label>
                        <select name="status" id="status" class="form-control" style="margin-top: 0.25rem;" required>
                            <option value="On Progress" <?= $report_finding['status'] === 'On Progress' ? 'selected' : '' ?>>On Progress (Sedang Dikerjakan)</option>
                            <option value="Done" <?= $report_finding['status'] === 'Done' ? 'selected' : '' ?>>Done (Selesai)</option>
                        </select>
                        <small style="display: block; color: var(--text-secondary); margin-top: 0.25rem; font-size: 0.75rem;">
                            Status "Done" mewajibkan Anda untuk mengunggah foto hasil perbaikan.
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="improvement_description" class="form-label" style="font-weight: 600;">Deskripsi Tindakan Perbaikan *</label>
                        <textarea name="improvement_description" id="improvement_description" class="form-control" rows="4" style="margin-top: 0.25rem;" placeholder="Tuliskan tindakan konkret yang telah dilakukan..." required><?= htmlspecialchars($report_finding['improvement_description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label class="form-label" style="font-weight: 600;">Unggah Foto Hasil Perbaikan (Sesudah)</label>
                        <div class="custom-file-row">
                            <div class="custom-file-col">
                                <label class="custom-file-upload">
                                    <span class="material-symbols-rounded upload-icon">photo_library</span>
                                    <span class="custom-file-label">Galeri / File</span>
                                    <span class="custom-file-subtext">Belum ada file</span>
                                    <input type="file" name="improvement_photo[]" id="improvement_photo" class="hidden-file-input file-input-group" data-type="gallery" accept="image/*" multiple>
                                </label>
                            </div>
                            <div class="custom-file-col">
                                <label class="custom-file-upload">
                                    <span class="material-symbols-rounded upload-icon">photo_camera</span>
                                    <span class="custom-file-label">Kamera HP</span>
                                    <span class="custom-file-subtext">Belum memotret</span>
                                    <input type="file" name="improvement_photo[]" id="improvement_photo_camera" class="hidden-file-input file-input-group" data-type="camera" accept="image/*" capture="environment">
                                </label>
                            </div>
                        </div>
                        <small style="display: block; color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.75rem;">
                            Status "Done" membutuhkan minimal satu foto perbaikan. Anda dapat memilih beberapa foto dari galeri atau memotret langsung dengan kamera HP.
                        </small>
                    </div>

                    <?php if (!empty($after_photos)): ?>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label" style="font-weight: 700; color: var(--text-primary); display: block; margin-bottom: 0.5rem;">Foto Hasil Perbaikan Saat Ini (Centang untuk menghapus):</label>
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                <?php foreach ($after_photos as $ap): ?>
                                    <div style="position: relative; width: 80px; text-align: center; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.2rem; background-color: rgba(255,255,255,0.02);">
                                        <img src="<?= $base_path ?>view_image.php?id=<?= $ap['id'] ?>" class="img-thumbnail" style="width: 65px; height: 65px; object-fit: cover; display: block; margin: 0 auto 0.2rem;" alt="Improvement Photo">
                                        <label style="font-size: 0.75rem; color: var(--danger); font-weight: 700; display: inline-flex; align-items: center; gap: 0.15rem; cursor: pointer;">
                                            <input type="checkbox" name="delete_photo[]" value="<?= $ap['id'] ?>"> Hapus
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan Laporan</button>
                        <button type="button" id="report-cancel-btn" class="btn btn-secondary" style="min-width: 100px;">Batal</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const viewFinding = <?= json_encode($view_finding) ?>;
    const reportFinding = <?= json_encode($report_finding) ?>;
    
    const detailsModal = document.getElementById('finding-details-modal');
    const detailsCloseBtn = document.getElementById('details-close-btn');
    const detailsCloseBtnBottom = document.getElementById('details-close-btn-bottom');
    
    const reportModal = document.getElementById('report-form-modal');
    const reportCloseBtn = document.getElementById('report-close-btn');
    const reportCancelBtn = document.getElementById('report-cancel-btn');

    // Trigger details modal
    if (viewFinding && detailsModal) {
        detailsModal.classList.add('active');
    }
    
    // Trigger report modal
    if (reportFinding && reportModal) {
        reportModal.classList.add('active');
    }

    // Close details helper
    const closeDetails = () => {
        if (detailsModal) {
            detailsModal.classList.remove('active');
            const url = new URL(window.location);
            url.searchParams.delete('view_finding');
            window.location.href = url.pathname + url.search;
        }
    };
    
    // Close report helper
    const closeReport = () => {
        if (reportModal) {
            reportModal.classList.remove('active');
            const url = new URL(window.location);
            url.searchParams.delete('report');
            window.location.href = url.pathname + url.search;
        }
    };

    if (detailsCloseBtn) detailsCloseBtn.addEventListener('click', closeDetails);
    if (detailsCloseBtnBottom) detailsCloseBtnBottom.addEventListener('click', closeDetails);
    
    if (reportCloseBtn) reportCloseBtn.addEventListener('click', closeReport);
    if (reportCancelBtn) reportCancelBtn.addEventListener('click', closeReport);

    // Close on backdrop click
    window.addEventListener('click', (e) => {
        if (e.target === detailsModal) {
            closeDetails();
        }
        if (e.target === reportModal) {
            closeReport();
        }
    });

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
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
