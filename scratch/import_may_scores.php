<?php
require_once __DIR__ . '/../config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo->beginTransaction();

    echo "=== PREPARING AREAS FOR DIVISION 8 (HRGA) ===\n";

    // 1. Rename Area ID 36 (Gazebo-assembling) to 'Gudang Botol - Starkum - Assembling Dan Toilet Assembling'
    $stmt_rename = $pdo->prepare("UPDATE areas SET name = :new_name WHERE id = 36");
    $stmt_rename->execute(['new_name' => 'Gudang Botol - Starkum - Assembling Dan Toilet Assembling']);
    echo "Renamed Area ID 36 to 'Gudang Botol - Starkum - Assembling Dan Toilet Assembling'\n";

    // 2. Check and insert the new area: 'Parkir Tamu - Taman Kantor - Parkir Mobil Karyawan - Danau Belakang Kantor'
    $new_area_name = 'Parkir Tamu - Taman Kantor - Parkir Mobil Karyawan - Danau Belakang Kantor';
    $stmt_check = $pdo->prepare("SELECT id FROM areas WHERE division_id = 8 AND name = :name");
    $stmt_check->execute(['name' => $new_area_name]);
    $new_area_row = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($new_area_row) {
        $new_area_id = $new_area_row['id'];
        echo "Area '$new_area_name' already exists with ID: $new_area_id\n";
    } else {
        $stmt_insert_area = $pdo->prepare("INSERT INTO areas (division_id, name) VALUES (8, :name)");
        $stmt_insert_area->execute(['name' => $new_area_name]);
        $new_area_id = $pdo->lastInsertId();
        echo "Inserted new Area '$new_area_name' with ID: $new_area_id\n";
    }

    // 3. Define the scores mapping
    $scores_by_division = [
        // Division 4: Herbisida
        4 => [
            'Filling E1' => 1.97,
            'Filling F4' => 2.37,
            'Area ka.shift dan operator reaktor' => 2.79,
            'Area panel kontrol' => 2.87,
            'Reaktor glyposate bagian atas' => 3.21,
            'Reaktor paraquat bagian atas' => 2.58,
            'Reaktor aux bagian atas' => 2.27,
            'Reaktor glyposate bagian bawah' => 2.35,
            'Reaktor paraquat bagian bawah' => 2.38,
            'Reaktor aux bagian bawah' => 2.32,
            'Tangki amonia' => 2.34,
        ],
        // Division 1: RMT / Gudang BB
        1 => [
            'Gudang Bahan Baku If Cf ( A1 )' => 2.67,
            'Gudang Bahan Baku If Cf Stiker ( B1 )' => 2.66,
            'Gudang bahan baku metyl, aux, mp dan stiker ( F3 )' => 2.49,
            'Gudang bahan baku gliphosate ( F2 )' => 2.30,
            'Gudang bahan baku paraquat ( F1 )' => 2.04,
            'Gudang ( I 1 ) bahan baku gliposate, paraquat, mp, aux' => 2.24,
            'Gudang ( I 2 ) karton box' => 2.77,
            'Gudang bahan baku assembling I3' => 3.29,
            'Gudang bahan baku assembling I4' => 3.01,
            'Gudang barang jadi tutup botol D4' => 2.82,
            'Gudang bb botol D5' => 2.80,
            'Gudang bahan baku mulsa' => 3.19,
        ],
        // Division 2: Plastik
        2 => [
            'Area Blow dan PET' => 2.55,
            'Area Inject' => 2.56,
            'Area Mixing' => 2.78,
            'Area Crusher' => 2.63,
            'Area Welding' => 2.72,
            'Area Kantor Plastik' => 2.89,
            'Area Produksi Mulsa' => 2.96,
            'Area Mulsa Recycle' => 2.76,
            'Area Mulsa Mixing' => 2.79,
            'Area Kantor Mulsa' => 2.95,
            'Area Mulsa Granulator' => 2.50,
            'Assembling Perakitan' => 2.86,
            'Assembling Area Sablon' => 2.49,
            'Assembling Kantor' => 2.83,
        ],
        // Division 5: FG & Logistik
        5 => [
            'Area loading 1' => 2.87,
            'Area loading 2' => 2.94,
            'Gudang barang jadi Glyposate ( F5 )' => 2.94,
            'Gudang barang jadi Centafur ( A2 )' => 2.73,
            'Gudang barang jadi Insect Fungi (A3)' => 2.77,
            'Gudang barang jadi assembling' => 3.21,
            'Gudang barang jadi mulsa' => 2.75,
            'Gudang BJ J ( BARAT )' => 2.73,
        ],
        // Division 8: HRGA (special handling for variations)
        8 => [
            'Kantor lantai 1' => 3.29,
            'Kantor lantai 2' => 3.49,
            'Gudang IT' => 3.45,
            'Kantin atas ( area dalam )' => 2.69,
            'Kantin bawah ( area dalam )' => 3.27,
            'Mushola atas ( area dalam )' => 3.42,
            'Mushola bawah ( area dalam )' => 3.51,
            'Parkir Motor - Security - Jl. Utama - Parkiran Manager - CFIF' => 2.69,
            'Mulsa - Botol - Timbangan - Mushola - Kantin - Logistik - TPS ( area luar ) Dan Jalan Paving' => 2.64,
            'Parkir Tamu - Taman Kantor - Parkir Mobil Karyawan - Danau Belakang Kantor' => 2.40,
            'Gudang Botol - Starkum - Assembling Dan Toilet Assembling' => 2.68,
            'Ruang Dokumen' => 2.83,
            'TPS ( area dalam )' => 1.94,
        ]
    ];

    // Fetch all database areas to match names dynamically
    $stmt_areas = $pdo->query("SELECT id, division_id, name FROM areas");
    $db_areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

    // Build standard mapper
    $area_mapper = [];
    foreach ($db_areas as $da) {
        $area_mapper[$da['division_id']][strtolower(trim($da['name']))] = $da['id'];
    }

    // Prepare insert statement
    $stmt_score = $pdo->prepare("
        INSERT INTO area_evaluations (area_id, bulan, tahun, nilai_5r) 
        VALUES (:area_id, 5, 2026, :nilai_5r)
        ON DUPLICATE KEY UPDATE nilai_5r = VALUES(nilai_5r)
    ");

    $inserted_count = 0;

    foreach ($scores_by_division as $div_id => $areas_scores) {
        echo "\nProcessing Division ID: $div_id\n";
        foreach ($areas_scores as $area_name => $score) {
            $matched_id = null;
            $normalized_name = strtolower(trim($area_name));

            // Custom manual mappings for Division 8 aliases
            if ($div_id == 8) {
                if (strpos($normalized_name, 'kantin atas') !== false) {
                    $matched_id = $area_mapper[8][strtolower(trim('Kantin atas'))] ?? null;
                } elseif (strpos($normalized_name, 'kantin bawah') !== false) {
                    $matched_id = $area_mapper[8][strtolower(trim('Kantin bawah'))] ?? null;
                } elseif (strpos($normalized_name, 'mushola atas') !== false) {
                    $matched_id = $area_mapper[8][strtolower(trim('Mushola atas'))] ?? null;
                } elseif (strpos($normalized_name, 'mushola bawah') !== false) {
                    $matched_id = $area_mapper[8][strtolower(trim('Mushola bawah'))] ?? null;
                } elseif (strpos($normalized_name, 'parkir motor - security') !== false) {
                    $matched_id = $area_mapper[8][strtolower(trim('Gerbang- CF IF'))] ?? null;
                } elseif (strpos($normalized_name, 'mulsa - botol - timbangan') !== false) {
                    $matched_id = $area_mapper[8][strtolower(trim('Mulsa-Kantin'))] ?? null;
                } elseif (strpos($normalized_name, 'parkir tamu - taman kantor') !== false) {
                    $matched_id = $new_area_id;
                } elseif (strpos($normalized_name, 'tps') !== false) {
                    $matched_id = $area_mapper[8][strtolower(trim('TPS'))] ?? null;
                }
            }

            // Fallback to standard exact matches
            if ($matched_id === null) {
                if (isset($area_mapper[$div_id][$normalized_name])) {
                    $matched_id = $area_mapper[$div_id][$normalized_name];
                }
            }

            if ($matched_id !== null) {
                $stmt_score->execute([
                    'area_id' => $matched_id,
                    'nilai_5r' => $score
                ]);
                echo "  Inserted score $score for area ID $matched_id ('$area_name')\n";
                $inserted_count++;
            } else {
                throw new Exception("Error: Could not match area '$area_name' for division $div_id in the database.");
            }
        }
    }

    $pdo->commit();
    echo "\n=== IMPORT SUCCESSFUL ===\n";
    echo "Total area scores inserted/updated for May 2026: $inserted_count\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error during import: " . $e->getMessage() . "\n";
}
?>
