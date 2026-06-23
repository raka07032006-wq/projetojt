<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

// Find all Page objects
$pos = 0;
$pages = [];
while (($pos = strpos($content, '/Type /Page', $pos)) !== false) {
    $start = $pos;
    while ($start > 0 && substr($content, $start, 3) !== 'obj') {
        $start--;
    }
    $start_obj = $start;
    while ($start_obj > 0 && $content[$start_obj] !== "\n" && $content[$start_obj] !== "\r") {
        $start_obj--;
    }
    $header = trim(substr($content, $start_obj, $pos - $start_obj));
    if (preg_match("/(\d+)\s+\d+\s+obj/i", $header, $m)) {
        $pages[] = $m[1];
    }
    $pos += 11;
}

echo "Found " . count($pages) . " page objects: " . implode(", ", $pages) . "\n\n";

foreach ($pages as $idx => $page_id) {
    $search = "$page_id 0 obj";
    $pos = strpos($content, $search);
    if ($pos === false) {
        echo "Page $idx (Obj $page_id): NOT FOUND\n";
        continue;
    }
    $end_pos = strpos($content, "endobj", $pos);
    $obj_content = substr($content, $pos, $end_pos - $pos + 6);
    
    echo "Page $idx (Obj $page_id):\n";
    if (preg_match("/\/Contents\s+(\d+)\s+\d+\s+R/i", $obj_content, $m)) {
        echo "  Contents: Obj " . $m[1] . "\n";
    } else if (preg_match("/\/Contents\s*\[(.*?)\]/is", $obj_content, $m)) {
        echo "  Contents: Array [" . trim(preg_replace("/\s+/", " ", $m[1])) . "]\n";
    } else {
        echo "  Contents: NONE\n";
    }
    
    if (preg_match("/\/Resources\s*<<(.*?)>>/is", $obj_content, $m)) {
        echo "  Resources snippet: " . substr(trim($m[1]), 0, 100) . "\n";
    } else if (preg_match("/\/Resources\s+(\d+)\s+\d+\s+R/i", $obj_content, $m)) {
        // Resources is a separate object
        $res_id = $m[1];
        echo "  Resources: Obj $res_id\n";
        $r_pos = strpos($content, "$res_id 0 obj");
        if ($r_pos !== false) {
            $r_end = strpos($content, "endobj", $r_pos);
            $r_content = substr($content, $r_pos, $r_end - $r_pos + 6);
            echo "    Content: " . substr(trim($r_content), 0, 200) . "\n";
        }
    }
}
?>
