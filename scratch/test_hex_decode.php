<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $content, $matches);

foreach ($matches[1] as $idx => $stream) {
    $data = @gzuncompress($stream);
    if ($data === false) {
        $data = @gzuncompress(substr($stream, 2));
    }
    
    if ($data !== false && (strpos($data, 'BT') !== false)) {
        echo "Stream #$idx:\n";
        
        // Find all <XXXX> hex codes
        preg_match_all("/<([0-9a-fA-F]+)>/", $data, $hex_matches);
        
        $decoded = "";
        foreach ($hex_matches[1] as $hex) {
            // Check if 4 hex chars (UTF-16BE / UCS-2)
            if (strlen($hex) == 4) {
                $val = hexdec($hex);
                // Convert value to char
                $decoded .= html_entity_decode("&#$val;", ENT_NOQUOTES, 'UTF-8');
            } else {
                // If it's a sequence of hex digits
                for ($i = 0; $i < strlen($hex); $i += 4) {
                    $sub = substr($hex, $i, 4);
                    $val = hexdec($sub);
                    $decoded .= html_entity_decode("&#$val;", ENT_NOQUOTES, 'UTF-8');
                }
            }
            $decoded .= " ";
        }
        
        echo "Decoded text snippet:\n";
        echo substr($decoded, 0, 1000) . "\n\n";
        break; // just check the first text stream
    }
}
?>
