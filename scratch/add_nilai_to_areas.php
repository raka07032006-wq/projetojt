<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM areas LIKE 'nilai_5r'");
    $column = $stmt->fetch();
    
    if (!$column) {
        $pdo->exec("ALTER TABLE areas ADD COLUMN nilai_5r DECIMAL(3,2) DEFAULT NULL");
        echo "Migration completed: nilai_5r column added to areas table.\n";
    } else {
        echo "Migration skipped: nilai_5r column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
