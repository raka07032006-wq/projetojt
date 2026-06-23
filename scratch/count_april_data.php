<?php
require_once __DIR__ . '/../config/db.php';

echo "=== COUNT OF SCORES (area_evaluations) FOR APRIL 2026 ===\n";
$stmt = $pdo->query("
    SELECT d.id as division_id, d.name as division_name, COUNT(ae.id) as score_count
    FROM divisions d
    LEFT JOIN areas a ON a.division_id = d.id
    LEFT JOIN area_evaluations ae ON ae.area_id = a.id AND ae.bulan = 4 AND ae.tahun = 2026
    GROUP BY d.id, d.name
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID: {$row['division_id']} | {$row['division_name']} | Scores count: {$row['score_count']}\n";
}

echo "\n=== COUNT OF FINDINGS FOR APRIL 2026 ===\n";
$stmt2 = $pdo->query("
    SELECT d.id as division_id, d.name as division_name, COUNT(f.id) as findings_count
    FROM divisions d
    LEFT JOIN findings f ON f.division_id = d.id AND f.created_at >= '2026-04-01 00:00:00' AND f.created_at <= '2026-04-30 23:59:59'
    GROUP BY d.id, d.name
");
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID: {$row['division_id']} | {$row['division_name']} | Findings count: {$row['findings_count']}\n";
}
?>
