<?php
$file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/.system_generated/logs/transcript.jsonl';
$handle = fopen($file, 'r');
if ($handle) {
    $line_num = 0;
    while (($line = fgets($handle)) !== false) {
        $line_num++;
        // Search for keywords: python, script, PIL, slice, image
        if (stripos($line, 'python') !== false || stripos($line, 'slice') !== false || stripos($line, 'coordinate') !== false || stripos($line, 'png') !== false) {
            echo "Line $line_num: " . substr($line, 0, 300) . "...\n";
        }
    }
    fclose($handle);
} else {
    echo "Could not open transcript.\n";
}
?>
