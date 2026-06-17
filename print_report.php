<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

$base_path = '';

// Ensure user is logged in
if (!isset($_SESSION['role'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

$user_role = $_SESSION['role'];
$user_div_id = $_SESSION['division_id'] ?? null;

// Get parameters
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('n'));
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));

$is_all_divisions = (isset($_GET['division_id']) && $_GET['division_id'] === 'all');
$division_id = $is_all_divisions ? 0 : (isset($_GET['division_id']) ? intval($_GET['division_id']) : 0);

// Enforce role-based permission
if ($user_role === 'division') {
    if ($is_all_divisions || $division_id !== intval($user_div_id)) {
        die("Akses ditolak. Anda hanya dapat mencetak laporan divisi Anda sendiri.");
    }
} elseif ($user_role !== 'admin') {
    die("Akses ditolak. Peran tidak valid.");
}

// Fetch Divisions to print
$divisions_to_print = [];
if ($is_all_divisions) {
    $stmt_divs = $pdo->query("SELECT * FROM divisions ORDER BY name ASC");
    $divisions_to_print = $stmt_divs->fetchAll();
} else {
    if ($division_id <= 0) {
        die("Parameter divisi tidak valid.");
    }
    $stmt_div = $pdo->prepare("SELECT * FROM divisions WHERE id = :id");
    $stmt_div->execute(['id' => $division_id]);
    $division = $stmt_div->fetch();
    if (!$division) {
        die("Divisi tidak ditemukan.");
    }
    $divisions_to_print = [$division];
}

// Month names list
$bulan_names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$bulan_abbrev = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
];

$month_year_text = $bulan_abbrev[$bulan] . '-' . substr($tahun, -2);

