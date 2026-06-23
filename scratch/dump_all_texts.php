<?php
require_once __DIR__ . '/test_extractor.php';

$dir = 'c:/xampp/htdocs/ProjectsOJT/Data April/';
$files = glob($dir . '*.pdf');

// We exclude the non-compressed one if compressed is present
$files_to_process = [];
foreach ($files as $file) {
    $filename = basename($file);
    if (strpos($filename, 'Insectfungi - April.pdf') !== false) {
        // Skip uncompressed one if compressed exists
        continue;
    }
    $files_to_process[] = $file;
}

foreach ($files_to_process as $file) {
    $basename = basename($file, '.pdf');
    echo "Processing $basename...\n";
    $parser = new SimplePDFParser($file);
    $text = $parser->getText();
    
    // Save to text file
    file_put_contents($dir . $basename . '.txt', $text);
    
    // Print the first 400 characters of page 1 to see the score table layout
    echo "--- SAMPLE OF PAGE 1 ---\n";
    echo substr($text, 0, 500) . "\n";
    echo "========================\n\n";
}
?>
