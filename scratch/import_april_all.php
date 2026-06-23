<?php
require_once __DIR__ . '/../config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to normalize names for comparison
function normalize_name($name) {
    return preg_replace("/[^a-zA-Z0-9]/", "", strtolower($name));
}

try {
    $pdo->beginTransaction();

    // 1. Get all divisions and areas from the database
    $stmt = $pdo->query("SELECT id, name FROM divisions");
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $areas_by_div = [];
    $stmt_areas = $pdo->query("SELECT id, division_id, name FROM areas");
    foreach ($stmt_areas->fetchAll(PDO::FETCH_ASSOC) as $area) {
        $areas_by_div[$area['division_id']][] = $area;
    }

    // 2. Map division ID to txt files
    $file_map = [
        1 => 'Rekap Gudang RMT - Apr_compressed.txt',
        2 => 'REKAP 5R PRODUKSI PLASTIK - Apr.txt',
        3 => 'Rekap 5R Insectfungi - April_compressed.txt',
        4 => 'Rekap 5R Herbisida - Apr_compressed.txt',
        5 => 'REKAP PENILAIAN 5R LOGISTIK & GUDANG BJ - APRIL.txt',
        6 => 'Rekap Nilai 5R Maintenance - April 2026.txt',
        7 => 'REKAP 5R RND - APRIL 2026.txt',
        8 => 'Rekap 5R HRGA - APRIL 2026.txt'
    ];

    $dir = 'c:/xampp/htdocs/ProjectsOJT/Data April/';

    // Prepare DB statements
    $stmt_score = $pdo->prepare("
        INSERT INTO area_evaluations (area_id, bulan, tahun, nilai_5r) 
        VALUES (:area_id, 4, 2026, :nilai_5r)
        ON DUPLICATE KEY UPDATE nilai_5r = VALUES(nilai_5r)
    ");

    $stmt_finding = $pdo->prepare("
        INSERT INTO findings (area, division_id, description, pic, status, created_at, updated_at) 
        VALUES (:area, :division_id, :description, NULL, 'On Progress', '2026-04-15 10:00:00', NOW())
    ");

    // Clear previous findings for divisions 1 to 7 for April 2026 to prevent duplicate entries
    echo "Clearing existing findings for divisions 1-7 (April 2026)...\n";
    $pdo->exec("DELETE FROM findings WHERE division_id BETWEEN 1 AND 7 AND created_at >= '2026-04-01 00:00:00' AND created_at <= '2026-04-30 23:59:59'");

    $total_scores = 0;
    $total_findings = 0;

    foreach ($file_map as $div_id => $filename) {
        $filepath = $dir . $filename;
        if (!file_exists($filepath)) {
            echo "File not found: $filepath\n";
            continue;
        }
        
        $div_name = "";
        foreach ($divisions as $d) {
            if ($d['id'] == $div_id) $div_name = $d['name'];
        }
        
        echo "\nProcessing Division $div_id: $div_name\n";
        
        $text = file_get_contents($filepath);
        $text_norm = str_replace("\r", "", $text);
        
        $pages = explode("--- PAGE BREAK ---", $text_norm);
        
        // --- A. Extract and Ingest Scores ---
        $score_page = $pages[0];
        $areas = $areas_by_div[$div_id] ?? [];
        
        foreach ($areas as $area) {
            $area_name = $area['name'];
            
            // Build flexible regex to match area name
            $regex_parts = [];
            for ($i = 0; $i < strlen($area_name); $i++) {
                $char = $area_name[$i];
                if (preg_match("/[a-zA-Z0-9]/", $char)) {
                    $regex_parts[] = preg_quote($char, '/');
                } else {
                    $regex_parts[] = "[^a-zA-Z0-9]*";
                }
            }
            $flexible_regex = implode("", $regex_parts);
            
            if (preg_match("/$flexible_regex.{0,50}?(\d)[.,](\d{2})/is", $score_page, $m)) {
                $score = floatval($m[1] . '.' . $m[2]);
                $stmt_score->execute([
                    'area_id' => $area['id'],
                    'nilai_5r' => $score
                ]);
                $total_scores++;
            }
        }
        
        // --- B. Extract and Ingest Findings (Only for Divisions 1 to 7) ---
        // (GA / Division 8 findings were already inserted manually with photos)
        if ($div_id == 8) {
            continue;
        }
        
        for ($p_idx = 1; $p_idx < count($pages); $p_idx++) {
            $page_text = $pages[$p_idx];
            if (trim($page_text) === "") continue;
            
            // Find matched areas on this page
            $area_matches = [];
            foreach ($areas as $area) {
                $area_name = $area['name'];
                
                $regex_parts = [];
                for ($i = 0; $i < strlen($area_name); $i++) {
                    $char = $area_name[$i];
                    if (preg_match("/[a-zA-Z0-9]/", $char)) {
                        $regex_parts[] = preg_quote($char, '/');
                    } else {
                        $regex_parts[] = "[^a-zA-Z0-9]*";
                    }
                }
                $flexible_regex = implode("", $regex_parts);
                
                if (preg_match("/\b$flexible_regex\b/is", $page_text, $m, PREG_OFFSET_CAPTURE)) {
                    $area_matches[] = [
                        'id' => $area['id'],
                        'name' => $area_name,
                        'offset' => $m[0][1]
                    ];
                } else if (preg_match("/$flexible_regex/is", $page_text, $m, PREG_OFFSET_CAPTURE)) {
                    $area_matches[] = [
                        'id' => $area['id'],
                        'name' => $area_name,
                        'offset' => $m[0][1]
                    ];
                }
            }
            
            // Sort matches
            usort($area_matches, function($a, $b) {
                return $a['offset'] <=> $b['offset'];
            });
            
            if (empty($area_matches)) continue;
            
            // Ingest findings for each section
            for ($i = 0; $i < count($area_matches); $i++) {
                $curr_match = $area_matches[$i];
                $start_pos = $curr_match['offset'];
                $end_pos = ($i + 1 < count($area_matches)) ? $area_matches[$i+1]['offset'] : strlen($page_text);
                
                $section_text = substr($page_text, $start_pos, $end_pos - $start_pos);
                $area_name = $curr_match['name'];
                
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
                    
                    foreach ($section_findings as $desc) {
                        $stmt_finding->execute([
                            'area' => $area_name,
                            'division_id' => $div_id,
                            'description' => $desc
                        ]);
                        $total_findings++;
                    }
                }
            }
        }
    }

    $pdo->commit();
    echo "\n=== INGESTION SUCCESSFUL ===\n";
    echo "Total scores updated: $total_scores\n";
    echo "Total text findings inserted: $total_findings\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\nError during ingestion: " . $e->getMessage() . "\n";
}
?>
