<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

// Find "33 0 obj"
$pos = strpos($content, "33 0 obj");
if ($pos !== false) {
    $end_pos = strpos($content, "endobj", $pos);
    $obj_content = substr($content, $pos, $end_pos - $pos + 6);
    
    if (preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj_content, $stream_match)) {
        $decompressed = @gzuncompress($stream_match[1]);
        if ($decompressed === false) {
            $decompressed = @gzuncompress(substr($stream_match[1], 2));
        }
        if ($decompressed !== false) {
            echo "=== Font Object 33 ToUnicode CMap ===\n";
            echo $decompressed . "\n";
        }
    }
} else {
    echo "Object 33 not found\n";
}
?>
