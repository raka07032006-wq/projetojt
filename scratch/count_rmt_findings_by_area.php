<?php
require_once __DIR__ . '/../config/db.php';

echo "=== RMT Findings Count by Area ===\n";
$stmt = $pdo->query("
    SELECT area, COUNT(*) as c
    FROM findings
    WHERE division_id = 1 AND created_at >= '2026-04-01 00:00:00' AND created_at <= '2026-04-30 23:59:59'
    GROUP BY area
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Area: {$row['area']} | Findings: {$row['c']}\n";
}
?>
