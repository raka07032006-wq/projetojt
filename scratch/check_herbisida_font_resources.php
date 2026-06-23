<?php
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R Herbisida - Apr_compressed.pdf';
$content = file_get_contents($file);

echo "=== Font Resources in Herbisida ===\n";
preg_match_all("/\/Font\s*<<\s*(.*?)\s*>>/is", $content, $m);
foreach ($m[0] as $idx => $match) {
    echo "Match #$idx:\n" . trim($match) . "\n\n";
}
?>
