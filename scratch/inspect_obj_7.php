<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

function get_obj_content($content, $id) {
    if (!preg_match("/\b{$id}\s+0\s+obj\b/i", $content, $m, PREG_OFFSET_CAPTURE)) {
        return "";
    }
    $pos = $m[0][1];
    $end = strpos($content, "endobj", $pos);
    if ($end === false) return "";
    return substr($content, $pos, $end - $pos + 6);
}

$obj7_content = get_obj_content($content, 7);
echo "Obj 7 content header:\n" . substr($obj7_content, 0, 200) . "\n\n";

if (preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj7_content, $m)) {
    $stream = $m[1];
    $decompressed = @gzuncompress($stream);
    if ($decompressed === false) {
        $decompressed = @gzuncompress(substr($stream, 2));
    }
    if ($decompressed !== false) {
        echo "Decompressed stream length: " . strlen($decompressed) . "\n";
        echo "Sample (first 500 chars):\n" . substr($decompressed, 0, 500) . "\n";
    } else {
        echo "Failed to decompress stream!\n";
    }
} else {
    echo "No stream found in Obj 7!\n";
}
?>
