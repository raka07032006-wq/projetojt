<?php
require_once __DIR__ . '/../config/db.php';

function get_area_regex($area_name) {
    // Normalization of search pattern based on name
    $name = trim($area_name);
    
    // Explicit overrides for known mismatches
    if ($name === 'Gudang bb botol D5') {
        return "Gudang (bb|bahan baku) botol D5";
    }
    if ($name === 'Gudang ( I 1 ) bahan baku gliposate, paraquat, mp, aux') {
        return "Gudang \\(\\s*I\\s*1\\s*\\) bahan baku";
    }
    if ($name === 'Gudang ( I 2 ) karton box') {
        return "Gudang \\(\\s*I\\s*2\\s*\\) karton box";
    }
    if ($name === 'Area Blow dan PET') {
        return "Area (Mesin )?Blow dan PET";
    }
    if ($name === 'Area Produksi Mulsa') {
        return "Produksi Mulsa";
    }
    if ($name === 'Area Mulsa Recycle') {
        return "Recycle Borongan";
    }
    if ($name === 'Area Mulsa Mixing') {
        return "Mixing Mulsa";
    }
    if ($name === 'Area Kantor Mulsa') {
        return "Kantor Mulsa";
    }
    if ($name === 'Area Mulsa Granulator') {
        return "Granulator Mulsa";
    }
    if ($name === 'Assembling Kantor') {
        return "Assembling (Area )?Kantor";
    }
    if ($name === 'Produksi Centafur') {
        return "Produksi (Centafur|CF)";
    }
    if ($name === 'IF Mixer Gd. B1') {
        return "IF Mixer";
    }
    if ($name === 'Gudang barang jadi Glyposate ( F5 )') {
        return "Gudang barang jadi Gly[ph]+osate";
    }
    if ($name === 'Gudang BJ J ( BARAT )') {
        return "Gudang (Barang Jadi J|BJ J) \\(Barat\\)";
    }
    if ($name === 'Cooling tower & WTP') {
        return "Cooling Tower (dan|&) WTP";
    }
    if ($name === 'Kantor engineering') {
        return "Kantor (Engineering|Maintenance)";
    }
    if ($name === 'Ruang panel Glyposate') {
        return "Ruang Panel Gly[ph]+osate";
    }
    if ($name === 'Compressor hanbell') {
        return "Compressor Han[d]?bell";
    }
    if ($name === 'Minilab CF-IF') {
        return "Minilab CF[\\s\\-]*IF";
    }
    
    // Default: convert to flexible regex
    $parts = [];
    for ($i = 0; $i < strlen($name); $i++) {
        $char = $name[$i];
        if (preg_match("/[a-zA-Z0-9]/", $char)) {
            $parts[] = preg_quote($char, '/');
        } else {
            $parts[] = "[^a-zA-Z0-9]*";
        }
    }
    return implode("", $parts);
}

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
    if (!file_exists($filepath)) continue;
    
    $text = file_get_contents($filepath);
    $text = str_replace("\r", "", $text);
    
    $stmt = $pdo->prepare("SELECT id, name FROM areas WHERE division_id = :div_id ORDER BY id ASC");
    $stmt->execute(['div_id' => $div_id]);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Division $div_id | $filename ===\n";
    foreach ($areas as $area) {
        $pattern = get_area_regex($area['name']);
        
        // Search for the pattern preceded optionally by a section number (e.g. "1. ")
        $regex = "/(?:\\b\\d+[\\.\\s]+)?" . $pattern . "/i";
        
        if (preg_match($regex, $text, $m, PREG_OFFSET_CAPTURE)) {
            echo "  Match: '{$area['name']}' -> found '{$m[0][0]}' at offset {$m[0][1]}\n";
        } else {
            echo "  MISSING: '{$area['name']}'\n";
        }
    }
    echo "\n";
}
?>
