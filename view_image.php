<?php
require_once __DIR__ . '/config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("HTTP/1.1 404 Not Found");
    exit("Image not found");
}

// Fetch image details from database
$stmt = $pdo->prepare("SELECT image_data, mime_type, image_path FROM finding_images WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$img = $stmt->fetch();

if (!$img) {
    header("HTTP/1.1 404 Not Found");
    exit("Image not found");
}

if ($img['image_data'] !== null) {
    // Serve from database BLOB
    header("Content-Type: " . ($img['mime_type'] ?: 'image/jpeg'));
    header("Content-Length: " . strlen($img['image_data']));
    echo $img['image_data'];
    exit;
} else if (!empty($img['image_path'])) {
    // Fallback: Serve from local uploads folder
    $file_path = __DIR__ . '/uploads/' . $img['image_path'];
    if (file_exists($file_path)) {
        $mime = @mime_content_type($file_path) ?: 'image/jpeg';
        header("Content-Type: " . $mime);
        header("Content-Length: " . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

header("HTTP/1.1 404 Not Found");
exit("Image not found");
