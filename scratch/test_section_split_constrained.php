<?php
require_once __DIR__ . '/../config/db.php';

$file_map = [
    1 => 'Rekap Gudang RMT - Apr_compressed.txt',
    2 => 'REKAP 5R PRODUKSI PLASTIK - Apr.txt',
    3 => 'Rekap 5R Insectfungi - April_compressed.txt',
    4 => 'Rekap 5R Herbisida - Apr_compressed.txt',
    5 => 'REKAP PENILAIAN 5R LOGISTIK & GUDANG BJ - APRIL.txt',
    6 => 'Rekap Nilai 5R Maintenance - April 2026.txt',
    7 => 'REKAP 5R RND - APRIL 2026.txt'
];

$dir = 'c:/xampp/htdocs/ProjectsOJT/Data April/';

foreach ($file_map as $div_id => $filename) {
    $filepath = $dir . $filename;
    if (!file_exists($filepath)) {
        continue;
    }
    
    // Fetch areas for this division
    $stmt = $pdo->prepare("SELECT id, name FROM areas WHERE division_id = :div_id ORDER BY id ASC");
    $stmt->execute(['div_id' => $div_id]);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Division $div_id ({$filename}) | Areas count: " . count($areas) . " ===\n";
    
    $text = file_get_contents($filepath);
    $text = str_replace("\r", "", $text);
    
    $offsets = [];
    $last_offset = 0;
    
    for ($i = 0; $i < count($areas); $i++) {
        $sec_num = $i + 1;
        
        // Search in substring starting from $last_offset
        $subtext = substr($text, $last_offset);
        
        $pattern = "/^\s*" . $sec_num . "\s*\./m";
        if (preg_match($pattern, $subtext, $m, PREG_OFFSET_CAPTURE)) {
            $offset = $last_offset + $m[0][1];
            $offsets[$i] = $offset;
            $last_offset = $offset + strlen($m[0][0]);
            echo "  Area " . $sec_num . " (" . $areas[$i]['name'] . "): Found at offset " . $offset . "\n";
        } else {
            $pattern2 = "/^\s*" . $sec_num . "\s+/m";
            if (preg_match($pattern2, $subtext, $m, PREG_OFFSET_CAPTURE)) {
                $offset = $last_offset + $m[0][1];
                $offsets[$i] = $offset;
                $last_offset = $offset + strlen($m[0][0]);
                echo "  Area " . $sec_num . " (" . $areas[$i]['name'] . "): Found (without dot) at offset " . $offset . "\n";
            } else {
                echo "  Area " . $sec_num . " (" . $areas[$i]['name'] . "): NOT FOUND!\n";
            }
        }
    }
    echo "\n";
}
?>