$report_title_name = 'Semua Divisi';
if (!$is_all_divisions && !empty($divisions_to_print)) {
    $parts = explode(' - ', $divisions_to_print[0]['name']);
    $report_title_name = $parts[0] ?? $divisions_to_print[0]['name'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan Audit 5R - <?= htmlspecialchars($report_title_name) ?> - <?= htmlspecialchars($bulan_names[$bulan]) ?> <?= $tahun ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --border-color: #334155;
            --text-main: #000000;
            --text-secondary: #475569;
            --light-bg: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            background: #ffffff;
            font-size: 11px;
            line-height: 1.4;
        }

        .no-print-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            margin: -20px -20px 20px -20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #3b82f6;
        }

        .btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn:hover {
            background-color: #2563eb;
        }

        .btn-secondary {
            background-color: #475569;
        }

        .btn-secondary:hover {
            background-color: #334155;
        }

        .header-section {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .header-title {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 5px 0;
        }

        .header-subtitle {
            font-size: 12px;
            color: var(--text-secondary);
            margin: 0;
            font-weight: 600;
        }

        .top-grids {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            align-items: start;
        }

        .left-table-container {
            flex: 1;
        }

        .right-table-container {
            width: 250px;
        }

        .table-title {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #000000;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #f1f5f9;
            font-weight: 700;
            text-transform: uppercase;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: 700;
        }

        .month-label {
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 5px;
            display: block;
        }

        .detail-section {
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .area-title {
            font-size: 12px;
            font-weight: 800;
            margin: 20px 0 8px 0;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 4px;
        }

        .findings-table th {
            background-color: #f8fafc;
        }

        .findings-table td {
            padding: 8px 10px;
        }

        .green-header {
            background-color: #22c55e !important;
            color: #ffffff;
        }

        .page-break {
            display: block;
            margin: 40px 0;
            border-top: 1px dashed #cbd5e1;
        }

        /* Hide elements on print */
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                size: portrait;
                margin: 1.5cm;
            }
            th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page-break {
                display: block;
                page-break-before: always;
                margin: 0;
                border-top: none;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar for Web View -->
    <div class="no-print-bar">
        <div>
            <strong style="font-size: 14px;">Laporan Audit 5R - Mode Cetak</strong>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Laporan
            </button>
            <button onclick="window.close()" class="btn btn-secondary">Tutup Tab</button>
        </div>
    </div>

    <?php 
    $first_div = true;
    foreach ($divisions_to_print as $division):
        $div_id = $division['id'];
        
        // Extract Divisi name and PIC
        $parts = explode(' - ', $division['name']);
        $display_div_name = $parts[0] ?? $division['name'];
        $pic_name = $parts[1] ?? 'Staf Divisi';

        // Fetch all areas and their scores for selected month/year
        $stmt_areas = $pdo->prepare("
            SELECT a.id, a.name, ae.nilai_5r 
            FROM areas a
            LEFT JOIN area_evaluations ae ON a.id = ae.area_id AND ae.bulan = :bulan AND ae.tahun = :tahun
            WHERE a.division_id = :div_id
            ORDER BY a.name ASC
        ");
        $stmt_areas->execute([
            'div_id' => $div_id,
            'bulan' => $bulan,
            'tahun' => $tahun
        ]);
        $areas = $stmt_areas->fetchAll();

        // Calculate averages
        $total_score = 0;
        $scored_count = 0;
        foreach ($areas as $area) {
            if ($area['nilai_5r'] !== null) {
                $total_score += floatval($area['nilai_5r']);
                $scored_count++;
            }
        }
        $average_score = $scored_count > 0 ? ($total_score / $scored_count) : null;
        $average_grade = $average_score !== null ? get_letter_grade($average_score) : '-';

        // Fetch monthly findings/notes recorded in findings table for this division, month, and year (only if not printing all divisions)
        $all_findings = [];
        $findings_by_area = [];
        if (!$is_all_divisions) {
            $stmt_findings = $pdo->prepare("
                SELECT id, area, description, finding_photo, improvement_photo, created_at 
                FROM findings 
                WHERE division_id = :div_id AND MONTH(created_at) = :bulan AND YEAR(created_at) = :tahun
                ORDER BY id ASC
            ");
            $stmt_findings->execute([
                'div_id' => $div_id,
                'bulan' => $bulan,
                'tahun' => $tahun
            ]);
            $all_findings = $stmt_findings->fetchAll();

            // Group findings by area name for display
            foreach ($all_findings as $finding) {
                $area_key = strtolower(trim($finding['area']));
                $findings_by_area[$area_key][] = [
                    'description' => $finding['description'],
                    'finding_photo' => $finding['finding_photo'],
                    'improvement_photo' => $finding['improvement_photo'],
                    'area' => $finding['area']
                ];
            }
        }

        if (!$first_div) {
            echo '<div class="page-break"></div>';
        }
        $first_div = false;
    ?>
        <!-- Month Label top-left -->
        <span class="month-label" style="margin-top: 15px;"><?= htmlspecialchars($month_year_text) ?></span>

        <div class="top-grids">
            <!-- Left Table: Audit Scores -->
            <div class="left-table-container">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" class="center" style="width: 30px;">No</th>
                            <th rowspan="2" style="width: 60px;">Bagian</th>
                            <th rowspan="2">Area</th>
                            <th colspan="2" class="center">NILAI 5R</th>
                            <th colspan="2" class="center">Nilai Rata-Rata</th>
                            <th rowspan="2" style="width: 80px;">PIC</th>
                        </tr>
                        <tr>
                            <th class="center" style="width: 50px;">Angka</th>
                            <th class="center" style="width: 50px;">Huruf</th>
                            <th class="center" style="width: 60px;">Rata-Rata</th>
                            <th class="center" style="width: 50px;">Huruf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_areas = count($areas);
                        $first = true;
                        $no = 1;
                        foreach ($areas as $area):
                            $grade = get_letter_grade($area['nilai_5r']);
                        ?>
                            <tr>
                                <td class="center"><?= $no++ ?></td>
                                
                                <!-- Merged Division/Bagian column -->
                                <?php if ($first): ?>
                                    <td rowspan="<?= $total_areas ?>" class="bold"><?= htmlspecialchars($display_div_name) ?></td>
                                <?php endif; ?>
                                
                                <td><?= htmlspecialchars($area['name']) ?></td>
                                <td class="center bold"><?= $area['nilai_5r'] !== null ? number_format($area['nilai_5r'], 2) : '-' ?></td>
                                <td class="center bold"><?= $grade ?></td>
                                
                                <!-- Merged Average & PIC columns -->
                                <?php if ($first): ?>
                                    <td rowspan="<?= $total_areas ?>" class="center bold" style="font-size: 12px;"><?= $average_score !== null ? number_format($average_score, 2) : '-' ?></td>
                                    <td rowspan="<?= $total_areas ?>" class="center bold" style="font-size: 12px;"><?= $average_grade ?></td>
                                    <td rowspan="<?= $total_areas ?>" class="bold"><?= htmlspecialchars($pic_name) ?></td>
                                    <?php $first = false; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Right Table: Grading Legend -->
            <div class="right-table-container">
                <div class="table-title">Akan dilakukan proses evaluasi berdasarkan penilaian setiap bulan</div>
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" class="center" style="width: 30px;">No</th>
                            <th colspan="2" class="center">Nilai</th>
                        </tr>
                        <tr>
                            <th class="center">Presentasi</th>
                            <th class="center">Huruf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="center">1</td><td class="center">4.00</td><td class="center bold">A</td></tr>
                        <tr><td class="center">2</td><td class="center">3.70</td><td class="center bold">A-</td></tr>
                        <tr><td class="center">3</td><td class="center">3.30</td><td class="center bold">B+</td></tr>
                        <tr><td class="center">4</td><td class="center">3.00</td><td class="center bold">B</td></tr>
                        <tr><td class="center">5</td><td class="center">2.70</td><td class="center bold">B-</td></tr>
                        <tr><td class="center">6</td><td class="center">2.30</td><td class="center bold">C+</td></tr>
                        <tr><td class="center">7</td><td class="center">2.00</td><td class="center bold">C</td></tr>
                        <tr><td class="center">8</td><td class="center">1.00</td><td class="center bold">D</td></tr>
                        <tr><td class="center">9</td><td class="center">0.00</td><td class="center bold">E</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom Section: Findings/Notes Details Per Area -->
        <?php if (!empty($all_findings)): ?>
            <div style="margin-top: 30px;">
                <h2 style="font-size: 14px; font-weight: 800; border-bottom: 2px solid #000000; padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase;">Detail Catatan Temuan Per Area (<?= htmlspecialchars($display_div_name) ?>)</h2>
                
                <?php 
                $i = 1;
                $printed_area_keys = [];
                foreach ($areas as $area):
                    $area_key = strtolower(trim($area['name']));
                    $printed_area_keys[$area_key] = true;
                    $area_findings = $findings_by_area[$area_key] ?? [];
                    if (empty($area_findings)) {
                        continue;
                    }
                ?>
                    <div class="detail-section" style="page-break-inside: avoid;">
                        <div class="area-title"><?= $i++ ?>. <?= htmlspecialchars($area['name']) ?></div>
                        <table class="findings-table">
                            <thead>
                                <tr>
                                    <th class="green-header center" style="width: 40px;">No.</th>
                                    <th class="green-header">Catatan Temuan (<?= htmlspecialchars($bulan_names[$bulan]) ?> <?= $tahun ?>)</th>
                                    <th class="green-header center" style="width: 110px;">Foto Sebelum</th>
                                    <th class="green-header center" style="width: 110px;">Foto Sesudah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $f_no = 1; foreach ($area_findings as $item): ?>
                                    <tr>
                                        <td class="center"><?= $f_no++ ?>.</td>
                                        <td><?= htmlspecialchars($item['description']) ?></td>
                                        <td class="center">
                                            <?php if ($item['finding_photo']): ?>
                                                <img src="<?= $base_path ?>uploads/<?= htmlspecialchars($item['finding_photo']) ?>" style="width: 90px; height: 90px; object-fit: cover; border: 1px solid #000000; border-radius: 4px; display: block; margin: 0 auto;" alt="Sebelum">
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="center">
                                            <?php if ($item['improvement_photo']): ?>
                                                <img src="<?= $base_path ?>uploads/<?= htmlspecialchars($item['improvement_photo']) ?>" style="width: 90px; height: 90px; object-fit: cover; border: 1px solid #000000; border-radius: 4px; display: block; margin: 0 auto;" alt="Sesudah">
                                            <?php else: ?>
                                                <span style="color: var(--text-secondary); font-style: italic; font-size: 9px;">Belum Ada</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

                <?php 
                // Print remaining unmatched custom areas
                foreach ($findings_by_area as $area_key => $area_findings):
                    if (isset($printed_area_keys[$area_key])) {
                        continue;
                    }
                    $area_name = $area_findings[0]['area'] ?? ucwords($area_key);
                ?>
                    <div class="detail-section" style="page-break-inside: avoid;">
                        <div class="area-title"><?= $i++ ?>. <?= htmlspecialchars($area_name) ?> <span style="font-size: 10px; color: var(--text-secondary); font-weight: normal;">(Area Kustom)</span></div>
                        <table class="findings-table">
                            <thead>
                                <tr>
                                    <th class="green-header center" style="width: 40px;">No.</th>
                                    <th class="green-header">Catatan Temuan (<?= htmlspecialchars($bulan_names[$bulan]) ?> <?= $tahun ?>)</th>
                                    <th class="green-header center" style="width: 110px;">Foto Sebelum</th>
                                    <th class="green-header center" style="width: 110px;">Foto Sesudah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $f_no = 1; foreach ($area_findings as $item): ?>
                                    <tr>
                                        <td class="center"><?= $f_no++ ?>.</td>
                                        <td><?= htmlspecialchars($item['description']) ?></td>
                                        <td class="center">
                                            <?php if ($item['finding_photo']): ?>
                                                <img src="<?= $base_path ?>uploads/<?= htmlspecialchars($item['finding_photo']) ?>" style="width: 90px; height: 90px; object-fit: cover; border: 1px solid #000000; border-radius: 4px; display: block; margin: 0 auto;" alt="Sebelum">
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="center">
                                            <?php if ($item['improvement_photo']): ?>
                                                <img src="<?= $base_path ?>uploads/<?= htmlspecialchars($item['improvement_photo']) ?>" style="width: 90px; height: 90px; object-fit: cover; border: 1px solid #000000; border-radius: 4px; display: block; margin: 0 auto;" alt="Sesudah">
                                            <?php else: ?>
                                                <span style="color: var(--text-secondary); font-style: italic; font-size: 9px;">Belum Ada</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

</body>
</html>
