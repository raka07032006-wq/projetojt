<?php
require_once __DIR__ . '/../config/db.php';

function get_area_regex($area_name) {
    $name = trim($area_name);
    
    if ($name === 'Gudang bb botol D5') {
        return "Gudang (bb|bahan baku) botol D5";
    }
    if ($name === 'Gudang ( I 1 ) bahan baku gliposate, paraquat, mp, aux') {
        return "Gudang \\(\\s*I\\s*1\\s*\\) bahan baku";
    }
    if ($name === 'Gudang ( I 2 ) karton box') {
        return "Gudang \\(\\s*I\\s*2\\s*\\) karton box";
    }
    if ($name === 'Area Blow dan PET') {
        return "Area (Mesin )?Blow dan PET";
    }
    if ($name === 'Area Produksi Mulsa') {
        return "Produksi Mulsa";
    }
    if ($name === 'Area Mulsa Recycle') {
        return "Recycle Borongan";
    }
    if ($name === 'Area Mulsa Mixing') {
        return "Mixing Mulsa";
    }
    if ($name === 'Area Kantor Mulsa') {
        return "Kantor Mulsa";
    }
    if ($name === 'Area Mulsa Granulator') {
        return "Granulator Mulsa";
    }
    if ($name === 'Assembling Kantor') {
        return "Assembling (Area )?Kantor";
    }
    if ($name === 'Produksi Centafur') {
        return "Produksi (Centafur|CF)";
    }
    if ($name === 'IF Mixer Gd. B1') {
        return "IF Mixer";
    }
    if ($name === 'Gudang barang jadi Glyposate ( F5 )') {
        return "Gudang barang jadi Gly[ph]+osate";
    }
    if ($name === 'Gudang BJ J ( BARAT )') {
        return "Gudang (Barang Jadi J|BJ J) \\(Barat\\)";
    }
    if ($name === 'Cooling tower & WTP') {
        return "Cooling Tower (dan|&) WTP";
    }
    if ($name === 'Kantor engineering') {
        return "Kantor (Engineering|Maintenance)";
    }
    if ($name === 'Ruang panel Glyposate') {
        return "Ruang Panel Gly[ph]+osate";
    }
    if ($name === 'Compressor hanbell') {
        return "Compressor Han[d]?bell";
    }
    if ($name === 'Minilab CF-IF') {
        return "Minilab CF[\\s\\-]*IF";
    }
    if ($name === 'Reaktor glyposate bagian bawah') {
        return "Reaktor gly[ph]+osate bagian bawah";
    }
    if ($name === 'Reaktor glyposate bagian atas') {
        return "Reaktor gly[ph]+osate bagian atas";
    }
    
    // Default flexible regex
    $parts = [];
    for ($i = 0; $i < strlen($name); $i++) {
        $char = $name[$i];
        if (preg_match("/[a-zA-Z0-9]/", $char)) {
            $parts[] = preg_quote($char, '/');
        } else {
            $parts[] = "[^a-zA-Z0-9]*";
        }
    }
    return implode("", $parts);
}

$file_map = [
    1 => 'Rekap Gudang RMT - Apr_compressed.txt',
    2 => 'REKAP 5R PRODUKSI PLASTIK - Apr.txt',
    3 => 'Rekap 5R Insectfungi - April_compressed.txt',
    4 => 'Rekap 5R Herbisida - Apr_compressed.txt',
    5 => 'REKAP PENILAIAN 5R LOGISTIK & GUDANG BJ - APRIL.txt',
    6 => 'Rekap Nilai 5R Maintenance - April 2026.txt',
    7 => 'REKAP 5R RND - APRIL 2026.txt'
];

$dir = 'c:/xampp/htdocs/ProjectsOJT/Data April/';

