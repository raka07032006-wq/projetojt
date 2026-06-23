<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$content = file_get_contents($file);

function get_obj_content($content, $id) {
    if (!preg_match("/\b{$id}\s+0\s+obj\b/i", $content, $m, PREG_OFFSET_CAPTURE)) {
        return "";
    }
    $pos = $m[0][1];
    $end = strpos($content, "endobj", $pos);
    if ($end === false) return "";
    return substr($content, $pos, $end - $pos + 6);
}

// 1. Find /Catalog
preg_match("/\b(\d+)\s+\d+\s+obj\s*<<\s*\/Type\s*\/Catalog/i", $content, $m);
if (!$m) {
    preg_match("/\/Root\s+(\d+)\s+\d+\s+R/i", $content, $m);
}

if (!$m) {
    die("Catalog root not found.\n");
}

$catalog_id = $m[1];
echo "Catalog Object ID: $catalog_id\n";

$catalog_content = get_obj_content($content, $catalog_id);
echo "Catalog content: " . trim($catalog_content) . "\n\n";

// Find /Pages
if (preg_match("/\/Pages\s+(\d+)\s+\d+\s+R/i", $catalog_content, $m)) {
    $pages_id = $m[1];
    echo "Pages root Object ID: $pages_id\n";
    $pages_root_content = get_obj_content($content, $pages_id);
    echo "Pages root content: " . trim($pages_root_content) . "\n\n";
    
    // Resolve kids recursively
    function resolve_kids($content, $id) {
        $obj = get_obj_content($content, $id);
        if (preg_match("/\/Kids\s*\[(.*?)\]/is", $obj, $m)) {
            preg_match_all("/(\d+)\s+\d+\s+R/i", $m[1], $m_ids);
            $pages = [];
            foreach ($m_ids[1] as $kid_id) {
                $kid_obj = get_obj_content($content, $kid_id);
                if (preg_match("/\/Type\s*\/Page\b/i", $kid_obj)) {
                    $pages[] = $kid_id;
                } else if (preg_match("/\/Type\s*\/Pages\b/i", $kid_obj)) {
                    $pages = array_merge($pages, resolve_kids($content, $kid_id));
                }
            }
            return $pages;
        }
        return [];
    }
    
    $pages = resolve_kids($content, $pages_id);
    echo "Resolved " . count($pages) . " real Page objects in order: " . implode(", ", $pages) . "\n";
    
    foreach ($pages as $idx => $p_id) {
        $p_obj = get_obj_content($content, $p_id);
        echo "Page $idx (Obj $p_id):\n";
        if (preg_match("/\/Contents\s+(\d+)\s+\d+\s+R/i", $p_obj, $m)) {
            echo "  Contents: Obj " . $m[1] . "\n";
        } else if (preg_match("/\/Contents\s*\[(.*?)\]/is", $p_obj, $m)) {
            echo "  Contents: Array [" . trim(preg_replace("/\s+/", " ", $m[1])) . "]\n";
        } else {
            echo "  Contents: NONE\n";
        }
    }
} else {
    echo "Pages root not found in Catalog.\n";
}
?>
