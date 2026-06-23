<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query('
    SELECT ae.area_id, a.name, a.division_id, ae.nilai_5r 
    FROM area_evaluations ae 
    JOIN areas a ON ae.area_id = a.id 
    WHERE ae.bulan = 4 AND ae.tahun = 2026
    ORDER BY a.division_id, a.id
');
$scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($scores as $s) {
    echo "Div: " . $s['division_id'] . " | Area ID: " . $s['area_id'] . " | Name: " . $s['name'] . " | Score: " . $s['nilai_5r'] . "\n";
}
?>
