<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $content, $matches);

echo "Total streams found: " . count($matches[1]) . "\n";
foreach ($matches[1] as $idx => $stream) {
    $data = @gzuncompress($stream);
    if ($data === false) {
        $data = @gzuncompress(substr($stream, 2));
    }
    
    if ($data !== false) {
        if (strpos($data, 'BT') !== false || strpos($data, 'Tj') !== false || strpos($data, 'TJ') !== false) {
            echo "Stream #$idx contains text instructions! Length: " . strlen($data) . "\n";
            // Print a snippet
            preg_match_all("/BT(.*?)ET/is", $data, $bt_matches);
            foreach (array_slice($bt_matches[0], 0, 5) as $bt) {
                echo "  Text Block: " . substr(trim($bt), 0, 200) . "\n";
            }
        }
    }
}
?>
