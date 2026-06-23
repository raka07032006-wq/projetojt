<?php
require_once __DIR__ . '/../config/db.php';

echo "=== finding_images schema ===\n";
$q = $pdo->query("DESCRIBE finding_images");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
