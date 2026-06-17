<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "--- Create Table findings ---\n";
    $findings_create = $pdo->query("SHOW CREATE TABLE findings")->fetch(PDO::FETCH_ASSOC);
    echo $findings_create['Create Table'] . "\n\n";

    echo "--- Create Table notifications ---\n";
    $notif_create = $pdo->query("SHOW CREATE TABLE notifications")->fetch(PDO::FETCH_ASSOC);
    echo $notif_create['Create Table'] . "\n\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
