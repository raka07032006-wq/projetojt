<?php
require_once __DIR__ . '/../config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo->beginTransaction();

    // 1. Define the remaining scores mapping
    $scores_by_division = [
        // Division 6: Maintenance
        6 => [
            'Otomotif' => 2.61,
            'Area penyimpanan change part CF' => 2.17,
            'Mtc filling' => 2.77,
            'Cooling tower & WTP' => 2.96,
            'Workshop utility' => 2.91,
            'Kantor engineering' => 3.32,
            'Chiller' => 3.39,
            'Genset & cmp gl' => 3.24,
            'Compressor hanbell' => 3.32,
            'Gudang pestisida' => 3.30,
            'Gudang plastik' => 2.98,
            'Area penyimpanan change part MP' => 2.85,
            'Area panel CF' => 2.48,
            'Area panel MP jetmil' => 2.38,
            'Area panel MT' => 3.38,
            'Ruang panel Glyposate' => 2.57,
            'Kantor engineering Plastik' => 2.58,
            'Workshop Plastik' => 2.42,
        ],
        // Division 3: Insekfungi
        3 => [
            'Produksi Centafur' => 2.75,
            'IF Packing Gd. B4' => 2.96,
            'IF Mixer Gd. B1' => 2.68,
            'Methyl' => 2.78,
            'Jetmill' => 2.84,
            'MP Packing' => 2.96,
            'Starkum' => 2.76,
        ],
        // Division 7: QC-RND
        7 => [
            'Ruang office lab' => 3.42,
            'Ruang sampel plastik' => 3.29,
            'Ruang meeting lab' => 3.41,
            'Ruang loby dan taman lab' => 2.60,
            'Ruang instrumen' => 3.41,
            'Ruang arsip sampel' => 2.65,
            'Ruang formulasi RnD' => 3.19,
            'Ruang preparasi' => 3.42,
            'Ruang oven dan timbangan' => 3.24,
            'Ruang mikrobiologi' => 3.44,
            'Ruang QC plastik' => 3.24,
            'Ruang QC assembling' => 3.15,
            'Minilab filling' => 2.90,
            'Minilab reaktor' => 2.62,
            'Ruang workshop RnD' => 2.88,
            'Minilab CF-IF' => 2.79,
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

            if (isset($area_mapper[$div_id][$normalized_name])) {
                $matched_id = $area_mapper[$div_id][$normalized_name];
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
    echo "Total remaining area scores inserted/updated for May 2026: $inserted_count\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error during import: " . $e->getMessage() . "\n";
}
?>
