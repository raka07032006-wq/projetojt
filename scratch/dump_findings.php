<?php
require_once __DIR__ . '/../config/db.php';
$findings = $pdo->query("SELECT * FROM findings")->fetchAll();
echo "Total findings: " . count($findings) . "\n";
print_r($findings);
