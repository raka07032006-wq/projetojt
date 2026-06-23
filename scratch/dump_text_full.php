<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $content, $matches);

$full_text = "";
foreach ($matches[1] as $idx => $stream) {
    $data = @gzuncompress($stream);
    if ($data === false) {
        $data = @gzuncompress(substr($stream, 2));
    }
    
    if ($data !== false && (strpos($data, 'BT') !== false)) {
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
        
        // Clean double spaces to restore word spacing
        // E.g. "P e r b a i k a n   s a l u r a n" -> "Perbaikan saluran"
        $clean = preg_replace("/\s{2,}/", " | ", $decoded);
        $clean = str_replace(" ", "", $clean);
        $clean = str_replace("|", " ", $clean);
        
        $full_text .= "--- STREAM #$idx ---\n" . $clean . "\n\n";
    }
}

file_put_contents('c:/xampp/htdocs/ProjectsOJT/scratch/rekap_hrga.txt', $full_text);
echo "Dumped text to scratch/rekap_hrga.txt\n";
?>