foreach ($file_map as $div_id => $filename) {
    $filepath = $dir . $filename;
    if (!file_exists($filepath)) continue;
    
    $text = file_get_contents($filepath);
    $text = str_replace("\r", "", $text);
    
    // Find search start offset
    $start_search_offset = 0;
    if (preg_match("/No\.?\s*catatan/i", $text, $match_cat, PREG_OFFSET_CAPTURE)) {
        $start_search_offset = max(0, $match_cat[0][1] - 50);
    }
    
    // Fetch areas
    $stmt = $pdo->prepare("SELECT id, name FROM areas WHERE division_id = :div_id ORDER BY id ASC");
    $stmt->execute(['div_id' => $div_id]);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Match section offsets
    $matches = [];
    foreach ($areas as $area) {
        $pattern = get_area_regex($area['name']);
        $regex = "/(?:\\b\\d+[\\.\\s]+)?" . $pattern . "/i";
        
        $offset = $start_search_offset;
        while (preg_match($regex, $text, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $match_str = $m[0][0];
            $match_offset = $m[0][1];
            
            $context = substr($text, $match_offset, 250);
            if (preg_match("/No\b/i", $context) && preg_match("/catatan/i", $context)) {
                $matches[] = [
                    'id' => $area['id'],
                    'name' => $area['name'],
                    'offset' => $match_offset,
                    'length' => strlen($match_str)
                ];
                break;
            }
            $offset = $match_offset + strlen($match_str);
        }
    }
    
    // Sort matches by offset to determine start/end positions
    usort($matches, function($a, $b) {
        return $a['offset'] <=> $b['offset'];
    });
    
    echo "=== Division $div_id | $filename (Matches: " . count($matches) . ") ===\n";
    
    for ($i = 0; $i < count($matches); $i++) {
        $curr = $matches[$i];
        $start_pos = $curr['offset'];
        $end_pos = ($i + 1 < count($matches)) ? $matches[$i+1]['offset'] : strlen($text);
        
        $section_text = substr($text, $start_pos, $end_pos - $start_pos);
        
        // Extract findings after "Catatan"
        $cat_pos = stripos($section_text, 'catatan');
        if ($cat_pos !== false) {
            $findings_text = substr($section_text, $cat_pos + 7);
            
            $lines = explode("\n", $findings_text);
            $section_findings = [];
            $curr_num = null;
            $curr_desc = "";
            
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === "" || $line === "-") continue;
                
                if (ctype_digit($line)) {
                    $num = intval($line);
                    if ($curr_num !== null && !empty($curr_desc)) {
                        $desc = trim($curr_desc);
                        if (strlen($desc) >= 6 && !ctype_digit(str_replace(' ', '', $desc))) {
                            $section_findings[$curr_num] = $desc;
                        }
                    }
                    $curr_num = $num;
                    $curr_desc = "";
                } else if (preg_match("/^(\d+)[.\s]+(.*)$/", $line, $m)) {
                    $num = intval($m[1]);
                    if ($curr_num !== null && !empty($curr_desc)) {
                        $desc = trim($curr_desc);
                        if (strlen($desc) >= 6 && !ctype_digit(str_replace(' ', '', $desc))) {
                            $section_findings[$curr_num] = $desc;
                        }
                    }
                    $curr_num = $num;
                    $curr_desc = $m[2];
                } else {
                    if ($curr_num !== null) {
                        $curr_desc .= " " . $line;
                    }
                }
            }
            if ($curr_num !== null && !empty($curr_desc)) {
                $desc = trim($curr_desc);
                if (strlen($desc) >= 6 && !ctype_digit(str_replace(' ', '', $desc))) {
                    $section_findings[$curr_num] = $desc;
                }
            }
            
            echo "  Area: '{$curr['name']}' -> offset: {$curr['offset']} -> Findings count: " . count($section_findings) . "\n";
            // Print first 2 findings
            $slice = array_slice($section_findings, 0, 2, true);
            foreach ($slice as $num => $desc) {
                echo "    $num. $desc\n";
            }
        } else {
            echo "  Area: '{$curr['name']}' -> NO CATATAN WORD FOUND!\n";
        }
    }
    echo "\n";
}
?>
