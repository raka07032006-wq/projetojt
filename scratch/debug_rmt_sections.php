<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap Gudang RMT - Apr_compressed.txt';
$content = file_get_contents($file);

$pages = explode("--- PAGE BREAK ---", $content);

echo "=== Total Pages in RMT text file: " . count($pages) . " ===\n";

$areas_to_debug = [
    'Gudang Bahan Baku If Cf ( A1 )',
    'Gudang ( I 1 ) bahan baku gliposate, paraquat, mp, aux',
    'Gudang bb botol D5'
];

foreach ($pages as $p_idx => $page) {
    echo "\n--- Page $p_idx (first 100 chars): '" . trim(preg_replace("/\s+/", " ", substr($page, 0, 100))) . "'\n";
    // Check which areas are found in this page
    foreach ($areas_to_debug as $area_name) {
        $clean_area = preg_replace("/[^a-zA-Z0-9]/", "", strtolower($area_name));
        $clean_page = preg_replace("/[^a-zA-Z0-9]/", "", strtolower($page));
        if (strpos($clean_page, $clean_area) !== false) {
            echo "  [MATCH] normalized area '{$area_name}' is inside this page!\n";
        }
    }
}
?>
