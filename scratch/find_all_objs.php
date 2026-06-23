<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

preg_match_all("/(\d+)\s+0\s+obj/i", $content, $matches, PREG_OFFSET_CAPTURE);

$objects = [];
foreach ($matches[1] as $idx => $match) {
    $id = $match[0];
    $offset = $match[1];
    $objects[$id][] = $offset;
}

echo "Total unique object IDs: " . count($objects) . "\n";
foreach ($objects as $id => $offsets) {
    if (count($offsets) > 1) {
        echo "Object $id has " . count($offsets) . " definitions at offsets: " . implode(", ", $offsets) . "\n";
    }
}
?>
