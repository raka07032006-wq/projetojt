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
            <select name="filter_status" class="form-control" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; width: 140px; background-color: var(--bg-main);">
                <option value="">Semua Status</option>
                <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="On Progress" <?= $filter_status === 'On Progress' ? 'selected' : '' ?>>On Progress</option>
                <option value="Done" <?= $filter_status === 'Done' ? 'selected' : '' ?>>Done</option>
            </select>
            <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Filter</button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table>
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
                                <img src="<?= $base_path ?>uploads/<?= htmlspecialchars($f['finding_photo']) ?>" class="img-thumbnail" alt="Before">
                            </td>
                            <td style="text-align: center;">
                                <?php if ($f['improvement_photo']): ?>
                                    <img src="<?= $base_path ?>uploads/<?= htmlspecialchars($f['improvement_photo']) ?>" class="img-thumbnail" alt="After">
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 0.8rem; font-style: italic;">Belum Ada</span>
                                <?php endif; ?>
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
                                <?php if ($akses_perbaikan === 0): ?>
                                    <?php if ($f['status'] === 'Done'): ?>
                                        <div style="text-align: left; background-color: rgba(16, 185, 129, 0.08); padding: 0.5rem; border-radius: var(--radius-sm); border: 1px solid rgba(16, 185, 129, 0.2);">
                                            <div style="font-size: 0.75rem; font-weight: 700; color: var(--success); margin-bottom: 0.15rem;">Tindakan Perbaikan:</div>
                                            <div style="font-size: 0.8rem; color: var(--text-primary); line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($f['improvement_description']) ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-size: 0.85rem; font-style: italic;">Baca-Saja</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($f['status'] !== 'Done'): ?>
                                        <a href="improve.php?id=<?= $f['id'] ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.45rem 0.9rem; font-weight: 600; width: 100%;">
                                            Laporkan Perbaikan
                                        </a>
                                    <?php else: ?>
                                        <div style="text-align: left; background-color: rgba(16, 185, 129, 0.08); padding: 0.5rem; border-radius: var(--radius-sm); border: 1px solid rgba(16, 185, 129, 0.2);">
                                            <div style="font-size: 0.75rem; font-weight: 700; color: var(--success); margin-bottom: 0.15rem;">Tindakan Perbaikan:</div>
                                            <div style="font-size: 0.8rem; color: var(--text-primary); line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($f['improvement_description']) ?></div>
                                            <a href="improve.php?id=<?= $f['id'] ?>" style="font-size: 0.75rem; color: var(--accent); font-weight: 600; text-decoration: underline; display: inline-block; margin-top: 0.25rem;">Ubah Laporan</a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
