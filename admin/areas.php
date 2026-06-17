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

$selected_month = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('n'));
$selected_year = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));
$selected_div = isset($_GET['filter_div']) ? intval($_GET['filter_div']) : 0;

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = intval(date('n'));
}
if ($selected_year < 2025 || $selected_year > 2030) {
    $selected_year = intval(date('Y'));
}

// Handle edit score / details action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $nilai_input = $_POST['nilai_5r'];
        $nilai_5r = ($nilai_input !== '' && $nilai_input !== null) ? floatval($nilai_input) : null;
        
        $post_month = isset($_POST['bulan']) ? intval($_POST['bulan']) : $selected_month;
        $post_year = isset($_POST['tahun']) ? intval($_POST['tahun']) : $selected_year;
        $post_div = isset($_POST['filter_div']) ? intval($_POST['filter_div']) : $selected_div;
        
        if ($id > 0 && !empty($name)) {
            // Validate score range if provided
            if ($nilai_5r !== null && ($nilai_5r < 0 || $nilai_5r > 4.0)) {
                $error = 'Nilai 5R harus berada dalam rentang 0.00 hingga 4.00.';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Update area name
                    $stmt_name = $pdo->prepare("UPDATE areas SET name = :name WHERE id = :id");
                    $stmt_name->execute(['name' => $name, 'id' => $id]);
                    
                    // Save or update score
                    if ($nilai_5r !== null) {
                        $stmt_eval = $pdo->prepare("
                            INSERT INTO area_evaluations (area_id, bulan, tahun, nilai_5r) 
                            VALUES (:area_id, :bulan, :tahun, :nilai_5r)
                            ON DUPLICATE KEY UPDATE nilai_5r = :nilai_5r2
                        ");
                        $stmt_eval->execute([
                            'area_id' => $id,
                            'bulan' => $post_month,
                            'tahun' => $post_year,
                            'nilai_5r' => $nilai_5r,
                            'nilai_5r2' => $nilai_5r
                        ]);
                    } else {
                        // Delete if empty value submitted
                        $stmt_del = $pdo->prepare("
                            DELETE FROM area_evaluations 
                            WHERE area_id = :area_id AND bulan = :bulan AND tahun = :tahun
                        ");
                        $stmt_del->execute([
                            'area_id' => $id,
                            'bulan' => $post_month,
                            'tahun' => $post_year
                        ]);
                    }
                    
                    $pdo->commit();
                    header("Location: areas.php?bulan=$post_month&tahun=$post_year&filter_div=$post_div&success=1");
                    exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Gagal memperbarui data: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'ID atau nama area tidak valid.';
        }
    }
}

if (isset($_GET['success'])) {
    $success = 'Data area dan Nilai 5R berhasil diperbarui.';
}

// Fetch all divisions for filter dropdown
$divisions = $pdo->query("SELECT * FROM divisions ORDER BY name ASC")->fetchAll();

// Fetch edit target
$edit_area = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
        $stmt = $pdo->prepare("
            SELECT a.id, a.name, d.name AS division_name, ae.nilai_5r 
            FROM areas a 
            JOIN divisions d ON a.division_id = d.id 
            LEFT JOIN area_evaluations ae ON a.id = ae.area_id AND ae.bulan = :bulan AND ae.tahun = :tahun
            WHERE a.id = :id 
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $edit_id,
            'bulan' => $selected_month,
            'tahun' => $selected_year
        ]);
        $edit_area = $stmt->fetch();
    }
}

