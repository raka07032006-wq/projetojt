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

// Restricted to Ketua Divisi only (area_id is null)
$area_id = $_SESSION['area_id'] ?? null;
if ($area_id) {
    header("Location: dashboard.php");
    exit;
}

$division_id = $_SESSION['division_id'];
$division_name = $_SESSION['division_name'];

$selected_month = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('n'));
$selected_year = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = intval(date('n'));
}
if ($selected_year < 2025 || $selected_year > 2030) {
    $selected_year = intval(date('Y'));
}

// Fetch area members list and findings stats for selected month/year
$area_members = $pdo->prepare("
    SELECT a.id AS area_id, a.name AS area_name, ae.nilai_5r, u.username,
           COUNT(f.id) AS total_findings,
           SUM(CASE WHEN f.status = 'Pending' THEN 1 ELSE 0 END) AS pending_findings,
           SUM(CASE WHEN f.status = 'On Progress' THEN 1 ELSE 0 END) AS progress_findings,
           SUM(CASE WHEN f.status = 'Done' THEN 1 ELSE 0 END) AS done_findings
    FROM areas a
    LEFT JOIN users u ON u.area_id = a.id AND u.role = 'division'
    LEFT JOIN findings f ON f.area = a.name AND f.division_id = a.division_id
    LEFT JOIN area_evaluations ae ON a.id = ae.area_id AND ae.bulan = :bulan AND ae.tahun = :tahun
    WHERE a.division_id = :div_id
    GROUP BY a.id, ae.nilai_5r, u.username
    ORDER BY a.name ASC
");
$area_members->execute([
    'div_id' => $division_id,
    'bulan' => $selected_month,
    'tahun' => $selected_year
]);
$area_members_list = $area_members->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Monitoring Area Divisi <?= htmlspecialchars($division_name) ?></h1>
        <p class="page-subtitle">Pantau status penyelesaian perbaikan audit 5R dan hasil penilaian Nilai 5R setiap area</p>
    </div>
</div>

<!-- Month & Year Filter Selector -->
<div class="card-section" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="areas.php" style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; margin-bottom: 0;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="bulan" style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">Pilih Bulan Evaluasi:</label>
            <select name="bulan" id="bulan" class="form-control form-control-sm" style="width: 160px;" onchange="this.form.submit()">
                <?php
                $bulan_names = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                foreach ($bulan_names as $m => $name) {
                    $selected = ($m == $selected_month) ? 'selected' : '';
                    echo "<option value='$m' $selected>$name</option>";
                }
                ?>
            </select>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="tahun" style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">Tahun:</label>
            <select name="tahun" id="tahun" class="form-control form-control-sm" style="width: 110px;" onchange="this.form.submit()">
                <?php
                for ($y = 2025; $y <= 2030; $y++) {
                    $selected = ($y == $selected_year) ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>
        </div>
        <div style="margin-left: 1.5rem; display: flex; gap: 0.5rem; align-items: center;">
            <a href="../print_report.php?bulan=<?= $selected_month ?>&tahun=<?= $selected_year ?>&division_id=<?= $division_id ?>" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; padding: 0; border-radius: var(--radius-sm); text-decoration: none; color: white;" title="Cetak Laporan">
                <span class="material-symbols-rounded" style="font-size: 1.25rem;">print</span>
            </a>
            <a href="../export_excel.php?bulan=<?= $selected_month ?>&tahun=<?= $selected_year ?>&division_id=<?= $division_id ?>" class="btn btn-sm" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; padding: 0; border-radius: var(--radius-sm); text-decoration: none; color: white; background-color: var(--success);" title="Ekspor Excel">
                <span class="material-symbols-rounded" style="font-size: 1.25rem;">grid_on</span>
            </a>
        </div>
        <div style="margin-left: auto; font-size: 0.85rem; color: var(--text-secondary);">
            Periode Aktif: <strong style="color: var(--accent);"><?= $bulan_names[$selected_month] ?> <?= $selected_year ?></strong>
        </div>
    </form>
</div>

<!-- Search area input for premium feel -->
<div class="card-section" style="padding: 1.25rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; justify-content: space-between; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.5rem 1rem; width: 100%; max-width: 400px;">
        <span class="material-symbols-rounded" style="color: var(--text-secondary); font-size: 1.25rem;">search</span>
        <input type="text" id="areaSearch" placeholder="Cari nama area atau username..." style="background: transparent; border: none; color: var(--text-primary); outline: none; width: 100%; font-size: 0.9rem;">
    </div>
    <div style="font-size: 0.85rem; color: var(--text-secondary);">
        Total Area: <strong style="color: var(--accent); font-size: 1rem;"><?= count($area_members_list) ?></strong> Area Terdaftar
    </div>
</div>

<!-- Area Table List -->
<div class="card-section">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nama Area</th>
                    <th>Username Akun</th>
                    <th style="text-align: center;">Total Temuan</th>
                    <th style="color: var(--warning); text-align: center;">Pending</th>
                    <th style="color: var(--info); text-align: center;">Proses</th>
                    <th style="color: var(--success); text-align: center;">Selesai (Done)</th>
                    <th style="text-align: center;">Penyelesaian</th>
                    <th style="text-align: center;">Nilai 5R</th>
                    <th style="text-align: center;">Grade</th>
                </tr>
            </thead>
            <tbody id="areaTableBody">
                <?php if (empty($area_members_list)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-secondary); padding: 2.5rem;">Tidak ada data area di divisi ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($area_members_list as $member): 
                        $pct = $member['total_findings'] > 0 ? round(($member['done_findings'] / $member['total_findings']) * 100) : 100;
                        $color_pct = 'var(--text-secondary)';
                        if ($member['total_findings'] > 0) {
                            if ($pct == 100) $color_pct = 'var(--success)';
                            elseif ($pct >= 50) $color_pct = 'var(--info)';
                            else $color_pct = 'var(--danger)';
                        }
                    ?>
                        <tr class="area-row">
                            <td class="area-name" style="font-weight: 700;"><?= htmlspecialchars($member['area_name']) ?></td>
                            <td class="area-username">
                                <?php if ($member['username']): ?>
                                    <code style="background-color: rgba(255, 255, 255, 0.05); padding: 0.2rem 0.4rem; border-radius: var(--radius-sm); color: var(--accent); font-size: 0.85rem;"><?= htmlspecialchars($member['username']) ?></code>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 0.8rem; font-style: italic;">Belum Dibuat</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; font-weight: 600;"><?= $member['total_findings'] ?></td>
                            <td style="color: var(--warning); text-align: center; font-weight: 600;"><?= $member['pending_findings'] ?? 0 ?></td>
                            <td style="color: var(--info); text-align: center; font-weight: 600;"><?= $member['progress_findings'] ?? 0 ?></td>
                            <td style="color: var(--success); text-align: center; font-weight: 600;"><?= $member['done_findings'] ?? 0 ?></td>
                            <td style="font-weight: 800; color: <?= $color_pct ?>; text-align: center;"><?= $pct ?>%</td>
                            <td style="text-align: center; font-weight: 700; color: var(--accent);"><?= $member['nilai_5r'] !== null ? number_format($member['nilai_5r'], 2) : '-' ?></td>
                            <td style="text-align: center; font-weight: 800;">
                                <?php 
                                    $grade = get_letter_grade($member['nilai_5r']);
                                    $grade_color = 'var(--text-secondary)';
                                    if ($grade === 'A' || $grade === 'A-') $grade_color = 'var(--success)';
                                    elseif ($grade === 'B+' || $grade === 'B' || $grade === 'B-') $grade_color = 'var(--info)';
                                    elseif ($grade === 'C+' || $grade === 'C') $grade_color = 'var(--warning)';
                                    elseif ($grade === 'D' || $grade === 'E') $grade_color = 'var(--danger)';
                                ?>
                                <span style="color: <?= $grade_color ?>; background: rgba(255,255,255,0.02); padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid rgba(255,255,255,0.05);"><?= $grade ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('areaSearch');
    const tableRows = document.querySelectorAll('.area-row');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            
            tableRows.forEach(row => {
                const areaName = row.querySelector('.area-name').textContent.toLowerCase();
                const username = row.querySelector('.area-username').textContent.toLowerCase();
                
                if (areaName.includes(query) || username.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
