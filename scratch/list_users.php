<?php
require_once __DIR__ . '/../config/db.php';
$users = $pdo->query("SELECT id, username, role, division_id, area_id FROM users WHERE area_id IS NULL")->fetchAll();
print_r($users);
