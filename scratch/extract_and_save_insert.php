<?php
$file = 'C:/Users/asus/.gemini/antigravity-ide/brain/4e72136d-c399-46e7-a8bc-2e7dfcc52442/.system_generated/logs/transcript.jsonl';
$handle = fopen($file, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        if (strpos($line, 'insert_april_data.php') !== false && strpos($line, 'tool_calls') !== false) {
            $data = json_decode($line, true);
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $tc) {
                    if (isset($tc['args']['TargetFile']) && strpos($tc['args']['TargetFile'], 'insert_april_data.php') !== false) {
                        file_put_contents('c:/xampp/htdocs/ProjectsOJT/scratch/recovered_insert_april_data.php', $tc['args']['CodeContent']);
                        echo "Successfully wrote recovered script to scratch/recovered_insert_april_data.php\n";
                        break 2;
                    }
                }
            }
        }
    }
    fclose($handle);
} else {
    echo "Failed to open transcript.\n";
}
?>
