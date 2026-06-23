<?php
require_once __DIR__ . '/../config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$composite_file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/media__1782215445774.png';
if (!file_exists($composite_file)) {
    die("Error: Composite image not found at $composite_file\n");
}

$img = imagecreatefrompng($composite_file);
if (!$img) {
    die("Error: Failed to load composite image\n");
}

$findings_data = [
    1 => [
        'description' => 'Sunblast kaca ruang direksi agar dirapihkan dan dibersihkan',
        'slices' => [0, 1]
    ],
    2 => [
        'description' => 'Pembersihan debu pada kaca jendela area luar ruang direksi',
        'slices' => [2]
    ],
    3 => [
        'description' => 'Cermin di depan toilet bernoda hitam, perlu pembersihan',
        'slices' => [3]
    ],
    4 => [
        'description' => 'Dinding ruang ATK & RTK perlu di cat ulang',
        'slices' => [4, 5, 6]
    ],
    5 => [
        'description' => 'Dinding toilet pria perlu di bersihkan',
        'slices' => [7]
    ],
    6 => [
        'description' => 'Perapihan barang-barang area C11',
        'slices' => [8, 9, 10, 11]
    ],
    7 => [
        'description' => 'Perapihan dan pelabelan laci penyimpanan barang - barang depan meja pak abdi',
        'slices' => [12]
    ],
    8 => [
        'description' => 'Pembersihan jendela ruang RTK',
        'slices' => [13]
    ],
    9 => [
        'description' => 'Pembersihan lantai di bawah wastafel (sekat)',
        'slices' => [14]
    ],
    10 => [
        'description' => 'Pembersihan bekas tempelan doble type pada dinding',
        'slices' => [15]
    ],
    11 => [
        'description' => 'Pembersihan kaca bagian luar',
        'slices' => [16]
    ],
    12 => [
        'description' => 'Balkon aula berkarat, perlu di cat ulang',
        'slices' => [17, 18]
    ],
    13 => [
        'description' => 'Plafon kanopi depan aula berjamur, perlu di cat ulang',
        'slices' => [19]
    ],
    14 => [
        'description' => 'Tiang luar aula perlu perbaikan',
        'slices' => [20]
    ],
    15 => [
        'description' => 'Garis bekas line lapangan badminton perlu dibersihkan',
        'slices' => [21]
    ],
    16 => [
        'description' => 'Pembersihan sawang aula',
        'slices' => [22]
    ],
    17 => [
        'description' => 'Perapihan kabel depan meja pak Abdi',
        'slices' => [23, 24]
    ],
    18 => [
        'description' => 'Perapihan barang-barang yang melewati garis marking ( C13 )',
        'slices' => [25]
    ],
    19 => [
        'description' => 'Perapihan kertas yang tercecer pada rak penyimpanan ( C13 )',
        'slices' => [26]
    ],
    20 => [
        'description' => 'Pembersihan bekas lakban marking area ( C13 )',
        'slices' => [27]
    ],
    21 => [
        'description' => 'Perapihan area meja ( C12 )',
        'slices' => [28]
    ],
    22 => [
        'description' => 'Standar 5R masih dalam revisi layout',
        'slices' => [29]
    ],
    23 => [
        'description' => 'Dinding ruang aula retak perlu di cat ulang',
        'slices' => [30]
    ],
    24 => [
        'description' => 'Pintu samping aula perlu diberikan label tarik dorong',
        'slices' => [31]
    ],
    25 => [
        'description' => 'Dinding tangga kotor, perlu di cat ulang',
        'slices' => [32]
    ]
];