// Fetch filtered areas with division name and score for selected month/year
$div_where = $selected_div > 0 ? " WHERE a.division_id = :div_id" : "";
$stmt_areas = $pdo->prepare("
    SELECT a.id, a.name, d.name AS division_name, ae.nilai_5r 
    FROM areas a
    JOIN divisions d ON a.division_id = d.id
    LEFT JOIN area_evaluations ae ON a.id = ae.area_id AND ae.bulan = :bulan AND ae.tahun = :tahun
    $div_where
    ORDER BY d.name ASC, a.name ASC
");

$params = [
    'bulan' => $selected_month,
    'tahun' => $selected_year
];
if ($selected_div > 0) {
    $params['div_id'] = $selected_div;
}

$stmt_areas->execute($params);
$areas = $stmt_areas->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Kelola Area & Nilai Audit 5R</h1>
        <p class="page-subtitle">Atur Nilai 5R bulanan untuk setiap area kerja. Grade akan terhitung otomatis.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Month, Year & Division Filter Selector -->
<div class="card-section" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="areas.php" style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; margin-bottom: 0;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="bulan" style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">Bulan:</label>
            <select name="bulan" id="bulan" class="form-control" style="width: 140px; padding: 0.45rem 0.75rem;" onchange="this.form.submit()">
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
            <select name="tahun" id="tahun" class="form-control" style="width: 100px; padding: 0.45rem 0.75rem;" onchange="this.form.submit()">
                <?php
                for ($y = 2025; $y <= 2030; $y++) {
                    $selected = ($y == $selected_year) ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="filter_div" style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">Filter Divisi:</label>
            <select name="filter_div" id="filter_div" class="form-control" style="width: 200px; padding: 0.45rem 0.75rem;" onchange="this.form.submit()">
                <option value="0">Semua Divisi</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($selected_div == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-left: 1.5rem;">
            <a href="../print_report.php?bulan=<?= $selected_month ?>&tahun=<?= $selected_year ?>&division_id=<?= $selected_div > 0 ? $selected_div : 'all' ?>" target="_blank" class="btn btn-secondary" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none; border-radius: 4px; font-weight: 600; color: white;">
                <span class="material-symbols-rounded" style="font-size: 1.15rem;">print</span>
                <?= $selected_div > 0 ? 'Cetak Laporan' : 'Cetak Laporan Semua Divisi' ?>
            </a>
        </div>
        <div style="margin-left: auto; font-size: 0.85rem; color: var(--text-secondary);">
            Periode: <strong style="color: var(--accent);"><?= $bulan_names[$selected_month] ?> <?= $selected_year ?></strong>
        </div>
    </form>
</div>

<div class="layout-grid">
    <!-- List of Areas -->
    <div class="card-section">
        <div class="card-section-header" style="flex-wrap: wrap; gap: 1rem;">
            <h2 class="card-section-title" style="min-width: 150px;">Daftar Area Kerja</h2>
            <!-- Search bar -->
            <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.35rem 0.75rem; width: 100%; max-width: 250px;">
                <span class="material-symbols-rounded" style="color: var(--text-secondary); font-size: 1.1rem;">search</span>
                <input type="text" id="adminAreaSearch" placeholder="Cari area atau divisi..." style="background: transparent; border: none; color: var(--text-primary); outline: none; width: 100%; font-size: 0.85rem;">
            </div>
        </div>
        
        <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nama Area</th>
                        <th>Divisi</th>
                        <th style="text-align: center; width: 90px;">Nilai 5R</th>
                        <th style="text-align: center; width: 85px;">Grade</th>
                        <th style="width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="adminAreaTableBody">
                    <?php if (empty($areas)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 2rem;">Belum ada area kerja terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($areas as $area): 
                            $grade = get_letter_grade($area['nilai_5r']);
                            $grade_color = 'var(--text-secondary)';
                            if ($grade === 'A' || $grade === 'A-') $grade_color = 'var(--success)';
                            elseif ($grade === 'B+' || $grade === 'B' || $grade === 'B-') $grade_color = 'var(--info)';
                            elseif ($grade === 'C+' || $grade === 'C') $grade_color = 'var(--warning)';
                            elseif ($grade === 'D' || $grade === 'E') $grade_color = 'var(--danger)';
                        ?>
                            <tr class="admin-area-row">
                                <td class="area-name" style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($area['name']) ?></td>
                                <td class="area-division" style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($area['division_name']) ?></td>
                                <td style="text-align: center; font-weight: 700; color: var(--accent);"><?= $area['nilai_5r'] !== null ? number_format($area['nilai_5r'], 2) : '-' ?></td>
                                <td style="text-align: center; font-weight: 800; color: <?= $grade_color ?>;"><?= $grade ?></td>
                                <td>
                                    <div class="action-links" style="justify-content: center;">
                                        <a href="?edit=<?= $area['id'] ?>&bulan=<?= $selected_month ?>&tahun=<?= $selected_year ?>&filter_div=<?= $selected_div ?>" class="action-link edit">Edit Nilai</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Score Form -->
    <div class="card-section" style="align-self: start;">
        <div class="card-section-header">
            <h2 class="card-section-title"><?= $edit_area ? 'Edit Area & Nilai' : 'Pilih Area Untuk Mengedit Nilai' ?></h2>
        </div>
        <div style="padding: 1.5rem;">
            <?php if ($edit_area): ?>
                <form action="areas.php" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $edit_area['id'] ?>">
                    <input type="hidden" name="bulan" value="<?= $selected_month ?>">
                    <input type="hidden" name="tahun" value="<?= $selected_year ?>">
                    <input type="hidden" name="filter_div" value="<?= $selected_div ?>">
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" style="color: var(--text-secondary);">Divisi Terkait</label>
                        <input type="text" class="form-control" style="background-color: var(--bg-main); opacity: 0.8; font-weight: 600;" value="<?= htmlspecialchars($edit_area['division_name']) ?>" disabled>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" style="color: var(--text-secondary);">Periode Penilaian</label>
                        <input type="text" class="form-control" style="background-color: var(--bg-main); opacity: 0.8; font-weight: 600; color: var(--accent);" value="<?= $bulan_names[$selected_month] ?> <?= $selected_year ?>" disabled>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="name" class="form-label">Nama Area *</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($edit_area['name']) ?>" required placeholder="Contoh: Kantor lantai 1">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.75rem;">
                        <label for="nilai_5r" class="form-label">Nilai 5R (Skala 0.00 - 4.00)</label>
                        <input type="number" step="0.01" min="0" max="4" name="nilai_5r" id="nilai_5r" class="form-control" value="<?= $edit_area['nilai_5r'] !== null ? number_format($edit_area['nilai_5r'], 2) : '' ?>" placeholder="Contoh: 3.20">
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan Perubahan</button>
                        <a href="areas.php?bulan=<?= $selected_month ?>&tahun=<?= $selected_year ?>&filter_div=<?= $selected_div ?>" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center;">Batal</a>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-secondary); padding: 3rem 1rem;">
                    <span class="material-symbols-rounded" style="font-size: 3rem; color: var(--border-color); margin-bottom: 1rem; display: block;">edit_note</span>
                    Silakan klik tombol <strong>"Edit Nilai"</strong> pada salah satu area kerja di daftar sebelah kiri untuk memperbarui Nilai 5R bulanan area tersebut.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('adminAreaSearch');
    const tableRows = document.querySelectorAll('.admin-area-row');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            
            tableRows.forEach(row => {
                const areaName = row.querySelector('.area-name').textContent.toLowerCase();
                const division = row.querySelector('.area-division').textContent.toLowerCase();
                
                if (areaName.includes(query) || division.includes(query)) {
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
