<?php
require_once __DIR__ . '/test_extractor.php';

$parser = new SimplePDFParser('c:/xampp/htdocs/ProjectsOJT/Data April/Rekap 5R HRGA - APRIL 2026.pdf');

// Reflect private properties to debug
$ref = new ReflectionClass($parser);

$font_to_unicode_prop = $ref->getProperty('font_to_unicode');
$font_to_unicode_prop->setAccessible(true);
$font_to_unicode = $font_to_unicode_prop->getValue($parser);

echo "=== Font to Unicode keys ===\n";
print_r(array_keys($font_to_unicode));

echo "\n=== FT32 CMap map ===\n";
if (isset($font_to_unicode['FT32'])) {
    echo "FT32 is mapped! Sample keys:\n";
    print_r(array_slice($font_to_unicode['FT32'], 0, 10));
} else {
    echo "FT32 is NOT mapped!\n";
}
?>
