<?php
require_once __DIR__ . '/../config/db.php';

// Prevent browser timeout for large migrations
set_time_limit(300);

function compress_image($file_path, $max_width = 800, $max_height = 800, $quality = 75) {
    if (!file_exists($file_path)) {
        return null;
    }
    
    // Fallback if GD is not loaded
    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
        return file_get_contents($file_path);
    }
    
    $mime = mime_content_type($file_path);
    
    // Create image resource based on type
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $img = @imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $img = @imagecreatefrompng($file_path);
            break;
        case 'image/gif':
            $img = @imagecreatefromgif($file_path);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $img = @imagecreatefromwebp($file_path);
            } else {
                return file_get_contents($file_path);
            }
            break;
        default:
            return file_get_contents($file_path);
    }
    
    if (!$img) {
        return file_get_contents($file_path);
    }
    
    $width = imagesx($img);
    $height = imagesy($img);
    $ratio = $width / $height;
    
    // Resize if larger than max width/height
    if ($width > $max_width || $height > $max_height) {
        if ($max_width / $max_height > $ratio) {
            $new_height = $max_height;
            $new_width = $max_height * $ratio;
        } else {
            $new_width = $max_width;
            $new_height = $max_width / $ratio;
        }
        
        $new_img = imagecreatetruecolor($new_width, $new_height);
        
        // Handle transparency
        imagealphablending($new_img, false);
        imagesavealpha($new_img, true);
        
        imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($img);
        $img = $new_img;
    }
    
    // Compress and save as JPEG to output buffer
    ob_start();
    imagejpeg($img, null, $quality);
    $data = ob_get_clean();
    
    imagedestroy($img);
    return $data;
}

try {
    // 1. Add image_data and mime_type columns if they don't exist
    $stmt_cols = $pdo->query("SHOW COLUMNS FROM finding_images");
    $columns = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('image_data', $columns)) {
        $pdo->exec("ALTER TABLE finding_images ADD COLUMN image_data LONGBLOB DEFAULT NULL");
        echo "- Added 'image_data' column to 'finding_images' table.\n";
    } else {
        echo "- 'image_data' column already exists.\n";
    }

    if (!in_array('mime_type', $columns)) {
        $pdo->exec("ALTER TABLE finding_images ADD COLUMN mime_type VARCHAR(50) DEFAULT 'image/jpeg'");
        echo "- Added 'mime_type' column to 'finding_images' table.\n";
    } else {
        echo "- 'mime_type' column already exists.\n";
    }

    // 2. Select all images that don't have binary data yet
    $stmt_fetch = $pdo->query("SELECT id, image_path FROM finding_images WHERE image_data IS NULL");
    $images = $stmt_fetch->fetchAll();
    
    echo "- Found " . count($images) . " images to migrate.\n";
    
    $success_count = 0;
    $missing_count = 0;

    $stmt_update = $pdo->prepare("UPDATE finding_images SET image_data = :image_data, mime_type = :mime_type WHERE id = :id");

    foreach ($images as $img) {
        $file_path = __DIR__ . '/../uploads/' . $img['image_path'];
        
        if (file_exists($file_path)) {
            // Compress image to JPEG
            $compressed_data = compress_image($file_path, 800, 800, 75);
            
            if ($compressed_data !== null) {
                $stmt_update->execute([
                    'image_data' => $compressed_data,
                    'mime_type' => 'image/jpeg',
                    'id' => $img['id']
                ]);
                
                // Safely delete physical file now that it is stored in database
                @unlink($file_path);
                
                $success_count++;
                echo "  [MIGRATED & DELETED FILE] ID: {$img['id']} - File: {$img['image_path']}\n";
            }
        } else {
            $missing_count++;
            echo "  [WARNING] File not found: {$img['image_path']} (ID: {$img['id']})\n";
        }
    }

    echo "\nMigration complete!\n";
    echo "- Successfully migrated: $success_count images.\n";
    echo "- Missing physical files: $missing_count images.\n";

} catch (Exception $e) {
    echo "\nError during migration: " . $e->getMessage() . "\n";
}
