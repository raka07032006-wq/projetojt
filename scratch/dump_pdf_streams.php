<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $content, $matches);

foreach ($matches[1] as $idx => $stream) {
    $data = @gzuncompress($stream);
    if ($data === false) {
        $data = @gzuncompress(substr($stream, 2));
    }
    
    if ($data !== false) {
        echo "Stream #$idx (length " . strlen($data) . "):\n";
        echo substr($data, 0, 800) . "\n\n";
        if ($idx >= 3) break; // just print first few
    }
}
?>
