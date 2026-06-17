<?php
require_once __DIR__ . '/../config/db.php';
$users = $pdo->query("SELECT u.id, u.username, u.role, d.name AS division_name FROM users u LEFT JOIN divisions d ON u.division_id = d.id")->fetchAll();
print_r($users);
