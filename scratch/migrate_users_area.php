<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'area_id'");
    $column = $stmt->fetch();
    
    if (!$column) {
        $pdo->exec("ALTER TABLE users ADD COLUMN area_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL");
        echo "Migration completed: area_id column added to users table.\n";
    } else {
        echo "Migration skipped: area_id column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
