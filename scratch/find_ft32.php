<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

// Find references to FT32 in the PDF
preg_match_all("/\/FT32\s+(\d+)\s+(\d+)\s+R/i", $content, $matches);
echo "FT32 font references:\n";
print_r($matches[0]);

// Let's search for FT32 directly
$pos = 0;
while (($pos = strpos($content, '/FT32', $pos)) !== false) {
    echo "Found /FT32 at position $pos: " . substr($content, $pos - 50, 100) . "\n";
    $pos += 5;
}
?>
