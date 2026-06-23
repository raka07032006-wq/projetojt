<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("
    SELECT ae.id, ae.area_id, a.name AS area_name, ae.nilai_5r, ae.bulan, ae.tahun
    FROM area_evaluations ae
    JOIN areas a ON ae.area_id = a.id
    WHERE a.division_id = 1 AND ae.bulan = 4 AND ae.tahun = 2026
    ORDER BY a.id ASC
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total records found: " . count($results) . "\n";
foreach ($results as $row) {
    echo "ID: {$row['id']} | Area ID: {$row['area_id']} | Name: {$row['area_name']} | Nilai: {$row['nilai_5r']} | Period: {$row['bulan']}-{$row['tahun']}\n";
}
?>
