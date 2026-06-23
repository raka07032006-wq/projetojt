<?php
function extract_pdf_text($filename) {
    $content = @file_get_contents($filename);
    if (!$content) {
        return "Could not read file.\n";
    }

    // PDF stream parser
    $result = "";
    
    // Find all streams
    preg_match_all("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $content, $matches);
    
    foreach ($matches[1] as $stream) {
        // Decompress if compressed using FlateDecode
        $data = @gzuncompress($stream);
        if ($data === false) {
            // Fallback for some stream formats
            $data = @gzuncompress(substr($stream, 2));
        }
        
        if ($data !== false) {
            // Find text blocks
            // TJ or Tj commands
            // TJ format: [ (Text) -10 (Text) ] TJ
            // Tj format: (Text) Tj
            
            // Extract Tj
            preg_match_all("/\((.*?)\)\s*Tj/i", $data, $tj_matches);
            if (!empty($tj_matches[1])) {
                $result .= implode(" ", $tj_matches[1]) . "\n";
            }
            
            // Extract TJ
            preg_match_all("/\[(.*?)\]\s*TJ/i", $data, $tj_array_matches);
            foreach ($tj_array_matches[1] as $tj_array) {
                preg_match_all("/\((.*?)\)/", $tj_array, $parts);
                if (!empty($parts[1])) {
                    $result .= implode("", $parts[1]) . " ";
                }
            }
            $result .= "\n";
        }
    }
    
    return $result;
}

$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
echo "Extracting text from: $file\n";
$text = extract_pdf_text($file);
echo "--- EXTRACTED TEXT (FIRST 1000 CHARS) ---\n";
echo substr($text, 0, 1000) . "\n";
?>
