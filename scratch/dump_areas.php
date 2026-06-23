<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("
    SELECT id, division_id, name
    FROM areas
    WHERE division_id = 1
    ORDER BY id ASC
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total areas found for division 1: " . count($results) . "\n";
foreach ($results as $row) {
    echo "ID: {$row['id']} | Name: {$row['name']}\n";
}
?>
