<?php
$base_path = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Verify admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

// Fetch stats
$total_findings = $pdo->query("SELECT COUNT(*) FROM findings")->fetchColumn();
$pending_findings = $pdo->query("SELECT COUNT(*) FROM findings WHERE status = 'Pending'")->fetchColumn();
$progress_findings = $pdo->query("SELECT COUNT(*) FROM findings WHERE status = 'On Progress'")->fetchColumn();
$done_findings = $pdo->query("SELECT COUNT(*) FROM findings WHERE status = 'Done'")->fetchColumn();

// Calculate percentage done
$completion_rate = $total_findings > 0 ? round(($done_findings / $total_findings) * 100) : 0;

// Fetch stats by Area (grouped dynamically from typed areas in findings) with scores for current month
$current_month = intval(date('n'));
$current_year = intval(date('Y'));

$stmt_area_stats = $pdo->prepare("
    SELECT f.area AS name, ae.nilai_5r,
           COUNT(f.id) as total_f,
           SUM(CASE WHEN f.status = 'Pending' THEN 1 ELSE 0 END) as pending_f,
           SUM(CASE WHEN f.status = 'On Progress' THEN 1 ELSE 0 END) as progress_f,
           SUM(CASE WHEN f.status = 'Done' THEN 1 ELSE 0 END) as done_f
    FROM findings f
    LEFT JOIN areas a ON f.area = a.name
    LEFT JOIN area_evaluations ae ON a.id = ae.area_id AND ae.bulan = :bulan AND ae.tahun = :tahun
    GROUP BY f.area, ae.nilai_5r
    ORDER BY f.area ASC
");
$stmt_area_stats->execute([
    'bulan' => $current_month,
    'tahun' => $current_year
]);
$area_stats = $stmt_area_stats->fetchAll();

// Fetch 5 most recent findings
$recent_findings = $pdo->query("
    SELECT f.*, f.area AS area_name, d.name AS division_name 
    FROM findings f
    JOIN divisions d ON f.division_id = d.id
    ORDER BY f.created_at DESC
    LIMIT 5
")->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Administrator</h1>
        <p class="page-subtitle">Ringkasan hasil audit 5R dan status perbaikan seluruh divisi</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Total Temuan</span>
        <span class="stat-val"><?= $total_findings ?></span>
    </div>
    <div class="stat-card pending">
        <span class="stat-label">Pending</span>
        <span class="stat-val"><?= $pending_findings ?></span>
    </div>
    <div class="stat-card progress">
        <span class="stat-label">Sedang Diproses</span>
        <span class="stat-val"><?= $progress_findings ?></span>
    </div>
    <div class="stat-card done">
        <span class="stat-label">Selesai (Done)</span>
        <span class="stat-val"><?= $done_findings ?></span>
    </div>
</div>

<!-- Progress Bar -->
<div class="card-section" style="padding: 1.5rem; margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
        <span style="font-weight: 700; font-size: 0.95rem;">Tingkat Penyelesaian Perbaikan</span>
        <span style="font-weight: 800; color: var(--success); font-size: 0.95rem;"><?= $completion_rate ?>%</span>
    </div>
    <div style="width: 100%; height: 12px; background-color: var(--border-color); border-radius: 9999px; overflow: hidden;">
        <div style="width: <?= $completion_rate ?>%; height: 100%; background: linear-gradient(to right, var(--accent) 0%, var(--success) 100%); transition: width 0.5s ease-in-out;"></div>
    </div>
</div>

<div class="layout-grid">
    <!-- Area Performance Table -->
    <div class="card-section">
        <div class="card-section-header">
            <h2 class="card-section-title">Status Audit Per Area</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Area Audit</th>
                        <th>Temuan</th>
                        <th>Pending</th>
                        <th>Proses</th>
                        <th>Selesai</th>
                        <th>Penyelesaian</th>
                        <th style="text-align: center;">Nilai 5R</th>
                        <th style="text-align: center;">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($area_stats)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-secondary);">Belum ada data area.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($area_stats as $area): 
                            $pct = $area['total_f'] > 0 ? round(($area['done_f'] / $area['total_f']) * 100) : 100;
                            $color_pct = 'var(--text-secondary)';
                            if ($area['total_f'] > 0) {
                                if ($pct == 100) $color_pct = 'var(--success)';
                                elseif ($pct >= 50) $color_pct = 'var(--info)';
                                else $color_pct = 'var(--danger)';
                            }
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($area['name']) ?></td>
                                <td><?= $area['total_f'] ?></td>
                                <td style="color: var(--warning);"><?= $area['pending_f'] ?></td>
                                <td style="color: var(--info);"><?= $area['progress_f'] ?></td>
                                <td style="color: var(--success);"><?= $area['done_f'] ?></td>
                                <td style="font-weight: 700; color: <?= $color_pct ?>;"><?= $pct ?>%</td>
                                <td style="text-align: center; font-weight: 700; color: var(--accent);"><?= $area['nilai_5r'] !== null ? number_format($area['nilai_5r'], 2) : '-' ?></td>
                                <td style="text-align: center; font-weight: 800;">
                                    <?php 
                                        $grade = get_letter_grade($area['nilai_5r']);
                                        $grade_color = 'var(--text-secondary)';
                                        if ($grade === 'A' || $grade === 'A-') $grade_color = 'var(--success)';
                                        elseif ($grade === 'B+' || $grade === 'B' || $grade === 'B-') $grade_color = 'var(--info)';
                                        elseif ($grade === 'C+' || $grade === 'C') $grade_color = 'var(--warning)';
                                        elseif ($grade === 'D' || $grade === 'E') $grade_color = 'var(--danger)';
                                    ?>
                                    <span style="color: <?= $grade_color ?>;"><?= $grade ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Findings Side List -->
    <div class="card-section">
        <div class="card-section-header">
            <h2 class="card-section-title">Temuan Terbaru</h2>
        </div>
        <div style="padding: 1rem;">
            <?php if (empty($recent_findings)): ?>
                <p style="text-align: center; padding: 2rem 0; color: var(--text-secondary);">Belum ada temuan yang didata.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($recent_findings as $finding): 
                        $badge_class = 'badge-pending';
                        if ($finding['status'] === 'On Progress') $badge_class = 'badge-progress';
                        if ($finding['status'] === 'Done') $badge_class = 'badge-done';
                    ?>
                        <div style="background-color: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 1rem; position: relative;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <span class="badge <?= $badge_class ?>" style="font-size: 0.7rem;"><?= htmlspecialchars($finding['status']) ?></span>
                                <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= date('d M Y', strtotime($finding['created_at'])) ?></span>
                            </div>
                            <h4 style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem;"><?= htmlspecialchars($finding['area_name']) ?></h4>
                            <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 0.5rem;"><?= htmlspecialchars($finding['description']) ?></p>
                            <div style="font-size: 0.75rem; font-weight: 600; color: var(--accent);">Divisi: <?= htmlspecialchars($finding['division_name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
