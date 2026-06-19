<?php
require_once __DIR__ . '/config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        // Query user with division name and area name if available
        $stmt = $pdo->prepare("
            SELECT u.*, d.name AS division_name, a.name AS area_name 
            FROM users u 
            LEFT JOIN divisions d ON u.division_id = d.id 
            LEFT JOIN areas a ON u.area_id = a.id 
            WHERE u.username = :username 
            LIMIT 1
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['division_id'] = $user['division_id'];
            $_SESSION['division_name'] = $user['division_name'];
            $_SESSION['area_id'] = $user['area_id'];
            $_SESSION['area_name'] = $user['area_name'];

            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua kolom.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Monitoring Audit 5R</title>
    <link rel="icon" type="image/png" href="assets/images/logo_5r.png?v=2">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">
    <!-- Ambient Glow Effects -->
    <div class="auth-glow-1"></div>
    <div class="auth-glow-2"></div>
    
    <div class="auth-card">
        <div class="auth-header">
            <img src="assets/images/logo_5r.png?v=2" alt="5R Logo" class="auth-logo">
            <h1 class="auth-title">Audit 5R HRGA</h1>
            <p class="auth-subtitle">Sistem Monitoring & Tindakan Perbaikan</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus autocomplete="username">
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Masuk ke Sistem</button>
        </form>
    </div>
</body>
</html>
