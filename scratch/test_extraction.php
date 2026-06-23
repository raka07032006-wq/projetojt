<?php
require_once __DIR__ . '/../config/db.php';

// 1. Get all divisions and their areas from database
$stmt = $pdo->query("SELECT id, name FROM divisions");
$divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$areas_by_div = [];
$stmt_areas = $pdo->query("SELECT id, division_id, name FROM areas");
foreach ($stmt_areas->fetchAll(PDO::FETCH_ASSOC) as $area) {
    $areas_by_div[$area['division_id']][] = $area;
}

// 2. Map PDF/TXT file names to division IDs
$file_map = [
    1 => 'Rekap Gudang RMT - Apr_compressed.txt',
    2 => 'REKAP 5R PRODUKSI PLASTIK - Apr.txt',
    3 => 'Rekap 5R Insectfungi - April_compressed.txt', // we can use the compressed one
    4 => 'Rekap 5R Herbisida - Apr_compressed.txt',
    5 => 'REKAP PENILAIAN 5R LOGISTIK & GUDANG BJ - APRIL.txt',
    6 => 'Rekap Nilai 5R Maintenance - April 2026.txt',
    7 => 'REKAP 5R RND - APRIL 2026.txt',
    8 => 'Rekap 5R HRGA - APRIL 2026.txt'
];

$dir = 'c:/xampp/htdocs/ProjectsOJT/Data April/';

foreach ($file_map as $div_id => $filename) {
    $filepath = $dir . $filename;
    if (!file_exists($filepath)) {
        echo "File not found: $filepath\n";
        continue;
    }
    
    $div_name = "";
    foreach ($divisions as $d) {
        if ($d['id'] == $div_id) $div_name = $d['name'];
    }
    
    echo "=== DIVISION $div_id: $div_name ===\n";
    $text = file_get_contents($filepath);
    
    // Normalize spaces and characters
    $text_norm = str_replace("\r", "", $text);
    
    // Try to extract scores
    echo "Scores found:\n";
    $areas = $areas_by_div[$div_id] ?? [];
    foreach ($areas as $area) {
        // Search for area name in text, then find a decimal number close to it
        $area_name = $area['name'];
        // escape special characters for regex
        $esc_area = preg_quote($area_name, '/');
        
        // Find area name, followed by optional spaces, then a decimal number (\d[.,]\d{2})
        // Since the text table structure might list the area name, then the score later,
        // we can look in a window of 50 characters after the area name!
        if (preg_match("/$esc_area.{0,50}?(\d)[.,](\d{2})/is", $text_norm, $m)) {
            $score = floatval($m[1] . '.' . $m[2]);
            echo "  - {$area_name}: $score (matched: {$m[0]})\n";
        } else {
            echo "  - {$area_name}: NOT FOUND\n";
        }
    }
    
    echo "\n";
}
?>
