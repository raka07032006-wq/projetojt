<?php
$file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/media__1782215445774.png';
$info = getimagesize($file);
echo "Width: {$info[0]}\n";
echo "Height: {$info[1]}\n";
echo "Mime: {$info['mime']}\n";
?>
