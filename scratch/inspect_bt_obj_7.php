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
if (preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj7_content, $m)) {
    $decompressed = @gzuncompress($m[1]);
    if ($decompressed === false) {
        $decompressed = @gzuncompress(substr($m[1], 2));
    }
    if ($decompressed !== false) {
        echo "BT index: " . strpos($decompressed, "BT") . "\n";
        echo "ET index: " . strpos($decompressed, "ET") . "\n";
        
        // Count BT and ET
        echo "BT count: " . substr_count($decompressed, "BT") . "\n";
        echo "ET count: " . substr_count($decompressed, "ET") . "\n";
        
        // Let's print occurrences of BT and some text after them
        $pos = 0;
        $count = 0;
        while (($pos = strpos($decompressed, "BT", $pos)) !== false && $count < 10) {
            echo "BT occurrence at $pos:\n" . substr($decompressed, $pos, 200) . "\n\n";
            $pos += 2;
            $count++;
        }
    }
}
?>
