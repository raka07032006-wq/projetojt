<?php
$file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/media__1782215445774.png';
$img = imagecreatefrompng($file);
$width = imagesx($img);
$height = imagesy($img);

for ($y = 0; $y < $height; $y++) {
    $white_count = 0;
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        // White or near white (background)
        if ($r > 240 && $g > 240 && $b > 240) {
            $white_count++;
        }
    }
    $ratio = $white_count / $width;
    if ($ratio > 0.50) {
        echo "y=$y: ratio=" . round($ratio, 3) . "\n";
    }
}
?>
