<?php
$dir = 'c:/xampp/htdocs/ProjectsOJT/Data April/';
$files = glob($dir . '*.pdf');

foreach ($files as $file) {
    $filename = basename($file);
    echo "=== FILE: $filename ===\n";
    $content = file_get_contents($file);
    
    preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $content, $matches);
    
    $found_text = false;
    foreach ($matches[1] as $idx => $stream) {
        $data = @gzuncompress($stream);
        if ($data === false) {
            $data = @gzuncompress(substr($stream, 2));
        }
        
        if ($data !== false && (strpos($data, 'BT') !== false)) {
            // Find hex codes
            preg_match_all("/<([0-9a-fA-F]+)>/", $data, $hex_matches);
            if (empty($hex_matches[1])) continue;
            
            // Try different offsets to find one that produces readable text
            // We look for common Indonesian/English letters/words (e.g., 'No', 'NILAI', '5R', 'Area')
            for ($offset = 0; $offset <= 100; $offset++) {
                $decoded = "";
                $char_count = 0;
                foreach (array_slice($hex_matches[1], 0, 40) as $hex) {
                    for ($i = 0; $i < strlen($hex); $i += 4) {
                        $sub = substr($hex, $i, 4);
                        $val = hexdec($sub);
                        $shifted_val = $val + $offset;
                        $decoded .= html_entity_decode("&#$shifted_val;", ENT_NOQUOTES, 'UTF-8');
                        $char_count++;
                    }
                    $decoded .= " ";
                }
                
                // Heuristic: check if the decoded text contains common words/patterns
                // like "No", "NILAI", "Area", "Bagian", "catatan", "5R" (case-insensitive, with/without spaces)
                $clean = strtolower(str_replace(' ', '', $decoded));
                if (strpos($clean, 'nilai') !== false || strpos($clean, 'area') !== false || strpos($clean, 'bagian') !== false || strpos($clean, 'catatan') !== false || strpos($clean, 'no') !== false || strpos($clean, '5r') !== false) {
                    echo "Found match! Offset: +$offset\n";
                    echo "Sample: " . substr($decoded, 0, 150) . "\n";
                    $found_text = true;
                    break 2; // skip to next file
                }
            }
        }
    }
    
    if (!$found_text) {
        echo "No matching text/offset found using standard heuristic.\n";
    }
    echo "\n";
}
?>
