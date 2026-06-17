<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Fetch all predefined areas
    $stmt = $pdo->query("SELECT * FROM areas");
    $areas = $stmt->fetchAll();
    
    echo "Found " . count($areas) . " areas. Seeding user accounts...\n";
    
    $default_password = 'password123';
    $hashed_pass = password_hash($default_password, PASSWORD_DEFAULT);
    
    $success_count = 0;
    $exist_count = 0;
    
    foreach ($areas as $area) {
        $area_id = $area['id'];
        $div_id = $area['division_id'];
        $original_name = $area['name'];
        
        // Sanitize name for username
        // 1. Lowercase
        $clean = strtolower($original_name);
        // 2. Replace non-alphanumeric characters with underscores
        $clean = preg_replace('/[^a-z0-9]+/', '_', $clean);
        // 3. Collapse multiple underscores
        $clean = preg_replace('/_+/', '_', $clean);
        // 4. Trim leading/trailing underscores
        $clean = trim($clean, '_');
        
        $username = 'area_' . $clean;
        
        // Limit username length if needed (column is VARCHAR(50))
        if (strlen($username) > 50) {
            $username = substr($username, 0, 50);
        }
        
        // Check if username already exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $check_stmt->execute(['username' => $username]);
        $existing_user = $check_stmt->fetch();
        
        if (!$existing_user) {
            // Insert new area restricted user account
            $ins_stmt = $pdo->prepare("
                INSERT INTO users (username, password, role, division_id, area_id) 
                VALUES (:username, :password, 'division', :division_id, :area_id)
            ");
            $ins_stmt->execute([
                'username' => $username,
                'password' => $hashed_pass,
                'division_id' => $div_id,
                'area_id' => $area_id
            ]);
            $success_count++;
        } else {
            // Update the existing account to link it to the area_id and division_id (in case it wasn't linked)
            $up_stmt = $pdo->prepare("
                UPDATE users 
                SET division_id = :division_id, area_id = :area_id 
                WHERE id = :id AND role = 'division'
            ");
            $up_stmt->execute([
                'division_id' => $div_id,
                'area_id' => $area_id,
                'id' => $existing_user['id']
            ]);
            $exist_count++;
        }
    }
    
    echo "Seeding completed!\n";
    echo "- Created new accounts: $success_count\n";
    echo "- Updated/Matched existing: $exist_count\n";
    echo "Default Password for all new accounts is: '$default_password'\n";
    
} catch (PDOException $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
}
