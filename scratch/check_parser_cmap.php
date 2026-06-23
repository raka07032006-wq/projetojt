<?php
require_once __DIR__ . '/test_extractor.php';

$parser = new SimplePDFParser('c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf');

$ref = new ReflectionClass($parser);
$method = $ref->getMethod('parseCMap');
$method->setAccessible(true);

echo "=== CMap for Object 33 raw stream ===\n";
// Let's print the actual stream content it parses
$search = "33 0 obj";
$content = file_get_contents('c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf');
$pos = strrpos($content, $search);
if ($pos !== false) {
    $end_pos = strpos($content, "endobj", $pos);
    $obj_content = substr($content, $pos, $end_pos - $pos + 6);
    if (preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj_content, $stream_match)) {
        $decompressed = @gzuncompress($stream_match[1]);
        if ($decompressed === false) {
            $decompressed = @gzuncompress(substr($stream_match[1], 2));
        }
        echo $decompressed . "\n";
    }
}
?>
