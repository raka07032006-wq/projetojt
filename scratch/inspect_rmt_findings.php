<?php
require_once __DIR__ . '/../config/db.php';

echo "=== RMT Findings in April 2026 ===\n";
$stmt = $pdo->query("SELECT area, description FROM findings WHERE division_id = 1 AND created_at >= '2026-04-01 00:00:00' AND created_at <= '2026-04-30 23:59:59'");
$findings = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total findings: " . count($findings) . "\n";
foreach (array_slice($findings, 0, 30) as $idx => $f) {
    echo ($idx+1) . ". Area: {$f['area']} | Desc: {$f['description']}\n";
}
?>
