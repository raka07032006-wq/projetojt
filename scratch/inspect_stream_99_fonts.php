<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $content, $matches);

$data = @gzuncompress($matches[1][99]);
if ($data === false) {
    $data = @gzuncompress(substr($matches[1][99], 2));
}

// Find all BT ... ET
preg_match_all("/BT(.*?)ET/is", $data, $bt_matches);

echo "Found " . count($bt_matches[0]) . " text blocks in Stream #99.\n";
foreach (array_slice($bt_matches[0], 0, 50) as $idx => $bt) {
    // Extract font
    preg_match("/\/(FT\d+)/", $bt, $font_match);
    $font = $font_match[1] ?? 'unknown';
    
    // Extract hex
    preg_match_all("/<([0-9a-fA-F]+)>/", $bt, $hex_matches);
    
    $raw_hex = implode(" ", $hex_matches[1]);
    
    // Decode with +29
    $decoded_29 = "";
    foreach ($hex_matches[1] as $hex) {
        for ($i = 0; $i < strlen($hex); $i += 4) {
            $sub = substr($hex, $i, 4);
            $val = hexdec($sub);
            $shifted = $val + 29;
            $decoded_29 .= html_entity_decode("&#$shifted;", ENT_NOQUOTES, 'UTF-8');
        }
        $decoded_29 .= " ";
    }
    
    // Decode with 0 (no offset)
    $decoded_0 = "";
    foreach ($hex_matches[1] as $hex) {
        for ($i = 0; $i < strlen($hex); $i += 4) {
            $sub = substr($hex, $i, 4);
            $val = hexdec($sub);
            $decoded_0 .= html_entity_decode("&#$val;", ENT_NOQUOTES, 'UTF-8');
        }
        $decoded_0 .= " ";
    }
    
    echo "Block #$idx [Font: $font]:\n";
    echo "  Raw Hex: $raw_hex\n";
    echo "  Decoded (+29): " . substr($decoded_29, 0, 80) . "\n";
    echo "  Decoded (+0):  " . substr($decoded_0, 0, 80) . "\n";
}
?>
