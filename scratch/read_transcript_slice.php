<?php
$file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/.system_generated/logs/transcript.jsonl';
$handle = fopen($file, 'r');
if ($handle) {
    $line_num = 0;
    while (($line = fgets($handle)) !== false) {
        $line_num++;
        if ($line_num >= 70 && $line_num <= 85) {
            echo "--- LINE $line_num ---\n";
            echo $line . "\n";
        }
    }
    fclose($handle);
}
?>
