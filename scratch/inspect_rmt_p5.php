<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap Gudang RMT - Apr_compressed.txt';
$content = file_get_contents($file);
$pages = explode("--- PAGE BREAK ---", $content);
echo "=== Page 5 ===\n" . $pages[5] . "\n\n";
echo "=== Page 6 ===\n" . $pages[6] . "\n\n";
?>
