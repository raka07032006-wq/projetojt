<?php
$file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/.system_generated/logs/transcript.jsonl';
$handle = fopen($file, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        if (strpos($line, 'insert_april_data.php') !== false && strpos($line, 'CodeContent') !== false) {
            echo "=== FOUND WRITE_TO_FILE FOR insert_april_data.php ===\n";
            $data = json_decode($line, true);
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $tc) {
                    if (isset($tc['args']['TargetFile']) && strpos($tc['args']['TargetFile'], 'insert_april_data.php') !== false) {
                        echo $tc['args']['CodeContent'] . "\n\n";
                    }
                }
            }
        }
        if (strpos($line, 'analyze_grid.php') !== false && strpos($line, 'CodeContent') !== false) {
            echo "=== FOUND WRITE_TO_FILE FOR analyze_grid.php ===\n";
            $data = json_decode($line, true);
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $tc) {
                    if (isset($tc['args']['TargetFile']) && strpos($tc['args']['TargetFile'], 'analyze_grid.php') !== false) {
                        echo $tc['args']['CodeContent'] . "\n\n";
                    }
                }
            }
        }
    }
    fclose($handle);
}
?>
