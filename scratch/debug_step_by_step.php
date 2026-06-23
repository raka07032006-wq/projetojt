<?php
require_once __DIR__ . '/test_extractor.php';

$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

$stream_obj_id = 7;
$search = "$stream_obj_id 0 obj";
$pos = strpos($content, $search);

echo "Search string: '$search'\n";
echo "strpos position: ";
var_dump($pos);

if ($pos !== false) {
    $end_pos = strpos($content, "endobj", $pos);
    echo "end_pos position: ";
    var_dump($end_pos);
    
    $obj_content = substr($content, $pos, $end_pos - $pos + 6);
    echo "obj_content snippet (first 100 chars): '" . substr($obj_content, 0, 100) . "'\n";
    
    if (preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj_content, $stream_match)) {
        echo "Stream matched, length: " . strlen($stream_match[1]) . "\n";
        $decompressed = @gzuncompress($stream_match[1]);
        if ($decompressed === false) {
            $decompressed = @gzuncompress(substr($stream_match[1], 2));
        }
        echo "Decompress status: ";
        var_dump($decompressed !== false);
        
        if ($decompressed !== false) {
            preg_match_all("/BT(.*?)ET/is", $decompressed, $bt_matches);
            echo "BT matches count: " . count($bt_matches[0]) . "\n";
        }
    } else {
        echo "Regex for stream failed!\n";
    }
}
?>
