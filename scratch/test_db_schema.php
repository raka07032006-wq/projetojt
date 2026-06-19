<?php
require_once __DIR__ . '/../config/db.php';
echo "Findings Columns:\n";
$stmt = $pdo->query("SHOW COLUMNS FROM findings");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

echo "\nFinding Images Columns:\n";
$stmt = $pdo->query("SHOW COLUMNS FROM finding_images");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
