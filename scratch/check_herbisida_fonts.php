<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R Herbisida - Apr_compressed.pdf';
$content = file_get_contents($file);

echo "=== ToUnicode References in Herbisida ===\n";
preg_match_all("/\/ToUnicode\s+(\d+)\s+\d+\s+R/i", $content, $m);
echo "Found " . count($m[1]) . " references: " . implode(", ", $m[1]) . "\n\n";

foreach ($m[1] as $obj_id) {
    echo "ToUnicode Object $obj_id:\n";
    // Get object
    if (preg_match("/\b{$obj_id}\s+0\s+obj\b/i", $content, $match, PREG_OFFSET_CAPTURE)) {
        $pos = $match[0][1];
        $end = strpos($content, "endobj", $pos);
        $obj_content = substr($content, $pos, $end - $pos + 6);
        echo "  Header: " . substr($obj_content, 0, 150) . "\n";
        
        if (preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj_content, $sm)) {
            $dec = @gzuncompress($sm[1]);
            if ($dec === false) $dec = @gzuncompress(substr($sm[1], 2));
            if ($dec !== false) {
                echo "  CMap excerpt:\n" . substr($dec, 0, 300) . "\n";
            } else {
                echo "  Failed to decompress stream!\n";
            }
        }
    }
    echo "---------------------------\n";
}
?>
