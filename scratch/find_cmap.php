<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

// Find ToUnicode references
preg_match_all("/\/ToUnicode\s+(\d+)\s+(\d+)\s+R/i", $content, $matches);

foreach ($matches[1] as $idx => $obj_id) {
    $search = "$obj_id 0 obj";
    $pos = strpos($content, $search);
    if ($pos !== false) {
        $end_pos = strpos($content, "endobj", $pos);
        $obj_content = substr($content, $pos, $end_pos - $pos + 6);
        
        if (preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj_content, $stream_match)) {
            $decompressed = @gzuncompress($stream_match[1]);
            if ($decompressed === false) {
                $decompressed = @gzuncompress(substr($stream_match[1], 2));
            }
            if ($decompressed !== false) {
                echo "=== Font Object $obj_id CMap ===\n";
                echo $decompressed . "\n\n";
            }
        }
    }
}
?>
