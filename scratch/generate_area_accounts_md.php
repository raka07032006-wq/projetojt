<?php
require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->query("
        SELECT u.username, d.name AS division_name, a.name AS area_name 
        FROM users u 
        JOIN divisions d ON u.division_id = d.id 
        JOIN areas a ON u.area_id = a.id
        ORDER BY d.name ASC, a.name ASC
    ");
    $users = $stmt->fetchAll();

    $outputPath = 'C:/Users/ajiji.CBAPABRIK/.gemini/antigravity-ide/brain/123b80f6-68f3-4f15-9c46-c5a6908e955f/area_accounts.md';
    
    $md = "# Daftar Akun Ketua/Staf Setiap Area\n\n";
    $md .= "Berikut adalah daftar lengkap username untuk akun login setiap area kerja, dikelompokkan berdasarkan divisi masing-masing.\n\n";
    $md .= "> [!NOTE]\n";
    $md .= "> **Password Default**: Semua akun area di bawah ini menggunakan password default: `password123`\n\n";
    
    $current_div = '';
    foreach ($users as $u) {
        if ($current_div !== $u['division_name']) {
            $current_div = $u['division_name'];
            $md .= "\n## Divisi: " . $current_div . "\n\n";
            $md .= "| Nama Area | Username | Password |\n";
            $md .= "|---|---|---|\n";
        }
        $md .= "| " . $u['area_name'] . " | `" . $u['username'] . "` | `password123` |\n";
    }
    
    file_put_contents($outputPath, $md);
    echo "Successfully generated area_accounts.md at: " . $outputPath . "\n";
} catch (Exception $e) {
    echo "Error generating file: " . $e->getMessage() . "\n";
}
