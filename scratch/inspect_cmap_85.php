<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R Herbisida - Apr_compressed.pdf';
$content = file_get_contents($file);

if (preg_match("/\b85\s+0\s+obj\b/i", $content, $match, PREG_OFFSET_CAPTURE)) {
    $pos = $match[0][1];
    $end = strpos($content, "endobj", $pos);
    $obj_content = substr($content, $pos, $end - $pos + 6);
    if (preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj_content, $sm)) {
        $dec = @gzuncompress($sm[1]);
        if ($dec === false) $dec = @gzuncompress(substr($sm[1], 2));
        echo "=== CMap 85 ===\n" . $dec . "\n";
    }
}
?>
