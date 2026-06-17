<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Fetch all divisions
    $stmt = $pdo->query("SELECT * FROM divisions");
    $divisions = $stmt->fetchAll();
    
    echo "Found " . count($divisions) . " divisions. Seeding division-level user accounts...\n";
    
    $default_password = 'password123';
    $hashed_pass = password_hash($default_password, PASSWORD_DEFAULT);
    
    $success_count = 0;
    $exist_count = 0;
    
    // Map division ID to a clean username
    $username_mapping = [
        1 => 'divisi_rmt',
        2 => 'divisi_plastik',
        3 => 'divisi_insekfungi',
        4 => 'divisi_herbisida',
        5 => 'divisi_fg_logistik',
        6 => 'divisi_maintenance',
        7 => 'divisi_qc',
        8 => 'divisi_ga'
    ];
    
    foreach ($divisions as $div) {
        $div_id = $div['id'];
        $username = $username_mapping[$div_id] ?? 'divisi_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $div['name']));
        
        // Check if username already exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $check_stmt->execute(['username' => $username]);
        $existing_user = $check_stmt->fetch();
        
        if (!$existing_user) {
            // Insert new division-level user account (area_id is NULL)
            $ins_stmt = $pdo->prepare("
                INSERT INTO users (username, password, role, division_id, area_id) 
                VALUES (:username, :password, 'division', :division_id, NULL)
            ");
            $ins_stmt->execute([
                'username' => $username,
                'password' => $hashed_pass,
                'division_id' => $div_id
            ]);
            $success_count++;
            echo "- Created division account: $username (Divisi: {$div['name']})\n";
        } else {
            // Update role/division_id just in case
            $up_stmt = $pdo->prepare("
                UPDATE users 
                SET role = 'division', division_id = :division_id, area_id = NULL 
                WHERE id = :id
            ");
            $up_stmt->execute([
                'division_id' => $div_id,
                'id' => $existing_user['id']
            ]);
            $exist_count++;
            echo "- Updated division account: $username (Divisi: {$div['name']})\n";
        }
    }
    
    echo "Seeding division-level accounts completed!\n";
    echo "- Created new: $success_count\n";
    echo "- Updated/Matched existing: $exist_count\n";
    echo "Default Password for all division accounts is: '$default_password'\n";
    
} catch (PDOException $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
}
