<?php
require_once __DIR__ . '/../config/db.php';

// Check GA (division_id = 8) evaluations for April 2026
echo "=== GA Evaluations for April 2026 ===\n";
$stmt = $pdo->query("SELECT ae.*, a.name as area_name FROM area_evaluations ae JOIN areas a ON ae.area_id = a.id WHERE a.division_id = 8 AND ae.date = '2026-04-30'");
$evals = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($evals as $e) {
    echo "Area ID: {$e['area_id']} | Area: {$e['area_name']} | Score: {$e['score']}\n";
}

echo "\n=== GA Findings for April 2026 ===\n";
$stmt2 = $pdo->query("SELECT f.*, a.name as area_name FROM findings f JOIN areas a ON f.area_id = a.id WHERE a.division_id = 8 AND f.date = '2026-04-30'");
$findings = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "Total findings found: " . count($findings) . "\n";
foreach ($findings as $idx => $f) {
    echo ($idx+1) . ". Area: {$f['area_name']} | Notes: {$f['notes']} | Image: {$f['image_path']}\n";
}
?>
