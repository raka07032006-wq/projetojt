<?php
$file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/media__1782215445774.png';
$img = imagecreatefrompng($file);
$width = imagesx($img);

$rows = [
    0 => ['start' => 30, 'end' => 168],
    1 => ['start' => 173, 'end' => 264],
    2 => ['start' => 269, 'end' => 359],
    3 => ['start' => 363, 'end' => 456],
    4 => ['start' => 459, 'end' => 566]
];

foreach ($rows as $r_idx => $r) {
    echo "=== Columns for Row $r_idx (Y: {$r['start']} to {$r['end']}) ===\n";
    $h = $r['end'] - $r['start'] + 1;
    
    // For each x, calculate ratio of white pixels in this row's vertical slice
    $x_white = [];
    for ($x = 0; $x < $width; $x++) {
        $white_count = 0;
        for ($y = $r['start']; $y <= $r['end']; $y++) {
            $rgb = imagecolorat($img, $x, $y);
            $red = ($rgb >> 16) & 0xFF;
            $green = ($rgb >> 8) & 0xFF;
            $blue = $rgb & 0xFF;
            if ($red > 240 && $green > 240 && $blue > 240) {
                $white_count++;
            }
        }
        $x_white[$x] = $white_count / $h;
    }
    
    // Find gaps where x_white is very high (e.g. > 0.98)
    $in_gap = false;
    $gap_start = 0;
    $gaps = [];
    for ($x = 0; $x < $width; $x++) {
        $is_gap = ($x_white[$x] > 0.98);
        if ($is_gap && !$in_gap) {
            $gap_start = $x;
            $in_gap = true;
        } else if (!$is_gap && $in_gap) {
            $gaps[] = [$gap_start, $x - 1];
            $in_gap = false;
        }
    }
    if ($in_gap) {
        $gaps[] = [$gap_start, $width - 1];
    }
    
    // Inferred column boundaries (cells)
    $cells = [];
    $last_end = 0;
    foreach ($gaps as $g) {
        if ($g[0] > $last_end) {
            // Check if cell is wide enough to be a photo (e.g. > 10px)
            if ($g[0] - $last_end > 10) {
                $cells[] = [$last_end, $g[0] - 1];
            }
        }
        $last_end = $g[1] + 1;
    }
    if ($last_end < $width) {
        if ($width - $last_end > 10) {
            $cells[] = [$last_end, $width - 1];
        }
    }
    
    echo "Detected " . count($cells) . " cells:\n";
    foreach ($cells as $c_idx => $c) {
        echo "  Cell #$c_idx: X: {$c[0]} to {$c[1]} (width: " . ($c[1] - $c[0] + 1) . "px)\n";
    }
    echo "\n";
}
?>
