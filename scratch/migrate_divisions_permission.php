<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM divisions LIKE 'akses_perbaikan'");
    $column = $stmt->fetch();
    
    if (!$column) {
        $pdo->exec("ALTER TABLE divisions ADD COLUMN akses_perbaikan TINYINT(1) NOT NULL DEFAULT 1");
        echo "Migration completed: akses_perbaikan column added to divisions table.\n";
    } else {
        echo "Migration skipped: akses_perbaikan column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
