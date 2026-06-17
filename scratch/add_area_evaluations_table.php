<?php
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Create area_evaluations table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `area_evaluations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `area_id` INT NOT NULL,
            `bulan` TINYINT NOT NULL CHECK (`bulan` BETWEEN 1 AND 12),
            `tahun` INT NOT NULL,
            `nilai_5r` DECIMAL(3,2) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_area_month_year` (`area_id`, `bulan`, `tahun`)
        ) ENGINE=InnoDB;
    ");
    echo "- Table 'area_evaluations' created or already exists.\n";

    // 2. Check if column 'nilai_5r' exists in 'areas' to migrate data
    $stmt = $pdo->query("SHOW COLUMNS FROM areas LIKE 'nilai_5r'");
    $column = $stmt->fetch();

    if ($column) {
        // Fetch all existing scores
        $stmt_scores = $pdo->query("SELECT id, nilai_5r FROM areas WHERE nilai_5r IS NOT NULL");
        $existing_scores = $stmt_scores->fetchAll();

        echo "- Found " . count($existing_scores) . " existing scores to migrate to June 2026.\n";

        $migrated_count = 0;
        foreach ($existing_scores as $score) {
            try {
                $ins_stmt = $pdo->prepare("
                    INSERT INTO area_evaluations (area_id, bulan, tahun, nilai_5r) 
                    VALUES (:area_id, 6, 2026, :nilai_5r)
                    ON DUPLICATE KEY UPDATE nilai_5r = VALUES(nilai_5r)
                ");
                $ins_stmt->execute([
                    'area_id' => $score['id'],
                    'nilai_5r' => $score['nilai_5r']
                ]);
                $migrated_count++;
            } catch (PDOException $e) {
                echo "  Failed migrating score for area ID {$score['id']}: " . $e->getMessage() . "\n";
            }
        }
        echo "- Successfully migrated $migrated_count scores to June 2026.\n";

        // 3. Drop column 'nilai_5r' from 'areas' table
        $pdo->exec("ALTER TABLE areas DROP COLUMN nilai_5r");
        echo "- Legacy column 'nilai_5r' dropped from 'areas' table.\n";
    } else {
        echo "- Legacy column 'nilai_5r' does not exist in 'areas'. Data migration skipped.\n";
    }

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