$slices_coords = [
    // Row 0
    0 => ['y1' => 30, 'y2' => 168, 'x1' => 43, 'x2' => 141],
    1 => ['y1' => 30, 'y2' => 168, 'x1' => 145, 'x2' => 245],
    2 => ['y1' => 30, 'y2' => 168, 'x1' => 248, 'x2' => 347],
    3 => ['y1' => 30, 'y2' => 168, 'x1' => 350, 'x2' => 475],
    4 => ['y1' => 30, 'y2' => 168, 'x1' => 478, 'x2' => 578],
    5 => ['y1' => 30, 'y2' => 168, 'x1' => 581, 'x2' => 680],
    6 => ['y1' => 30, 'y2' => 168, 'x1' => 684, 'x2' => 784],
    7 => ['y1' => 30, 'y2' => 168, 'x1' => 785, 'x2' => 884],

    // Row 1
    8 => ['y1' => 173, 'y2' => 264, 'x1' => 43, 'x2' => 194],
    9 => ['y1' => 173, 'y2' => 264, 'x1' => 199, 'x2' => 311],
    10 => ['y1' => 173, 'y2' => 264, 'x1' => 312, 'x2' => 423],
    11 => ['y1' => 173, 'y2' => 264, 'x1' => 426, 'x2' => 553],
    12 => ['y1' => 173, 'y2' => 264, 'x1' => 556, 'x2' => 702],
    13 => ['y1' => 173, 'y2' => 264, 'x1' => 706, 'x2' => 862],

    // Row 2
    14 => ['y1' => 269, 'y2' => 359, 'x1' => 43, 'x2' => 193],
    15 => ['y1' => 269, 'y2' => 359, 'x1' => 196, 'x2' => 321],
    16 => ['y1' => 269, 'y2' => 359, 'x1' => 330, 'x2' => 485],
    17 => ['y1' => 269, 'y2' => 359, 'x1' => 488, 'x2' => 615],
    18 => ['y1' => 269, 'y2' => 359, 'x1' => 618, 'x2' => 744],
    19 => ['y1' => 269, 'y2' => 359, 'x1' => 750, 'x2' => 863],

    // Row 3
    20 => ['y1' => 363, 'y2' => 456, 'x1' => 43, 'x2' => 170],
    21 => ['y1' => 363, 'y2' => 456, 'x1' => 171, 'x2' => 297],
    22 => ['y1' => 363, 'y2' => 456, 'x1' => 298, 'x2' => 424],
    23 => ['y1' => 363, 'y2' => 456, 'x1' => 425, 'x2' => 551],
    24 => ['y1' => 363, 'y2' => 456, 'x1' => 555, 'x2' => 681],
    25 => ['y1' => 363, 'y2' => 456, 'x1' => 684, 'x2' => 812],
    26 => ['y1' => 363, 'y2' => 456, 'x1' => 814, 'x2' => 928],

    // Row 4
    27 => ['y1' => 459, 'y2' => 566, 'x1' => 43, 'x2' => 167],
    28 => ['y1' => 459, 'y2' => 566, 'x1' => 171, 'x2' => 297],
    29 => ['y1' => 459, 'y2' => 566, 'x1' => 303, 'x2' => 402],
    30 => ['y1' => 459, 'y2' => 566, 'x1' => 407, 'x2' => 506],
    31 => ['y1' => 459, 'y2' => 566, 'x1' => 510, 'x2' => 619],
    32 => ['y1' => 459, 'y2' => 566, 'x1' => 623, 'x2' => 729]
];

try {
    $pdo->beginTransaction();

    // 1. Delete previous April 2026 findings for Kantor lantai 2 to prevent duplicates
    echo "Clearing existing findings for Kantor lantai 2 (April 2026)...\n";
    $stmt_fetch_old = $pdo->prepare("SELECT id FROM findings WHERE area = 'Kantor lantai 2' AND division_id = 8 AND created_at >= '2026-04-01 00:00:00' AND created_at <= '2026-04-30 23:59:59'");
    $stmt_fetch_old->execute();
    $old_ids = $stmt_fetch_old->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($old_ids)) {
        $in_clause = implode(',', $old_ids);
        $pdo->exec("DELETE FROM finding_images WHERE finding_id IN ($in_clause)");
        $pdo->exec("DELETE FROM findings WHERE id IN ($in_clause)");
        echo "Deleted " . count($old_ids) . " old findings and their images.\n";
    }

    // 2. Prepare insert statements
    $stmt_finding = $pdo->prepare("
        INSERT INTO findings (area, division_id, description, pic, status, created_at, updated_at) 
        VALUES ('Kantor lantai 2', 8, :description, NULL, 'On Progress', '2026-04-15 10:00:00', NOW())
    ");

    $stmt_image = $pdo->prepare("
        INSERT INTO finding_images (finding_id, image_path, type, image_data, mime_type) 
        VALUES (:finding_id, :image_path, 'before', :image_data, 'image/jpeg')
    ");

    // 3. Process each finding
    $finding_count = 0;
    $photo_count = 0;

    foreach ($findings_data as $find_num => $f_info) {
        // Insert finding
        $stmt_finding->execute([
            'description' => $f_info['description']
        ]);
        $finding_id = $pdo->lastInsertId();
        $finding_count++;
        
        echo "Inserted finding #$find_num (ID: $finding_id): '{$f_info['description']}'\n";

        // Process associated photo slices
        foreach ($f_info['slices'] as $photo_idx => $slice_id) {
            $coords = $slices_coords[$slice_id];
            
            $w = $coords['x2'] - $coords['x1'] + 1;
            $h = $coords['y2'] - $coords['y1'] + 1;
            
            // Create a sub-image
            $sub_img = imagecreatetruecolor($w, $h);
            imagecopy($sub_img, $img, 0, 0, $coords['x1'], $coords['y1'], $w, $h);
            
            // Compress and save to output buffer as JPEG
            ob_start();
            imagejpeg($sub_img, null, 75); // 75% quality
            $image_data = ob_get_clean();
            
            imagedestroy($sub_img);
            
            // Define unique filename
            $unique_hash = uniqid();
            $filename = "before_april_2_{$find_num}_{$photo_idx}_{$unique_hash}.jpg";
            
            // Insert into finding_images
            $stmt_image->execute([
                'finding_id' => $finding_id,
                'image_path' => $filename,
                'image_data' => $image_data
            ]);
            $photo_count++;
        }
    }

    $pdo->commit();
    echo "\nSuccessfully inserted $finding_count findings and $photo_count cropped photos for GA Kantor lantai 2 (April 2026)!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}

imagedestroy($img);
?>
