<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT id, name FROM divisions ORDER BY id ASC");
$divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($divisions as $div) {
    echo "=== Division {$div['id']}: {$div['name']} ===\n";
    $stmt_areas = $pdo->prepare("
        SELECT a.name AS area_name, COUNT(f.id) AS findings_count
        FROM areas a
        LEFT JOIN findings f ON f.area = a.name AND f.division_id = a.division_id AND f.created_at >= '2026-04-01 00:00:00' AND f.created_at <= '2026-04-30 23:59:59'
        WHERE a.division_id = :div_id
        GROUP BY a.id, a.name
        ORDER BY a.id ASC
    ");
    $stmt_areas->execute(['div_id' => $div['id']]);
    foreach ($stmt_areas->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  - {$row['area_name']}: {$row['findings_count']} findings\n";
    }
    echo "\n";
}
?>
