<?php
$searches = [
    'Rekap 5R Herbisida - Apr_compressed.txt' => ['panel', 'glyposate', 'glyphosate'],
    'REKAP PENILAIAN 5R LOGISTIK & GUDANG BJ - APRIL.txt' => ['Glyposate', 'BARAT', 'BJ J'],
    'Rekap Nilai 5R Maintenance - April 2026.txt' => ['Cooling', 'WTP', 'panel Glyposate']
];

$dir = 'c:/xampp/htdocs/ProjectsOJT/Data April/';

foreach ($searches as $file => $queries) {
    echo "=== File: $file ===\n";
    $content = file_get_contents($dir . $file);
    $lines = explode("\n", $content);
    foreach ($queries as $q) {
        echo "  Search '$q':\n";
        foreach ($lines as $idx => $line) {
            if (stripos($line, $q) !== false) {
                echo "    Line " . ($idx+1) . ": " . trim($line) . "\n";
            }
        }
    }
    echo "\n";
}
?>
