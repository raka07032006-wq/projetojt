<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT id, name FROM divisions ORDER BY id ASC");
$divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($divisions as $div) {
    $stmt_areas = $pdo->prepare("
        SELECT ae.nilai_5r
        FROM areas a
        LEFT JOIN area_evaluations ae ON a.id = ae.area_id AND ae.bulan = 4 AND ae.tahun = 2026
        WHERE a.division_id = :div_id
    ");
    $stmt_areas->execute(['div_id' => $div['id']]);
    $scores = $stmt_areas->fetchAll(PDO::FETCH_COLUMN);
    
    $total = 0;
    $count = 0;
    foreach ($scores as $s) {
        if ($s !== null) {
            $total += floatval($s);
            $count++;
        }
    }
    
    if ($count > 0) {
        $avg = $total / $count;
        $avg_rounded_standard = round($avg, 2);
        $avg_rounded_half_down = round($avg, 2, PHP_ROUND_HALF_DOWN);
        
        $grade_standard = get_letter_grade($avg_rounded_standard);
        $grade_half_down = get_letter_grade($avg_rounded_half_down);
        
        echo "Division {$div['id']}: {$div['name']}\n";
        echo "  - Avg raw: $avg\n";
        echo "  - Standard round (2): $avg_rounded_standard -> Grade: $grade_standard\n";
        echo "  - Half Down round (2): $avg_rounded_half_down -> Grade: $grade_half_down\n";
    } else {
        echo "Division {$div['id']}: {$div['name']} has no scores.\n";
    }
    echo "\n";
}
?>
