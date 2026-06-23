<?php
require_once __DIR__ . '/../config/db.php';

echo "=== DIVISIONS ===\n";
$stmt = $pdo->query("SELECT * FROM divisions");
$divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($divisions as $d) {
    echo "ID: {$d['id']} | Name: {$d['name']}\n";
    
    echo "  Areas:\n";
    $stmt_areas = $pdo->prepare("SELECT * FROM areas WHERE division_id = ?");
    $stmt_areas->execute([$d['id']]);
    foreach ($stmt_areas->fetchAll(PDO::FETCH_ASSOC) as $a) {
        echo "    ID: {$a['id']} | Name: {$a['name']}\n";
    }
}
?>
