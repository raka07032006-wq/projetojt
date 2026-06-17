<?php
require_once __DIR__ . '/config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$user_role = $_SESSION['role'] ?? '';
$division_id = $_SESSION['division_id'] ?? 0;

if ($action === 'mark_all_read') {
    try {
        if ($user_role === 'admin') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_role = 'admin'");
            $stmt->execute();
        } else {
            if (!empty($_SESSION['area_name'])) {
                $stmt = $pdo->prepare("
                    UPDATE notifications n
                    JOIN findings f ON n.finding_id = f.id
                    SET n.is_read = 1 
                    WHERE n.recipient_role = 'division' AND n.division_id = :div_id AND f.area = :area_name
                ");
                $stmt->execute([
                    'div_id' => $division_id,
                    'area_name' => $_SESSION['area_name']
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_role = 'division' AND division_id = :div_id");
                $stmt->execute(['div_id' => $division_id]);
            }
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'read') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            if ($user_role === 'admin') {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND recipient_role = 'admin'");
                $stmt->execute(['id' => $id]);
            } else {
                if (!empty($_SESSION['area_name'])) {
                    $stmt = $pdo->prepare("
                        UPDATE notifications n
                        JOIN findings f ON n.finding_id = f.id
                        SET n.is_read = 1 
                        WHERE n.id = :id AND n.recipient_role = 'division' AND n.division_id = :div_id AND f.area = :area_name
                    ");
                    $stmt->execute([
                        'id' => $id,
                        'div_id' => $division_id,
                        'area_name' => $_SESSION['area_name']
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND recipient_role = 'division' AND division_id = :div_id");
                    $stmt->execute(['id' => $id, 'div_id' => $division_id]);
                }
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid Action']);
