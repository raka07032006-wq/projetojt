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
        
        preg_match_all("/<([0-9a-fA-F]+)>/", $data, $hex_matches);
        
        $decoded = "";
        foreach ($hex_matches[1] as $hex) {
            for ($i = 0; $i < strlen($hex); $i += 4) {
                $sub = substr($hex, $i, 4);
                $val = hexdec($sub);
                $shifted_val = $val + 29;
                $decoded .= html_entity_decode("&#$shifted_val;", ENT_NOQUOTES, 'UTF-8');
            }
            $decoded .= " ";
        }
        
        echo "Decoded text:\n";
        echo $decoded . "\n\n";
    }
}
?>
