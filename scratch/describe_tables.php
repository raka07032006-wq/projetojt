<?php
require_once __DIR__ . '/../config/db.php';

echo "=== area_evaluations schema ===\n";
$q = $pdo->query("DESCRIBE area_evaluations");
print_r($q->fetchAll(PDO::FETCH_ASSOC));

echo "=== findings schema ===\n";
$q = $pdo->query("DESCRIBE findings");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
