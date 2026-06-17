<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("
    SELECT u.username, u.role, d.name AS division_name, a.name AS area_name 
    FROM users u 
    LEFT JOIN divisions d ON u.division_id = d.id 
    LEFT JOIN areas a ON u.area_id = a.id
    ORDER BY u.role ASC, d.name ASC, u.username ASC
");
$users = $stmt->fetchAll();

echo "TOTAL USERS: " . count($users) . "\n\n";
foreach ($users as $user) {
    echo "Username: " . $user['username'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Divisi: " . ($user['division_name'] ?? 'Semua/Admin') . "\n";
    echo "Area: " . ($user['area_name'] ?? 'Semua Area') . "\n";
    echo "----------------------------------------\n";
}
