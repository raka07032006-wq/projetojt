<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

// Find all objects with /Type /Font
$pos = 0;
while (($pos = strpos($content, '/Type /Font', $pos)) !== false) {
    // Search backward to find the start of the object: "X Y obj"
    $start = $pos;
    while ($start > 0 && substr($content, $start, 3) !== 'obj') {
        $start--;
    }
    
    // Find the object ID
    $start_obj = $start;
    while ($start_obj > 0 && $content[$start_obj] !== "\n" && $content[$start_obj] !== "\r") {
        $start_obj--;
    }
    
    $obj_header = trim(substr($content, $start_obj, $pos - $start_obj));
    echo "Font Object found near position $pos: $obj_header\n";
    
    $pos += 11;
}
?>
