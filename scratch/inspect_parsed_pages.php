<?php
require_once __DIR__ . '/test_extractor.php';

$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$parser = new SimplePDFParser($file);

$pages = explode("\n--- PAGE BREAK ---\n", $parser->getText());
echo "Total parsed pages: " . count($pages) . "\n";
foreach ($pages as $idx => $content) {
    echo "--- PAGE $idx (Length: " . strlen($content) . ") ---\n";
    echo substr(trim($content), 0, 200) . "\n";
    echo "====================================\n\n";
}
?>
