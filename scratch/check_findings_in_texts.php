<?php
$dir = 'c:/xampp/htdocs/ProjectsOJT/Data April/';
$files = glob($dir . '*.txt');

foreach ($files as $file) {
    $content = file_get_contents($file);
    echo "=== FILE: " . basename($file) . " ===\n";
    
    // Count occurrences of Catatan/CATATAN
    $count = preg_match_all("/catatan/i", $content, $matches);
    echo "Catatan count: $count\n";
    
    // Print a few snippets of text containing Catatan
    if ($count > 0) {
        $pos = 0;
        $idx = 0;
        while (($pos = stripos($content, 'catatan', $pos)) !== false && $idx < 3) {
            echo "  Occurrence #$idx:\n";
            echo substr($content, max(0, $pos - 50), 200) . "\n";
            $pos += 7;
            $idx++;
        }
    }
    echo "----------------------------------------\n\n";
}
?>
