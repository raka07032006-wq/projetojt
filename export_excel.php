<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

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
        die("Akses ditolak. Anda hanya dapat mengekspor laporan divisi Anda sendiri.");
    }
} elseif ($user_role !== 'admin') {
    die("Akses ditolak. Peran tidak valid.");
}

// Fetch Divisions to export
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

$report_title_name = 'Semua_Divisi';
if (!$is_all_divisions && !empty($divisions_to_print)) {
    $parts = explode(' - ', $divisions_to_print[0]['name']);
    $report_title_name = $parts[0] ?? $divisions_to_print[0]['name'];
}
$report_title_name = str_replace(' ', '_', $report_title_name);

// Determine absolute base URL for Excel to download images over HTTP
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_url = $protocol . $domainName . rtrim($script_dir, '/') . '/';

// Set excel headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Laporan_Audit_5R_" . urlencode($report_title_name) . "_" . $month_year_text . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Laporan Audit 5R</x:Name>
    <x:WorksheetOptions>
     <x:DisplayGridlines/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
    body {
        font-family: Arial, sans-serif;
    }
    table {
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #000000;
        padding: 6px;
        font-size: 10pt;
    }
    th {
        font-weight: bold;
    }
    .center {
        text-align: center;
    }
    .bold {
        font-weight: bold;
    }
    .number-cell {
        mso-number-format: "0.00";
        text-align: right;
    }
</style>
</head>
<body>

