<?php
require_once __DIR__ . '/../config/db.php';

$divs_to_check = [1, 2, 3, 4, 7];

foreach ($divs_to_check as $div_id) {
    echo "=== Division $div_id ===\n";
    $stmt = $pdo->prepare("
        SELECT a.name as area_name, ae.nilai_5r
        FROM area_evaluations ae
        JOIN areas a ON ae.area_id = a.id
        WHERE a.division_id = :div_id AND ae.bulan = 4 AND ae.tahun = 2026
        ORDER BY a.id ASC
    ");
    $stmt->execute(['div_id' => $div_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  - {$row['area_name']}: {$row['nilai_5r']}\n";
    }
    echo "\n";
}
?>
