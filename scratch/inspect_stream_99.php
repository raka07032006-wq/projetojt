<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $content, $matches);

$data = @gzuncompress($matches[1][99]);
if ($data === false) {
    $data = @gzuncompress(substr($matches[1][99], 2));
}

echo "=== Raw Stream #99 (First 2000 chars) ===\n";
echo substr($data, 0, 2000) . "\n";
?>
