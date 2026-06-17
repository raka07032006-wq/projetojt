<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Redirect based on role
if ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit;
} else {
    header("Location: division/dashboard.php");
    exit;
}
?>
