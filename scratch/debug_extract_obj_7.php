<?php
require_once __DIR__ . '/test_extractor.php';

// Instantiate parser to load fonts
$file = 'c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf';
$parser = new SimplePDFParser($file);

// Reflect and run extractStreamText on 7
$ref = new ReflectionClass($parser);
$method = $ref->getMethod('extractStreamText');
$method->setAccessible(true);

$result = $method->invoke($parser, 7);
echo "Result length: " . strlen($result) . "\n";
echo "Result excerpt:\n";
var_dump($result);
?>
