<?php
$css = file_get_contents(__DIR__ . '/../assets/css/style.css');
$len = strlen($css);
$braces = 0;
$in_comment = false;
for ($i = 0; $i < $len; $i++) {
    if ($css[$i] === '/' && $css[$i+1] === '*') {
        $in_comment = true;
        $i++;
        continue;
    }
    if ($css[$i] === '*' && $css[$i+1] === '/') {
        $in_comment = false;
        $i++;
        continue;
    }
    if ($in_comment) continue;
    
    if ($css[$i] === '{') {
        $braces++;
    } elseif ($css[$i] === '}') {
        $braces--;
        if ($braces < 0) {
            echo "Unmatched closing brace at char $i\n";
        }
    }
}
echo "Final braces count: $braces\n";
