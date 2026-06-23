<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query('SELECT d.id as division_id, d.name as division_name, a.id as area_id, a.name as area_name FROM areas a JOIN divisions d ON a.division_id = d.id ORDER BY d.id, a.id');
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = [];
foreach ($areas as $row) {
    $grouped[$row['division_id']]['name'] = $row['division_name'];
    $grouped[$row['division_id']]['areas'][] = [
        'id' => $row['area_id'],
        'name' => $row['area_name']
    ];
}

foreach ($grouped as $div_id => $info) {
    echo "=== DIVISION $div_id: {$info['name']} ===\n";
    foreach ($info['areas'] as $a) {
        echo "  - Area ID: {$a['id']} | Name: {$a['name']}\n";
    }
    echo "\n";
}
?>
