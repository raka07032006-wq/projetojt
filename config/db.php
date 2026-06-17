<?php
// Database connection configuration (standard XAMPP MySQL credentials)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'audit_5r');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Helper to log a notification for audit events
 */
function create_notification($pdo, $finding_id, $division_id, $title, $message, $recipient_role = 'admin') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (finding_id, division_id, title, message, recipient_role) 
            VALUES (:finding_id, :division_id, :title, :message, :recipient_role)
        ");
        return $stmt->execute([
            'finding_id' => $finding_id,
            'division_id' => $division_id,
            'title' => $title,
            'message' => $message,
            'recipient_role' => $recipient_role
        ]);
    } catch (PDOException $e) {
        return false;
    }
}
/**
 * Helper to get letter grade based on audit score (Nilai 5R)
 * Following the Excel logic:
 * - < 1.0 -> E
 * - < 2.0 -> D
 * - < 2.3 -> C
 * - < 2.7 -> C+
 * - < 3.0 -> B-
 * - < 3.3 -> B
 * - < 3.7 -> B+
 * - < 4.0 -> A-
 * - >= 4.0 -> A
 */
function get_letter_grade($score) {
    if ($score === null || $score === '') {
        return '-';
    }
    $val = floatval($score);
    if ($val < 1.0) return 'E';
    if ($val < 2.0) return 'D';
    if ($val < 2.3) return 'C';
    if ($val < 2.7) return 'C+';
    if ($val < 3.0) return 'B-';
    if ($val < 3.3) return 'B';
    if ($val < 3.7) return 'B+';
    if ($val < 4.0) return 'A-';
    return 'A';
}
?>
