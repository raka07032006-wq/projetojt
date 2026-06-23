<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap Gudang RMT - Apr_compressed.txt';
$content = file_get_contents($file);

$pos = 0;
while (($pos = stripos($content, 'IF CF', $pos)) !== false) {
    echo "Found 'IF CF' at position $pos:\n";
    echo substr($content, max(0, $pos - 100), 400) . "\n";
    echo "----------------------------------------\n";
    $pos += 5;
}
?>
