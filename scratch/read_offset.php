<?php
$content = file_get_contents('c:/xampp/htdocs/ProjectsOJT/Data April/Rekap Nilai 5R Maintenance - April 2026.txt');
echo "=== Substring at 5700-6100 ===\n";
echo substr($content, 5700, 400);
echo "\n==============================\n";
?>
