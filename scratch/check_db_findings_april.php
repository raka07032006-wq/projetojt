<?php
require_once __DIR__ . '/../config/db.php';

echo "=== FINDINGS IN APRIL 2026 ===\n";
$stmt = $pdo->prepare("SELECT * FROM findings WHERE created_at >= '2026-04-01 00:00:00' AND created_at <= '2026-04-30 23:59:59' AND division_id = 8");
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($res) . "\n";
foreach ($res as $f) {
    echo "ID: {$f['id']} | Area: {$f['area']} | Desc: " . substr($f['description'], 0, 100) . " | Created At: {$f['created_at']}\n";
    
    // Check images
    $stmt_img = $pdo->prepare("SELECT id, image_path, type, length(image_data) as img_len FROM finding_images WHERE finding_id = ?");
    $stmt_img->execute([$f['id']]);
    foreach ($stmt_img->fetchAll(PDO::FETCH_ASSOC) as $img) {
        echo "  Image ID: {$img['id']} | Path: {$img['image_path']} | Type: {$img['type']} | Blob size: {$img['img_len']} bytes\n";
    }
}
?>
