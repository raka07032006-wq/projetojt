<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

$search = "32 0 obj";
$pos = strpos($content, $search);
if ($pos !== false) {
    echo "Found object 32:\n";
    echo substr($content, $pos, 400) . "\n";
} else {
    echo "Object 32 not found\n";
}
?>
