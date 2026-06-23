<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.txt';
$content = file_get_contents($file);
$lines = explode("\n", $content);
for ($i = 0; $i < min(10, count($lines)); $i++) {
    echo "Line $i: '{$lines[$i]}'\n";
    echo "Bytes: ";
    for ($j = 0; $j < strlen($lines[$i]); $j++) {
        echo ord($lines[$i][$j]) . " ";
    }
    echo "\n\n";
}
?>
