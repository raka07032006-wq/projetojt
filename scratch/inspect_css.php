<?php
$css = file_get_contents(__DIR__ . '/../assets/css/style.css');
$lines = explode("\n", $css);
foreach ($lines as $i => $line) {
    if (strpos($line, '@media') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
