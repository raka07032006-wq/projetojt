<?php
$file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/media__1782215445774.png';
$img = imagecreatefrompng($file);
$width = imagesx($img);
$height = imagesy($img);

// 1. Analyze row white pixels
$row_white_ratio = [];
for ($y = 0; $y < $height; $y++) {
    $white_count = 0;
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        // White or near white (e.g. background)
        if ($r > 240 && $g > 240 && $b > 240) {
            $white_count++;
        }
    }
    $row_white_ratio[$y] = $white_count / $width;
}

// Find horizontal dividers where white ratio is high (e.g. > 90% or locally maximum)
echo "=== Horizontal scan (row white ratios) ===\n";
// Let's identify the rows that are background spacing.
// We can print ranges of y where white ratio is > 0.95
$in_gap = false;
$gap_start = 0;
$gaps = [];
for ($y = 0; $y < $height; $y++) {
    $is_gap = ($row_white_ratio[$y] > 0.95);
    if ($is_gap && !$in_gap) {
        $gap_start = $y;
        $in_gap = true;
    } else if (!$is_gap && $in_gap) {
        $gaps[] = [$gap_start, $y - 1];
        $in_gap = false;
    }
}
if ($in_gap) {
    $gaps[] = [$gap_start, $height - 1];
}

echo "Detected background horizontal gaps at ranges:\n";
foreach ($gaps as $idx => $g) {
    echo "Gap #$idx: {$g[0]} to {$g[1]} (size: " . ($g[1] - $g[0] + 1) . "px)\n";
}

// Let's infer the row boundaries
$row_boundaries = [];
$last_end = 0;
foreach ($gaps as $g) {
    if ($g[0] > $last_end) {
        $row_boundaries[] = [$last_end, $g[0] - 1];
    }
    $last_end = $g[1] + 1;
}
if ($last_end < $height) {
    $row_boundaries[] = [$last_end, $height - 1];
}

echo "\nInferred Row Boundaries:\n";
foreach ($row_boundaries as $idx => $r) {
    echo "Row #$idx: {$r[0]} to {$r[1]} (height: " . ($r[1] - $r[0] + 1) . "px)\n";
}
?>
