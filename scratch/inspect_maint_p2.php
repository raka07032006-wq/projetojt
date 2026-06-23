<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap Nilai 5R Maintenance - April 2026.txt';
$content = file_get_contents($file);
$pages = explode("--- PAGE BREAK ---", $content);
foreach ($pages as $idx => $page) {
    echo "=== Page $idx (length: " . strlen($page) . ") ===\n";
    echo substr(trim($page), 0, 150) . "\n";
    echo "---------------------------\n";
}
?>