<?php 
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

    // Fetch monthly findings/notes recorded in findings table for this division, month, and year
    $stmt_findings = $pdo->prepare("
        SELECT id, area, description, status, created_at 
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

    // Batch fetch finding images
    $finding_ids = array_column($all_findings, 'id');
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

    // Group findings by area name for display
    $findings_by_area = [];
    foreach ($all_findings as $finding) {
        $area_key = strtolower(trim($finding['area']));
        $findings_by_area[$area_key][] = [
            'id' => $finding['id'],
            'description' => $finding['description'],
            'area' => $finding['area']
        ];
    }
?>

    <h2 style="margin: 0; padding: 0;"><?= htmlspecialchars(strtoupper($display_div_name)) ?></h2>
    <table border="0" style="border: none; margin-bottom: 10px;">
        <tr style="border: none;"><td style="border: none; font-weight: bold; width: 120px;">Periode:</td><td style="border: none;"><?= $bulan_names[$bulan] ?> <?= $tahun ?></td></tr>
        <tr style="border: none;"><td style="border: none; font-weight: bold;">PIC:</td><td style="border: none;"><?= htmlspecialchars($pic_name) ?></td></tr>
    </table>

    <!-- Side-by-Side Outer Layout Table -->
    <table border="0" style="border: none; margin-bottom: 20px;">
        <tr style="border: none; vertical-align: top;">
            <!-- Left Side: Scores Table -->
            <td style="border: none; padding: 0;">
                <table border="1">
                    <thead>
                        <tr>
                            <th rowspan="2" class="center" style="background-color: #f1f5f9; width: 40px;">No</th>
                            <th rowspan="2" style="background-color: #f1f5f9; width: 150px;">Bagian</th>
                            <th rowspan="2" style="background-color: #f1f5f9; width: 200px;">Area Kerja</th>
                            <th colspan="2" class="center" style="background-color: #f1f5f9;">Nilai 5R</th>
                            <th colspan="2" class="center" style="background-color: #f1f5f9;">Rata-Rata Divisi</th>
                            <th rowspan="2" style="background-color: #f1f5f9; width: 150px;">PIC</th>
                        </tr>
                        <tr>
                            <th class="center" style="background-color: #f1f5f9; width: 80px;">Angka</th>
                            <th class="center" style="background-color: #f1f5f9; width: 60px;">Huruf</th>
                            <th class="center" style="background-color: #f1f5f9; width: 80px;">Angka</th>
                            <th class="center" style="background-color: #f1f5f9; width: 60px;">Huruf</th>
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
                                <td align="center" valign="middle"><?= $no++ ?></td>
                                <?php if ($first): ?>
                                    <td rowspan="<?= $total_areas ?>" class="bold" style="vertical-align: middle;"><?= htmlspecialchars($display_div_name) ?></td>
                                <?php endif; ?>
                                <td valign="middle"><?= htmlspecialchars($area['name']) ?></td>
                                <td class="number-cell" style="font-weight: bold; vertical-align: middle;"><?= $area['nilai_5r'] !== null ? number_format($area['nilai_5r'], 2) : '-' ?></td>
                                <td align="center" valign="middle" class="bold"><?= $grade ?></td>
                                <?php if ($first): ?>
                                    <td rowspan="<?= $total_areas ?>" class="number-cell" style="font-weight: bold; vertical-align: middle;"><?= $average_score !== null ? number_format($average_score, 2) : '-' ?></td>
                                    <td rowspan="<?= $total_areas ?>" align="center" valign="middle" class="bold"><?= $average_grade ?></td>
                                    <td rowspan="<?= $total_areas ?>" class="bold" style="vertical-align: middle;"><?= htmlspecialchars($pic_name) ?></td>
                                    <?php $first = false; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </td>
            <!-- Gap Column -->
            <td style="border: none; width: 30px;"></td>
            <!-- Right Side: Legend Table -->
            <td style="border: none; padding: 0; width: 250px;">
                <div style="font-size: 9pt; font-weight: bold; margin-bottom: 8px;">AKAN DILAKUKAN PROSES EVALUASI BERDASARKAN PENILAIAN SETIAP BULAN</div>
                <table border="1">
                    <thead>
                        <tr>
                            <th rowspan="2" class="center" style="background-color: #f1f5f9; width: 40px;">No</th>
                            <th colspan="2" class="center" style="background-color: #f1f5f9;">Nilai</th>
                        </tr>
                        <tr>
                            <th class="center" style="background-color: #f1f5f9; width: 100px;">Presentasi</th>
                            <th class="center" style="background-color: #f1f5f9; width: 60px;">Huruf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td align="center" valign="middle">1</td><td align="center" valign="middle">4.00</td><td align="center" valign="middle" class="bold">A</td></tr>
                        <tr><td align="center" valign="middle">2</td><td align="center" valign="middle">3.70</td><td align="center" valign="middle" class="bold">A-</td></tr>
                        <tr><td align="center" valign="middle">3</td><td align="center" valign="middle">3.30</td><td align="center" valign="middle" class="bold">B+</td></tr>
                        <tr><td align="center" valign="middle">4</td><td align="center" valign="middle">3.00</td><td align="center" valign="middle" class="bold">B</td></tr>
                        <tr><td align="center" valign="middle">5</td><td align="center" valign="middle">2.70</td><td align="center" valign="middle" class="bold">B-</td></tr>
                        <tr><td align="center" valign="middle">6</td><td align="center" valign="middle">2.30</td><td align="center" valign="middle" class="bold">C+</td></tr>
                        <tr><td align="center" valign="middle">7</td><td align="center" valign="middle">2.00</td><td align="center" valign="middle" class="bold">C</td></tr>
                        <tr><td align="center" valign="middle">8</td><td align="center" valign="middle">1.00</td><td align="center" valign="middle" class="bold">D</td></tr>
                        <tr><td align="center" valign="middle">9</td><td align="center" valign="middle">0.00</td><td align="center" valign="middle" class="bold">E</td></tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Bottom Section: Findings/Notes Details Per Area -->
    <?php if (!empty($all_findings)): ?>
        <br>
        <h3 style="margin: 0; padding: 0; text-transform: uppercase;">DETAIL CATATAN TEMUAN PER AREA (<?= htmlspecialchars($display_div_name) ?>)</h3>
        <br>

        <table border="1">
            <thead>
                <tr>
                    <th style="background-color: #22c55e; color: #ffffff; font-weight: bold; text-align: center; width: 50px;">No.</th>
                    <th style="background-color: #22c55e; color: #ffffff; font-weight: bold; width: 350px;">Catatan Temuan (<?= htmlspecialchars($bulan_names[$bulan]) ?> <?= $tahun ?>)</th>
                    <th style="background-color: #22c55e; color: #ffffff; font-weight: bold; text-align: center; width: 180px;">Foto Sebelum</th>
                    <th style="background-color: #22c55e; color: #ffffff; font-weight: bold; text-align: center; width: 180px;">Foto Sesudah</th>
                </tr>
            </thead>
            <tbody>
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
                    <!-- Area Group Header Row -->
                    <tr>
                        <td colspan="4" style="background-color: #e2e8f0; font-weight: bold; font-size: 11pt; padding: 8px; text-align: left;" valign="middle">
                            <?= $i++ ?>. <?= htmlspecialchars($area['name']) ?>
                        </td>
                    </tr>

                    <?php $f_no = 1; foreach ($area_findings as $item): 
                        $bef_imgs = $finding_images[$item['id']]['before'] ?? [];
                        $aft_imgs = $finding_images[$item['id']]['after'] ?? [];
                        $has_image = (!empty($bef_imgs) || !empty($aft_imgs));
                        $row_height_attr = $has_image ? ' height="110"' : '';
                    ?>
                        <tr<?= $row_height_attr ?>>
                            <td align="center" valign="middle"><?= $f_no++ ?>.</td>
                            <td valign="middle"><?= htmlspecialchars($item['description']) ?></td>
                            <td align="center" valign="middle">
                                <?php if (!empty($bef_imgs)): 
                                    foreach ($bef_imgs as $img_info):
                                ?>
                                    <img src="<?= $base_url ?>view_image.php?id=<?= $img_info['id'] ?>" width="80" height="80" style="width: 80px; height: 80px; object-fit: cover;" alt="Sebelum">
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td align="center" valign="middle">
                                <?php if (!empty($aft_imgs)): 
                                    foreach ($aft_imgs as $img_info):
                                ?>
                                    <img src="<?= $base_url ?>view_image.php?id=<?= $img_info['id'] ?>" width="80" height="80" style="width: 80px; height: 80px; object-fit: cover;" alt="Sesudah">
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <span style="color: #64748b; font-style: italic; font-size: 9pt;">Belum Ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <?php 
                // Print remaining unmatched custom areas
                foreach ($findings_by_area as $area_key => $area_findings):
                    if (isset($printed_area_keys[$area_key])) {
                        continue;
                    }
                    $area_name = $area_findings[0]['area'] ?? ucwords($area_key);
                ?>
                    <!-- Custom Area Group Header Row -->
                    <tr>
                        <td colspan="4" style="background-color: #e2e8f0; font-weight: bold; font-size: 11pt; padding: 8px; text-align: left;" valign="middle">
                            <?= $i++ ?>. <?= htmlspecialchars($area_name) ?> <span style="font-size: 9pt; color: #64748b; font-weight: normal;">(Area Kustom)</span>
                        </td>
                    </tr>

                    <?php $f_no = 1; foreach ($area_findings as $item): 
                        $bef_imgs = $finding_images[$item['id']]['before'] ?? [];
                        $aft_imgs = $finding_images[$item['id']]['after'] ?? [];
                        $has_image = (!empty($bef_imgs) || !empty($aft_imgs));
                        $row_height_attr = $has_image ? ' height="110"' : '';
                    ?>
                        <tr<?= $row_height_attr ?>>
                            <td align="center" valign="middle"><?= $f_no++ ?>.</td>
                            <td valign="middle"><?= htmlspecialchars($item['description']) ?></td>
                            <td align="center" valign="middle">
                                <?php if (!empty($bef_imgs)): 
                                    foreach ($bef_imgs as $img_info):
                                ?>
                                    <img src="<?= $base_url ?>view_image.php?id=<?= $img_info['id'] ?>" width="80" height="80" style="width: 80px; height: 80px; object-fit: cover;" alt="Sebelum">
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td align="center" valign="middle">
                                <?php if (!empty($aft_imgs)): 
                                    foreach ($aft_imgs as $img_info):
                                ?>
                                    <img src="<?= $base_url ?>view_image.php?id=<?= $img_info['id'] ?>" width="80" height="80" style="width: 80px; height: 80px; object-fit: cover;" alt="Sesudah">
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <span style="color: #64748b; font-style: italic; font-size: 9pt;">Belum Ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <br><hr style="border: none; border-top: 1px dashed #cccccc; margin: 20px 0;"><br>

<?php endforeach; ?>

</body>
</html>
