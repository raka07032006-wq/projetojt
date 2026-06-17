<?php
$html = file_get_contents('http://localhost/ProjectsOJT/admin/areas.php');
// Find <aside class="sidebar">
if (preg_match('/<aside[^>]*class="([^"]*)"/i', $html, $matches)) {
    echo "Sidebar class: " . $matches[1] . "\n";
} else {
    echo "Sidebar not found\n";
}
